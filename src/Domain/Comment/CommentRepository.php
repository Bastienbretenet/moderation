<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment;

use Symfony\Component\Uid\Uuid;

interface CommentRepository
{
    public function save(Comment $comment): void;

    public function findById(Uuid $id): ?Comment;
}
