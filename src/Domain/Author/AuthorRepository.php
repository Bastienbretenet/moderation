<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Author;

interface AuthorRepository
{
    public function findByExternalId(string $externalId): ?Author;

    public function findOrCreateByExternalId(string $externalId): Author;

    public function save(Author $author): void;
}
