<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment;

enum StatusChangeOrigin: string
{
    case Submission = 'submission';
    case AuthorBan = 'author_ban';
    case Llm = 'llm';
    case Operator = 'operator';
}
