<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Author\Exception;

use SudOuest\Comment\Domain\DomainException;

final class AuthorNotFoundException extends DomainException
{
    public static function withExternalId(string $externalId): self
    {
        return new self(sprintf('Author "%s" was not found.', $externalId));
    }
}
