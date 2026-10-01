<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Author;

interface AuthorRepository
{
    public function findOrCreateByExternalId(string $externalId): Author;
}
