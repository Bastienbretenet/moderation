<?php

declare(strict_types=1);

namespace SudOuest\Comment\UI\Api\Comment;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use SudOuest\Comment\Application\Query\GetComment\CommentView;
use SudOuest\Comment\Application\Query\GetComment\GetCommentQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetCommentController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/comments/{commentId}', methods: ['GET'])]
    #[OA\Tag(name: 'Comments')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Comment detail.',
        content: new OA\JsonContent(ref: new Model(type: CommentView::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Comment not found.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid comment identifier.')]
    public function __invoke(string $commentId): JsonResponse
    {
        return new JsonResponse($this->handle(new GetCommentQuery($commentId)));
    }
}
