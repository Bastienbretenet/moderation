<?php

declare(strict_types=1);

namespace SudOuest\Comment\Tests\UI\Api\Comment;

use PHPUnit\Framework\Attributes\DataProvider;
use SudOuest\Comment\Tests\Support\CreatesClientWithDatabase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ModerateCommentManuallyControllerTest extends WebTestCase
{
    use CreatesClientWithDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClientWithDatabase();
    }

    public function testOperatorPublishesARejectedCommentAndChangeIsHistorized(): void
    {
        $commentId = $this->submitComment('Ce maire est un incapable [moderation:insult]');

        $this->client->jsonRequest('PATCH', '/comments/' . $commentId . '/status', [
            'status' => 'published',
            'reason' => 'Critique politique légitime.',
        ]);

        self::assertResponseIsSuccessful();
        $comment = $this->decodeResponse();
        self::assertSame('published', $comment['status']);
        self::assertNull($comment['rejectionReason']);
        self::assertNull($comment['category']);
        self::assertSame('Critique politique légitime.', $comment['moderationExplanation']);

        $this->client->request('GET', '/comments/' . $commentId . '/status-history');

        self::assertResponseIsSuccessful();
        $statusHistory = array_map(
            static fn (array $statusChange): array => [
                $statusChange['previousStatus'],
                $statusChange['newStatus'],
                $statusChange['origin'],
            ],
            $this->decodeResponse(),
        );
        self::assertSame(
            [
                [null, 'pending', 'submission'],
                ['pending', 'rejected', 'llm'],
                ['rejected', 'published', 'operator'],
            ],
            $statusHistory,
        );
        self::assertSame('Critique politique légitime.', $this->decodeResponse()[2]['reason']);
    }

    public function testOperatorRejectsAPublishedCommentWithoutReason(): void
    {
        $commentId = $this->submitComment('Très bon article.');

        $this->client->jsonRequest('PATCH', '/comments/' . $commentId . '/status', ['status' => 'rejected']);

        self::assertResponseIsSuccessful();
        $comment = $this->decodeResponse();
        self::assertSame('rejected', $comment['status']);
        self::assertSame('operator', $comment['rejectionReason']);
        self::assertNull($comment['moderationExplanation']);
    }

    public function testManualModerationToCurrentStatusIsAConflict(): void
    {
        $commentId = $this->submitComment('Très bon article.');

        $this->client->jsonRequest('PATCH', '/comments/' . $commentId . '/status', ['status' => 'published']);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function invalidPayloadProvider(): iterable
    {
        yield 'pending is not a manual moderation status' => [['status' => 'pending']];
        yield 'unknown status' => [['status' => 'deleted']];
        yield 'blank reason' => [['status' => 'rejected', 'reason' => '']];
        yield 'missing status' => [['reason' => 'Spam.']];
    }

    /**
     * @param array<string, string> $payload
     */
    #[DataProvider('invalidPayloadProvider')]
    public function testInvalidPayloadIsUnprocessable(array $payload): void
    {
        $commentId = $this->submitComment('Très bon article.');

        $this->client->jsonRequest('PATCH', '/comments/' . $commentId . '/status', $payload);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUnknownCommentIsNotFound(): void
    {
        $this->client->jsonRequest('PATCH', '/comments/' . Uuid::v7()->toRfc4122() . '/status', ['status' => 'rejected']);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testStatusHistoryOfUnknownCommentIsNotFound(): void
    {
        $this->client->request('GET', '/comments/' . Uuid::v7()->toRfc4122() . '/status-history');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function submitComment(string $content): string
    {
        $this->client->jsonRequest('POST', '/comments', [
            'publisher' => 'sudouest',
            'source' => 'article-123',
            'content' => $content,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        $commentId = $this->decodeResponse()['id'];
        self::assertIsString($commentId);

        return $commentId;
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
