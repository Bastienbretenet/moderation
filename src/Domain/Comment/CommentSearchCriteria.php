<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment;

final readonly class CommentSearchCriteria
{
    public function __construct(
        public ?string $publisher,
        public ?ModerationStatus $status,
        public ?string $source,
        public ?string $authorExternalId,
        public int $page,
        public int $limit,
    ) {
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
