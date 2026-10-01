<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Query\GetAuthor;

use SudOuest\Comment\Application\Query\AuthorView;
use SudOuest\Comment\Domain\Author\AuthorRepository;
use SudOuest\Comment\Domain\Author\Exception\AuthorNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
final readonly class GetAuthorHandler
{
    public function __construct(
        private AuthorRepository $authorRepository,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(GetAuthorQuery $query): AuthorView
    {
        $violations = $this->validator->validate($query);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($query, $violations);
        }

        $author = $this->authorRepository->findByExternalId($query->authorId)
            ?? throw AuthorNotFoundException::withExternalId($query->authorId);

        return AuthorView::fromAuthor($author);
    }
}
