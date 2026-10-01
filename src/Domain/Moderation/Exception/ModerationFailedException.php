<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Moderation\Exception;

use SudOuest\Comment\Domain\DomainException;
use Throwable;

final class ModerationFailedException extends DomainException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self(sprintf('Moderation failed: %s', $reason), previous: $previous);
    }
}
