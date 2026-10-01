<?php

declare(strict_types=1);

namespace SudOuest\Comment\Tests\UI\Api\Author;

use SudOuest\Comment\Tests\Support\CreatesClientWithDatabase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AuthorBanControllerTest extends WebTestCase
{
    use CreatesClientWithDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClientWithDatabase();
    }

    public function testUnknownAuthorCanBeBannedBeforeCommenting(): void
    {
        $this->client->request('POST', '/authors/troll-42/ban');

        self::assertResponseIsSuccessful();
        $author = $this->decodeResponse();
        self::assertSame('troll-42', $author['authorId']);
        self::assertTrue($author['banned']);
        self::assertNotNull($author['bannedAt']);

        $this->client->request('GET', '/authors/troll-42');

        self::assertResponseIsSuccessful();
        self::assertTrue($this->decodeResponse()['banned']);
    }

    public function testUnbanResubmitsOnlyCommentsRejectedBecauseOfTheBan(): void
    {
        $illegalCommentId = $this->submitComment('Commentaire illicite [moderation:insult]');
        $manuallyRejectedCommentId = $this->submitComment('Commentaire hors sujet.');
        $this->client->jsonRequest('PATCH', '/comments/' . $manuallyRejectedCommentId . '/status', ['status' => 'rejected']);
        self::assertResponseIsSuccessful();

        $this->client->request('POST', '/authors/user-42/ban');
        self::assertResponseIsSuccessful();
        $commentDuringBanId = $this->submitComment('Commentaire pendant le ban.');
        self::assertSame('author_banned', $this->getComment($commentDuringBanId)['rejectionReason']);

        $this->client->request('POST', '/authors/user-42/unban');

        self::assertResponseIsSuccessful();
        $unbannedAuthor = $this->decodeResponse();
        self::assertSame(1, $unbannedAuthor['resubmittedCommentCount']);
        self::assertIsArray($unbannedAuthor['author']);
        self::assertFalse($unbannedAuthor['author']['banned']);

        self::assertSame('published', $this->getComment($commentDuringBanId)['status']);
        self::assertSame('illegal_content', $this->getComment($illegalCommentId)['rejectionReason']);
        self::assertSame('operator', $this->getComment($manuallyRejectedCommentId)['rejectionReason']);

        $this->client->request('GET', '/comments/' . $commentDuringBanId . '/status-history');
        self::assertSame(
            ['author_ban', 'author_unban', 'llm'],
            array_column($this->decodeResponse(), 'origin'),
        );
    }

    public function testBanningBannedAuthorIsAConflict(): void
    {
        $this->client->request('POST', '/authors/user-42/ban');
        $this->client->request('POST', '/authors/user-42/ban');

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testUnbanningNotBannedAuthorIsAConflict(): void
    {
        $this->submitComment('Très bon article.');

        $this->client->request('POST', '/authors/user-42/unban');

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testUnbanningUnknownAuthorIsNotFound(): void
    {
        $this->client->request('POST', '/authors/unknown-user/unban');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUnknownAuthorIsNotFound(): void
    {
        $this->client->request('GET', '/authors/unknown-user');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testTooLongAuthorIdIsUnprocessable(): void
    {
        $this->client->request('POST', '/authors/' . str_repeat('a', 256) . '/ban');

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function submitComment(string $content): string
    {
        $this->client->jsonRequest('POST', '/comments', [
            'publisher' => 'sudouest',
            'source' => 'article-123',
            'content' => $content,
            'authorId' => 'user-42',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        $commentId = $this->decodeResponse()['id'];
        self::assertIsString($commentId);

        return $commentId;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function getComment(string $commentId): array
    {
        $this->client->request('GET', '/comments/' . $commentId);

        self::assertResponseIsSuccessful();

        return $this->decodeResponse();
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodeResponse(): array
    {
        $decodedResponse = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decodedResponse);

        return $decodedResponse;
    }
}
