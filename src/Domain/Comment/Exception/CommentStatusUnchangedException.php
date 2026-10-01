<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment\Exception;

use SudOuest\Comment\Domain\Comment\ModerationStatus;
use SudOuest\Comment\Domain\DomainException;
use Symfony\Component\Uid\Uuid;

final class CommentStatusUnchangedException extends DomainException
{
    public static function withStatus(Uuid $commentId, ModerationStatus $status): self
    {
        return new self(sprintf('Comment "%s" is already %s.', $commentId->toRfc4122(), $status->value));
    }
}
