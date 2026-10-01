<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Command\UnbanAuthor;

use SudOuest\Comment\Application\Query\AuthorView;

final readonly class UnbannedAuthorView
{
    public function __construct(
        public AuthorView $author,
        public int $resubmittedCommentCount,
    ) {
    }
}
