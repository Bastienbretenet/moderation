<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Command\UnbanAuthor;

use Psr\Clock\ClockInterface;
use SudOuest\Comment\Application\Command\ModerateComment\ModerateCommentCommand;
use SudOuest\Comment\Application\Query\AuthorView;
use SudOuest\Comment\Domain\Author\AuthorRepository;
use SudOuest\Comment\Domain\Author\Exception\AuthorNotFoundException;
use SudOuest\Comment\Domain\Comment\CommentRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
final readonly class UnbanAuthorHandler
{
    public function __construct(
        private AuthorRepository $authorRepository,
        private CommentRepository $commentRepository,
        private ValidatorInterface $validator,
        private ClockInterface $clock,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(UnbanAuthorCommand $command): UnbannedAuthorView
    {
        $violations = $this->validator->validate($command);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($command, $violations);
        }

        $author = $this->authorRepository->findByExternalId($command->authorId)
            ?? throw AuthorNotFoundException::withExternalId($command->authorId);
        $author->unban();
        $this->authorRepository->save($author);

        $commentsToResubmit = $this->commentRepository->findRejectedBecauseAuthorBanned($author);
        foreach ($commentsToResubmit as $comment) {
            $comment->resubmitForModeration($this->clock->now());
            $this->commentRepository->save($comment);

            $this->messageBus->dispatch(
                new ModerateCommentCommand($comment->id()->toRfc4122()),
                [new DispatchAfterCurrentBusStamp()],
            );
        }

        return new UnbannedAuthorView(AuthorView::fromAuthor($author), count($commentsToResubmit));
    }
}
