<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment;

enum RejectionReason: string
{
    case AuthorBanned = 'author_banned';
    case IllegalContent = 'illegal_content';
}
