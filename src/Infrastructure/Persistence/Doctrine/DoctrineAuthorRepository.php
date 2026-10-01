<?php

declare(strict_types=1);

namespace SudOuest\Comment\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use SudOuest\Comment\Domain\Author\Author;
use SudOuest\Comment\Domain\Author\AuthorRepository;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineAuthorRepository implements AuthorRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findByExternalId(string $externalId): ?Author
    {
        return $this->entityManager->getRepository(Author::class)->findOneBy(['externalId' => $externalId]);
    }

    public function findOrCreateByExternalId(string $externalId): Author
    {
        $author = $this->findByExternalId($externalId);
        if ($author !== null) {
            return $author;
        }

        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO author (id, external_id) VALUES (:id, :externalId) ON CONFLICT (external_id) DO NOTHING',
            ['id' => Uuid::v7(), 'externalId' => $externalId],
            ['id' => UuidType::NAME],
        );

        return $this->findByExternalId($externalId)
            ?? throw new LogicException(sprintf('Author "%s" should exist after insertion.', $externalId));
    }

    public function save(Author $author): void
    {
        $this->entityManager->persist($author);
        $this->entityManager->flush();
    }
}
