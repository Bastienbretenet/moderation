<?php

declare(strict_types=1);

namespace SudOuest\Comment\UI\Api\Comment;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use SudOuest\Comment\Application\Command\ModerateCommentManually\ModerateCommentManuallyCommand;
use SudOuest\Comment\Application\Query\CommentView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class ModerateCommentManuallyController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/comments/{commentId}/status', methods: ['PATCH'])]
    #[OA\Tag(name: 'Comments')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Comment manually moderated by an operator.',
        content: new OA\JsonContent(ref: new Model(type: CommentView::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Comment not found.')]
    #[OA\Response(response: Response::HTTP_CONFLICT, description: 'Comment already has this status, or was modified concurrently.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid status or reason.')]
    public function __invoke(string $commentId, #[MapRequestPayload] ModerateCommentManuallyDto $moderateCommentManuallyDto): JsonResponse
    {
        return new JsonResponse($this->handle(new ModerateCommentManuallyCommand(
            $commentId,
            $moderateCommentManuallyDto->status,
            $moderateCommentManuallyDto->reason,
        )));
    }
}
