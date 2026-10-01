<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Query\GetComment;

use SudOuest\Comment\Domain\Comment\CommentRepository;
use SudOuest\Comment\Domain\Comment\Exception\CommentNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
final readonly class GetCommentHandler
{
    public function __construct(
        private CommentRepository $commentRepository,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(GetCommentQuery $query): CommentView
    {
        $violations = $this->validator->validate($query);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($query, $violations);
        }

        $commentId = Uuid::fromString($query->commentId);
        $comment = $this->commentRepository->findById($commentId)
            ?? throw CommentNotFoundException::withId($commentId);

        return CommentView::fromComment($comment);
    }
}
