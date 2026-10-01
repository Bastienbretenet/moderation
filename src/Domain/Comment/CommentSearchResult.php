<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment;

final readonly class CommentSearchResult
{
    /**
     * @param list<Comment> $comments
     */
    public function __construct(
        public array $comments,
        public int $total,
    ) {
    }
}
