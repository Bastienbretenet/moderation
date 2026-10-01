<?php

declare(strict_types=1);

namespace SudOuest\Comment\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use SudOuest\Comment\Domain\Author\Author;
use SudOuest\Comment\Domain\Comment\Comment;
use SudOuest\Comment\Domain\Comment\CommentRepository;
use SudOuest\Comment\Domain\Comment\CommentSearchCriteria;
use SudOuest\Comment\Domain\Comment\CommentSearchResult;
use SudOuest\Comment\Domain\Comment\RejectionReason;
use Symfony\Bridge\Doctrine\Types\UuidType;
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

    public function findRejectedBecauseAuthorBanned(Author $author): array
    {
        return $this->fetchComments($this->entityManager->createQueryBuilder()
            ->select('comment')
            ->from(Comment::class, 'comment')
            ->where('comment.author = :authorId')
            ->andWhere('comment.rejectionReason = :rejectionReason')
            ->setParameter('authorId', $author->id(), UuidType::NAME)
            ->setParameter('rejectionReason', RejectionReason::AuthorBanned)
            ->orderBy('comment.submittedAt', 'ASC'));
    }

    public function search(CommentSearchCriteria $criteria): CommentSearchResult
    {
        $comments = $this->fetchComments($this->createFilteredQueryBuilder($criteria)
            ->addSelect('author')
            ->orderBy('comment.submittedAt', 'DESC')
            ->addOrderBy('comment.id', 'DESC')
            ->setFirstResult($criteria->offset())
            ->setMaxResults($criteria->limit));

        $total = (int) $this->createFilteredQueryBuilder($criteria)
            ->select('COUNT(comment.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return new CommentSearchResult($comments, $total);
    }

    /**
     * @return list<Comment>
     */
    private function fetchComments(QueryBuilder $queryBuilder): array
    {
        $comments = [];
        foreach ((array) $queryBuilder->getQuery()->getResult() as $comment) {
            if ($comment instanceof Comment) {
                $comments[] = $comment;
            }
        }

        return $comments;
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
