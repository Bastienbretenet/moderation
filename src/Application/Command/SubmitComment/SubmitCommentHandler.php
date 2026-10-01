<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Command\SubmitComment;

use Psr\Clock\ClockInterface;
use SudOuest\Comment\Domain\Author\AuthorRepository;
use SudOuest\Comment\Domain\Comment\Comment;
use SudOuest\Comment\Domain\Comment\CommentRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
final readonly class SubmitCommentHandler
{
    public function __construct(
        private CommentRepository $commentRepository,
        private AuthorRepository $authorRepository,
        private ValidatorInterface $validator,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(SubmitCommentCommand $command): string
    {
        $violations = $this->validator->validate($command);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($command, $violations);
        }

        $author = $command->authorId !== null
            ? $this->authorRepository->findOrCreateByExternalId($command->authorId)
            : null;

        $comment = Comment::submit(
            Uuid::v7(),
            $command->publisher,
            $command->source,
            $author,
            $command->content,
            $this->clock->now(),
        );

        $this->commentRepository->save($comment);

        return $comment->id()->toRfc4122();
    }
}
