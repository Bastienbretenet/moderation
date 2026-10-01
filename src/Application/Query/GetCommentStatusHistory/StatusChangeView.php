<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Query\GetCommentStatusHistory;

use DateTimeInterface;
use SudOuest\Comment\Domain\Comment\CommentStatusChange;

final readonly class StatusChangeView
{
    public function __construct(
        public ?string $previousStatus,
        public string $newStatus,
        public string $origin,
        public ?string $reason,
        public string $changedAt,
    ) {
    }

    public static function fromStatusChange(CommentStatusChange $statusChange): self
    {
        return new self(
            $statusChange->previousStatus()?->value,
            $statusChange->newStatus()->value,
            $statusChange->origin()->value,
            $statusChange->reason(),
            $statusChange->changedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
