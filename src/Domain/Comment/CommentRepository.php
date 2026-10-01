<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment;

use SudOuest\Comment\Domain\Author\Author;
use Symfony\Component\Uid\Uuid;

interface CommentRepository
{
    public function save(Comment $comment): void;

    public function findById(Uuid $id): ?Comment;

    /**
     * @return list<Comment>
     */
    public function findRejectedBecauseAuthorBanned(Author $author): array;

    /**
     * Newest first; comments submitted at the same time are ordered by identifier.
     */
    public function search(CommentSearchCriteria $criteria): CommentSearchResult;
}
