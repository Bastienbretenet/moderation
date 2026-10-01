<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use SudOuest\Comment\Domain\Author\Author;
use SudOuest\Comment\Domain\Comment\Exception\CommentAlreadyModeratedException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'comment')]
#[ORM\Index(name: 'comment_publisher_status_idx', columns: ['publisher', 'status'])]
#[ORM\Index(name: 'comment_status_idx', columns: ['status'])]
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
        }

        return $comment;
    }

    public function publish(string $explanation, DateTimeImmutable $moderatedAt): void
    {
        $this->assertPending();

        $this->status = ModerationStatus::Published;
        $this->moderationExplanation = $explanation;
        $this->moderatedAt = $moderatedAt;
    }

    public function reject(
        IllegalContentCategory $category,
        string $explanation,
        DateTimeImmutable $moderatedAt,
    ): void {
        $this->assertPending();

        $this->status = ModerationStatus::Rejected;
        $this->rejectionReason = RejectionReason::IllegalContent;
        $this->category = $category;
        $this->moderationExplanation = $explanation;
        $this->moderatedAt = $moderatedAt;
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

    private function assertPending(): void
    {
        if ($this->status !== ModerationStatus::Pending) {
            throw CommentAlreadyModeratedException::withId($this->id);
        }
    }
}
