<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Author;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'author')]
final class Author
{
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $bannedAt = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'uuid', unique: true)]
        private Uuid $id,
        #[ORM\Column(length: 255, unique: true)]
        private string $externalId,
    ) {
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function externalId(): string
    {
        return $this->externalId;
    }

    public function ban(DateTimeImmutable $bannedAt): void
    {
        $this->bannedAt = $bannedAt;
    }

    public function isBanned(): bool
    {
        return $this->bannedAt !== null;
    }
}
