<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Command\ModerateComment;

use Psr\Clock\ClockInterface;
use SudOuest\Comment\Domain\Comment\CommentRepository;
use SudOuest\Comment\Domain\Comment\Exception\CommentNotFoundException;
use SudOuest\Comment\Domain\Moderation\Moderator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
final readonly class ModerateCommentHandler
{
    public function __construct(
        private CommentRepository $commentRepository,
        private Moderator $moderator,
        private ValidatorInterface $validator,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ModerateCommentCommand $command): void
    {
        $violations = $this->validator->validate($command);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($command, $violations);
        }

        $commentId = Uuid::fromString($command->commentId);
        $comment = $this->commentRepository->findById($commentId);
        if ($comment === null) {
            $commentNotFound = CommentNotFoundException::withId($commentId);

            throw new UnrecoverableMessageHandlingException($commentNotFound->getMessage(), previous: $commentNotFound);
        }

        if (!$comment->isPending()) {
            return;
        }

        $decision = $this->moderator->moderate($comment->content());
        $comment->applyModerationDecision($decision, $this->clock->now());

        $this->commentRepository->save($comment);
    }
}
