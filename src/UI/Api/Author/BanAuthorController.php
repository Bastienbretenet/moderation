<?php

declare(strict_types=1);

namespace SudOuest\Comment\UI\Api\Author;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use SudOuest\Comment\Application\Command\BanAuthor\BanAuthorCommand;
use SudOuest\Comment\Application\Query\AuthorView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class BanAuthorController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/authors/{authorId}/ban', methods: ['POST'])]
    #[OA\Tag(name: 'Authors')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Author banned: their new comments are rejected without moderation.',
        content: new OA\JsonContent(ref: new Model(type: AuthorView::class)),
    )]
    #[OA\Response(response: Response::HTTP_CONFLICT, description: 'Author already banned.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid author identifier.')]
    public function __invoke(string $authorId): JsonResponse
    {
        return new JsonResponse($this->handle(new BanAuthorCommand($authorId)));
    }
}
