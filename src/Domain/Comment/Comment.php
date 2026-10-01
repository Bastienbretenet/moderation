<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SudOuest\Comment\Domain\Author\Author;
use SudOuest\Comment\Domain\Comment\Exception\CommentAlreadyModeratedException;
use SudOuest\Comment\Domain\Comment\Exception\CommentStatusUnchangedException;
use SudOuest\Comment\Domain\Moderation\ModerationDecision;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'comment')]
#[ORM\Index(name: 'comment_publisher_status_submitted_at_idx', columns: ['publisher', 'status', 'submitted_at'])]
#[ORM\Index(name: 'comment_status_submitted_at_idx', columns: ['status', 'submitted_at'])]
#[ORM\Index(name: 'comment_submitted_at_idx', columns: ['submitted_at'])]
#[ORM\Index(name: 'comment_source_idx', columns: ['source'])]
final class Comment
{
    #[ORM\Column(length: 32, enumType: ModerationStatus::class)]
    private ModerationStatus $status = ModerationStatus::Pending;

    #[ORM\Column(length: 32, nullable: true, enumType: RejectionReason::class)]
    private ?RejectionReason $rejectionReason = null;

    #[ORM\Column(length: 64, nullable: true, enumType: IllegalContentCategory::class)]
    private ?IllegalContentCategory $category = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $moderationExplanation = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $moderatedAt = null;

    /**
     * @var Collection<int, CommentStatusChange>
     */
    #[ORM\OneToMany(targetEntity: CommentStatusChange::class, mappedBy: 'comment', cascade: ['persist'])]
    #[ORM\OrderBy(['changedAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $statusHistory;

    #[ORM\Version]
    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $version = 1;

    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'uuid', unique: true)]
        private Uuid $id,
        #[ORM\Column(length: 100)]
        private string $publisher,
        #[ORM\Column(length: 255)]
        private string $source,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: true)]
        private ?Author $author,
        #[ORM\Column(type: 'text')]
        private string $content,
        #[ORM\Column]
        private DateTimeImmutable $submittedAt,
    ) {
        $this->statusHistory = new ArrayCollection();
    }

    public static function submit(
        Uuid $id,
        string $publisher,
        string $source,
        ?Author $author,
        string $content,
        DateTimeImmutable $submittedAt,
    ): self {
        $comment = new self($id, $publisher, $source, $author, $content, $submittedAt);

        if ($author !== null && $author->isBanned()) {
            $comment->status = ModerationStatus::Rejected;
            $comment->rejectionReason = RejectionReason::AuthorBanned;
            $comment->moderatedAt = $submittedAt;
            $comment->recordStatusChange(null, StatusChangeOrigin::AuthorBan, null, $submittedAt);

            return $comment;
        }

        $comment->recordStatusChange(null, StatusChangeOrigin::Submission, null, $submittedAt);

        return $comment;
    }

    public function applyModerationDecision(ModerationDecision $decision, DateTimeImmutable $moderatedAt): void
    {
        if ($decision->illegalContentCategory === null) {
            $this->publish($decision->explanation, $moderatedAt);

            return;
        }

        $this->reject($decision->illegalContentCategory, $decision->explanation, $moderatedAt);
    }

    public function publishManually(?string $reason, DateTimeImmutable $changedAt): void
    {
        $this->assertStatusDiffersFrom(ModerationStatus::Published);

        $this->rejectionReason = null;
        $this->category = null;
        $this->changeStatus(ModerationStatus::Published, StatusChangeOrigin::Operator, $reason, $changedAt);
    }

    public function rejectManually(?string $reason, DateTimeImmutable $changedAt): void
    {
        $this->assertStatusDiffersFrom(ModerationStatus::Rejected);

        $this->rejectionReason = RejectionReason::Operator;
        $this->category = null;
        $this->changeStatus(ModerationStatus::Rejected, StatusChangeOrigin::Operator, $reason, $changedAt);
    }

    public function isPending(): bool
    {
        return $this->status === ModerationStatus::Pending;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function publisher(): string
    {
        return $this->publisher;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function author(): ?Author
    {
        return $this->author;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function status(): ModerationStatus
    {
        return $this->status;
    }

    public function rejectionReason(): ?RejectionReason
    {
        return $this->rejectionReason;
    }

    public function category(): ?IllegalContentCategory
    {
        return $this->category;
    }

    public function moderationExplanation(): ?string
    {
        return $this->moderationExplanation;
    }

    public function submittedAt(): DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function moderatedAt(): ?DateTimeImmutable
    {
        return $this->moderatedAt;
    }

    /**
     * @return list<CommentStatusChange>
     */
    public function statusHistory(): array
    {
        return array_values($this->statusHistory->toArray());
    }

    private function publish(string $explanation, DateTimeImmutable $moderatedAt): void
    {
        $this->assertPending();

        $this->changeStatus(ModerationStatus::Published, StatusChangeOrigin::Llm, $explanation, $moderatedAt);
    }

    private function reject(
        IllegalContentCategory $category,
        string $explanation,
        DateTimeImmutable $moderatedAt,
    ): void {
        $this->assertPending();

        $this->rejectionReason = RejectionReason::IllegalContent;
        $this->category = $category;
        $this->changeStatus(ModerationStatus::Rejected, StatusChangeOrigin::Llm, $explanation, $moderatedAt);
    }

    private function changeStatus(
        ModerationStatus $newStatus,
        StatusChangeOrigin $origin,
        ?string $reason,
        DateTimeImmutable $changedAt,
    ): void {
        $previousStatus = $this->status;

        $this->status = $newStatus;
        $this->moderationExplanation = $reason;
        $this->moderatedAt = $changedAt;
        $this->recordStatusChange($previousStatus, $origin, $reason, $changedAt);
    }

    private function recordStatusChange(
        ?ModerationStatus $previousStatus,
        StatusChangeOrigin $origin,
        ?string $reason,
        DateTimeImmutable $changedAt,
    ): void {
        $this->statusHistory->add(new CommentStatusChange(
            Uuid::v7(),
            $this,
            $previousStatus,
            $this->status,
            $origin,
            $reason,
            $changedAt,
        ));
    }

    private function assertPending(): void
    {
        if (!$this->isPending()) {
            throw CommentAlreadyModeratedException::withId($this->id);
        }
    }

    private function assertStatusDiffersFrom(ModerationStatus $requestedStatus): void
    {
        if ($this->status === $requestedStatus) {
            throw CommentStatusUnchangedException::withStatus($this->id, $requestedStatus);
        }
    }
}
