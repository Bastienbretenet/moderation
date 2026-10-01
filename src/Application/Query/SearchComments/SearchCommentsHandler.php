<?php

declare(strict_types=1);

namespace SudOuest\Comment\Application\Query\SearchComments;

use SudOuest\Comment\Application\Query\CommentView;
use SudOuest\Comment\Domain\Comment\CommentRepository;
use SudOuest\Comment\Domain\Comment\CommentSearchCriteria;
use SudOuest\Comment\Domain\Comment\ModerationStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
final readonly class SearchCommentsHandler
{
    public function __construct(
        private CommentRepository $commentRepository,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(SearchCommentsQuery $query): CommentSearchView
    {
        $violations = $this->validator->validate($query);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($query, $violations);
        }

        $searchResult = $this->commentRepository->search(new CommentSearchCriteria(
            $query->publisher,
            $query->status !== null ? ModerationStatus::from($query->status) : null,
            $query->source,
            $query->authorId,
            $query->page,
            $query->limit,
        ));

        return new CommentSearchView(
            array_map(CommentView::fromComment(...), $searchResult->comments),
            $searchResult->total,
            $query->page,
            $query->limit,
        );
    }
}
