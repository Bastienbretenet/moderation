<?php

declare(strict_types=1);

namespace SudOuest\Comment\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use SudOuest\Comment\Domain\Comment\Comment;
use SudOuest\Comment\Domain\Comment\CommentRepository;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineCommentRepository implements CommentRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Comment $comment): void
    {
        $this->entityManager->persist($comment);
        $this->entityManager->flush();
    }

    public function findById(Uuid $id): ?Comment
    {
        return $this->entityManager->find(Comment::class, $id);
    }
}
