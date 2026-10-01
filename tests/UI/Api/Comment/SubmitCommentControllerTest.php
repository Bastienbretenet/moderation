<?php

declare(strict_types=1);

namespace SudOuest\Comment\Tests\UI\Api\Comment;

use DateTimeImmutable;
use SudOuest\Comment\Domain\Author\Author;
use SudOuest\Comment\Tests\Support\CreatesClientWithDatabase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class SubmitCommentControllerTest extends WebTestCase
{
    use CreatesClientWithDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClientWithDatabase();
    }

    public function testSubmittedCommentIsAcceptedAndPending(): void
    {
        $commentId = $this->submitComment([
            'publisher' => 'sudouest',
            'source' => 'article-123',
            'content' => 'Très bon article.',
            'authorId' => 'user-42',
        ]);

        $comment = $this->getComment($commentId);
        self::assertSame('sudouest', $comment['publisher']);
        self::assertSame('article-123', $comment['source']);
        self::assertSame('user-42', $comment['authorId']);
        self::assertSame('Très bon article.', $comment['content']);
        self::assertSame('pending', $comment['status']);
        self::assertNull($comment['moderatedAt']);
    }

    public function testSubmittedCommentWithoutAuthorIsAccepted(): void
    {
        $commentId = $this->submitComment([
            'publisher' => 'sudouest',
            'source' => 'article-123',
            'content' => 'Très bon article.',
        ]);

        $comment = $this->getComment($commentId);
        self::assertNull($comment['authorId']);
        self::assertSame('pending', $comment['status']);
    }

    public function testCommentFromBannedAuthorIsRejectedImmediately(): void
    {
        $bannedAuthor = new Author(Uuid::v7(), 'banned-user');
        $bannedAuthor->ban(new DateTimeImmutable());
        self::entityManager()->persist($bannedAuthor);
        self::entityManager()->flush();

        $commentId = $this->submitComment([
            'publisher' => 'sudouest',
            'source' => 'article-123',
            'content' => 'Très bon article.',
            'authorId' => 'banned-user',
        ]);

        $comment = $this->getComment($commentId);
        self::assertSame('rejected', $comment['status']);
        self::assertSame('author_banned', $comment['rejectionReason']);
        self::assertNull($comment['category']);
        self::assertNotNull($comment['moderatedAt']);
    }

    public function testInvalidCommentIsRejectedWithViolations(): void
    {
        $this->client->jsonRequest('POST', '/comments', [
            'publisher' => 'Sud Ouest',
            'source' => '',
            'content' => 'Très bon article.',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $violatedFields = array_column($this->decodeResponse()['violations'], 'field');
        self::assertEqualsCanonicalizing(['publisher', 'source'], $violatedFields);
    }

    /**
     * @param array<string, string> $payload
     */
    private function submitComment(array $payload): string
    {
        $this->client->jsonRequest('POST', '/comments', $payload);

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        $commentId = $this->decodeResponse()['id'];
        self::assertIsString($commentId);

        return $commentId;
    }

    /**
     * @return array<string, mixed>
     */
    private function getComment(string $commentId): array
    {
        $this->client->request('GET', '/comments/' . $commentId);

        self::assertResponseIsSuccessful();

        return $this->decodeResponse();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(): array
    {
        $decodedResponse = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decodedResponse);

        return $decodedResponse;
    }
}
