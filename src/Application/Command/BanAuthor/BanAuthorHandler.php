<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Command\BanAuthor;

use Psr\Clock\ClockInterface;
use SudOuest\Comment\Application\Query\AuthorView;
use SudOuest\Comment\Domain\Author\AuthorRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
final readonly class BanAuthorHandler
{
    public function __construct(
        private AuthorRepository $authorRepository,
        private ValidatorInterface $validator,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(BanAuthorCommand $command): AuthorView
    {
        $violations = $this->validator->validate($command);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($command, $violations);
        }

        $author = $this->authorRepository->findOrCreateByExternalId($command->authorId);
        $author->ban($this->clock->now());

        $this->authorRepository->save($author);

        return AuthorView::fromAuthor($author);
    }
}
