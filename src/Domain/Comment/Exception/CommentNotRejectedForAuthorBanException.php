<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment\Exception;

use SudOuest\Comment\Domain\DomainException;
use Symfony\Component\Uid\Uuid;

final class CommentNotRejectedForAuthorBanException extends DomainException
{
    public static function withId(Uuid $commentId): self
    {
        return new self(sprintf('Comment "%s" was not rejected because its author was banned.', $commentId->toRfc4122()));
    }
}
