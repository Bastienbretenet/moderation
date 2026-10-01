<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Query\SearchComments;

use SudOuest\Comment\Application\Query\CommentView;

final readonly class CommentSearchView
{
    /**
     * @param list<CommentView> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
    ) {
    }
}
