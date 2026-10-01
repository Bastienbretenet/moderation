<?php

declare(strict_types=1);

namespace SudOuest\Comment\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use SudOuest\Comment\Domain\Comment\Comment;
use SudOuest\Comment\Domain\Comment\CommentRepository;
use SudOuest\Comment\Domain\Comment\CommentSearchCriteria;
use SudOuest\Comment\Domain\Comment\CommentSearchResult;
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

    public function search(CommentSearchCriteria $criteria): CommentSearchResult
    {
        $results = $this->createFilteredQueryBuilder($criteria)
            ->addSelect('author')
            ->orderBy('comment.submittedAt', 'DESC')
            ->addOrderBy('comment.id', 'DESC')
            ->setFirstResult($criteria->offset())
            ->setMaxResults($criteria->limit)
            ->getQuery()
            ->getResult();

        $comments = [];
        foreach ((array) $results as $comment) {
            if ($comment instanceof Comment) {
                $comments[] = $comment;
            }
        }

        $total = (int) $this->createFilteredQueryBuilder($criteria)
            ->select('COUNT(comment.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return new CommentSearchResult($comments, $total);
    }

    private function createFilteredQueryBuilder(CommentSearchCriteria $criteria): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('comment')
            ->from(Comment::class, 'comment')
            ->leftJoin('comment.author', 'author');

        if ($criteria->publisher !== null) {
            $queryBuilder->andWhere('comment.publisher = :publisher')->setParameter('publisher', $criteria->publisher);
        }

        if ($criteria->status !== null) {
            $queryBuilder->andWhere('comment.status = :status')->setParameter('status', $criteria->status);
        }

        if ($criteria->source !== null) {
            $queryBuilder->andWhere('comment.source = :source')->setParameter('source', $criteria->source);
        }

        if ($criteria->authorExternalId !== null) {
            $queryBuilder->andWhere('author.externalId = :authorExternalId')->setParameter('authorExternalId', $criteria->authorExternalId);
        }

        return $queryBuilder;
    }
}
