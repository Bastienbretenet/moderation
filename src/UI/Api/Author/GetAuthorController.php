<?php

declare(strict_types=1);

namespace SudOuest\Comment\UI\Api\Author;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use SudOuest\Comment\Application\Query\GetAuthor\GetAuthorQuery;
use SudOuest\Comment\Application\Query\AuthorView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetAuthorController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/authors/{authorId}', methods: ['GET'])]
    #[OA\Tag(name: 'Authors')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Author ban status.',
        content: new OA\JsonContent(ref: new Model(type: AuthorView::class)),
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Author not found.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid author identifier.')]
    public function __invoke(string $authorId): JsonResponse
    {
        return new JsonResponse($this->handle(new GetAuthorQuery($authorId)));
    }
}
