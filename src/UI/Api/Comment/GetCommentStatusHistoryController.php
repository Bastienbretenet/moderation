<?php

declare(strict_types=1);

namespace SudOuest\Comment\UI\Api\Comment;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use SudOuest\Comment\Application\Query\GetCommentStatusHistory\GetCommentStatusHistoryQuery;
use SudOuest\Comment\Application\Query\GetCommentStatusHistory\StatusChangeView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetCommentStatusHistoryController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/comments/{commentId}/status-history', methods: ['GET'])]
    #[OA\Tag(name: 'Comments')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Every status change of the comment, oldest first.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: StatusChangeView::class))),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Comment not found.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid comment identifier.')]
    public function __invoke(string $commentId): JsonResponse
    {
        return new JsonResponse($this->handle(new GetCommentStatusHistoryQuery($commentId)));
    }
}
