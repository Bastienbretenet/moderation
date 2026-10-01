<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'comment_status_change')]
final class CommentStatusChange
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'uuid', unique: true)]
        private Uuid $id,
        #[ORM\ManyToOne(inversedBy: 'statusHistory')]
        #[ORM\JoinColumn(nullable: false)]
        private Comment $comment,
        #[ORM\Column(length: 32, nullable: true, enumType: ModerationStatus::class)]
        private ?ModerationStatus $previousStatus,
        #[ORM\Column(length: 32, enumType: ModerationStatus::class)]
        private ModerationStatus $newStatus,
        #[ORM\Column(length: 32, enumType: StatusChangeOrigin::class)]
        private StatusChangeOrigin $origin,
        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $reason,
        #[ORM\Column]
        private DateTimeImmutable $changedAt,
    ) {
    }

    public function previousStatus(): ?ModerationStatus
    {
        return $this->previousStatus;
    }

    public function newStatus(): ModerationStatus
    {
        return $this->newStatus;
    }

    public function origin(): StatusChangeOrigin
    {
        return $this->origin;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function changedAt(): DateTimeImmutable
    {
        return $this->changedAt;
    }
}
