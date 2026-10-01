<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Command\ModerateCommentManually;

use Psr\Clock\ClockInterface;
use SudOuest\Comment\Application\Query\CommentView;
use SudOuest\Comment\Domain\Comment\CommentRepository;
use SudOuest\Comment\Domain\Comment\Exception\CommentNotFoundException;
use SudOuest\Comment\Domain\Comment\ModerationStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
final readonly class ModerateCommentManuallyHandler
{
    public function __construct(
        private CommentRepository $commentRepository,
        private ValidatorInterface $validator,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ModerateCommentManuallyCommand $command): CommentView
    {
        $violations = $this->validator->validate($command);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($command, $violations);
        }

        $commentId = Uuid::fromString($command->commentId);
        $comment = $this->commentRepository->findById($commentId)
            ?? throw CommentNotFoundException::withId($commentId);

        if ($command->status === ModerationStatus::Published->value) {
            $comment->publishManually($command->reason, $this->clock->now());
        } else {
            $comment->rejectManually($command->reason, $this->clock->now());
        }

        $this->commentRepository->save($comment);

        return CommentView::fromComment($comment);
    }
}
