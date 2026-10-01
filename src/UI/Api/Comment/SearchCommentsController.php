<?php

declare(strict_types=1);

namespace SudOuest\Comment\UI\Api\Comment;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use SudOuest\Comment\Application\Query\SearchComments\CommentSearchView;
use SudOuest\Comment\Application\Query\SearchComments\SearchCommentsQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class SearchCommentsController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/comments', methods: ['GET'])]
    #[OA\Tag(name: 'Comments')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Comments matching the filters, newest first.',
        content: new OA\JsonContent(ref: new Model(type: CommentSearchView::class)),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid search parameters.')]
    public function __invoke(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        SearchCommentsParams $searchCommentsParams = new SearchCommentsParams(),
    ): JsonResponse {
        return new JsonResponse($this->handle(new SearchCommentsQuery(
            $searchCommentsParams->publisher,
            $searchCommentsParams->status,
            $searchCommentsParams->source,
            $searchCommentsParams->authorId,
            $searchCommentsParams->page,
            $searchCommentsParams->limit,
        )));
    }
}
