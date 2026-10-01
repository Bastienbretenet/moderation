<?php

declare(strict_types=1);

namespace SudOuest\Comment\UI\Api\Comment;

use OpenApi\Attributes as OA;
use SudOuest\Comment\Application\Command\SubmitComment\SubmitCommentCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class SubmitCommentController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/comments', methods: ['POST'])]
    #[OA\Tag(name: 'Comments')]
    #[OA\Response(
        response: Response::HTTP_ACCEPTED,
        description: 'Comment accepted, moderation pending.',
        content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]),
    )]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid comment.')]
    public function __invoke(#[MapRequestPayload] SubmitCommentDto $submitCommentDto): JsonResponse
    {
        $commentId = $this->handle(new SubmitCommentCommand(
            $submitCommentDto->publisher,
            $submitCommentDto->source,
            $submitCommentDto->content,
            $submitCommentDto->authorId,
        ));

        return new JsonResponse(['id' => $commentId], Response::HTTP_ACCEPTED);
    }
}
