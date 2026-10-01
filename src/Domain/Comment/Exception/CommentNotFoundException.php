<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment\Exception;

use SudOuest\Comment\Domain\DomainException;
use Symfony\Component\Uid\Uuid;

final class CommentNotFoundException extends DomainException
{
    public static function withId(Uuid $commentId): self
    {
        return new self(sprintf('Comment "%s" was not found.', $commentId->toRfc4122()));
    }
}
