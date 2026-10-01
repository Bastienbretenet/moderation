<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Query;

use DateTimeInterface;
use SudOuest\Comment\Domain\Author\Author;

final readonly class AuthorView
{
    public function __construct(
        public string $authorId,
        public bool $banned,
        public ?string $bannedAt,
    ) {
    }

    public static function fromAuthor(Author $author): self
    {
        return new self(
            $author->externalId(),
            $author->isBanned(),
            $author->bannedAt()?->format(DateTimeInterface::ATOM),
        );
    }
}
