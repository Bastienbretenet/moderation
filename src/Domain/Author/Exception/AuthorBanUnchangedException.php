<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Author\Exception;

use SudOuest\Comment\Domain\DomainException;

final class AuthorBanUnchangedException extends DomainException
{
    public static function alreadyBanned(string $externalId): self
    {
        return new self(sprintf('Author "%s" is already banned.', $externalId));
    }

    public static function notBanned(string $externalId): self
    {
        return new self(sprintf('Author "%s" is not banned.', $externalId));
    }
}
