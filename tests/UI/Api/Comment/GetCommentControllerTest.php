<?php

declare(strict_types=1);

namespace SudOuest\Comment\Tests\UI\Api\Comment;

use SudOuest\Comment\Tests\Support\CreatesClientWithDatabase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class GetCommentControllerTest extends WebTestCase
{
    use CreatesClientWithDatabase;

    public function testUnknownCommentReturnsNotFound(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/comments/' . Uuid::v7()->toRfc4122());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testInvalidCommentIdReturnsUnprocessableEntity(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/comments/not-a-uuid');

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
