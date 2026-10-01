<?php

declare(strict_types=1);

namespace SudOuest\Comment\Infrastructure\Moderation\OpenRouter;

enum ModerationVerdict: string
{
    case Publish = 'publish';
    case Reject = 'reject';
}
