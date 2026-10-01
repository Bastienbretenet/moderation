<?php

declare(strict_types=1);

namespace SudOuest\Comment\Tests\UI\Api\Comment;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use SudOuest\Comment\Domain\Author\Author;
use SudOuest\Comment\Domain\Comment\Comment;
use SudOuest\Comment\Domain\Comment\IllegalContentCategory;
use SudOuest\Comment\Tests\Support\CreatesClientWithDatabase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class SearchCommentsControllerTest extends WebTestCase
{
    use CreatesClientWithDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClientWithDatabase();

        $firstAuthor = new Author(Uuid::v7(), 'user-1');
        $secondAuthor = new Author(Uuid::v7(), 'user-2');
        $entityManager = self::entityManager();
        $entityManager->persist($firstAuthor);
        $entityManager->persist($secondAuthor);

        $publishedComment = $this->submitComment('first', 'sudouest', 'article-1', $firstAuthor, '2026-10-01 08:00:00');
        $publishedComment->publish('RAS.', new DateTimeImmutable('2026-10-01 08:01:00'));
        $rejectedComment = $this->submitComment('second', 'sudouest', 'article-2', null, '2026-10-01 09:00:00');
        $rejectedComment->reject(IllegalContentCategory::Insult, 'Injure.', new DateTimeImmutable('2026-10-01 09:01:00'));
        $this->submitComment('third', 'sudouest', 'article-1', $secondAuthor, '2026-10-01 10:00:00');
        $otherPublisherComment = $this->submitComment('fourth', 'charentelibre', 'article-9', $firstAuthor, '2026-10-01 11:00:00');
        $otherPublisherComment->publish('RAS.', new DateTimeImmutable('2026-10-01 11:01:00'));
        $this->submitComment('fifth', 'charentelibre', 'article-9', null, '2026-10-01 11:00:00');

        $entityManager->flush();
    }

    public function testWithoutFilterReturnsAllCommentsNewestFirst(): void
    {
        $searchResult = $this->search([]);

        self::assertSame(5, $searchResult['total']);
        self::assertSame(1, $searchResult['page']);
        self::assertSame(20, $searchResult['limit']);
        self::assertSame(['fifth', 'fourth', 'third', 'second', 'first'], self::contents($searchResult));
    }

    /**
     * @return iterable<string, array{array<string, string>, list<string>}>
     */
    public static function filterProvider(): iterable
    {
        yield 'publisher' => [['publisher' => 'sudouest'], ['third', 'second', 'first']];
        yield 'status' => [['status' => 'published'], ['fourth', 'first']];
        yield 'source' => [['source' => 'article-9'], ['fifth', 'fourth']];
        yield 'author' => [['authorId' => 'user-1'], ['fourth', 'first']];
        yield 'publisher and status' => [['publisher' => 'sudouest', 'status' => 'pending'], ['third']];
        yield 'publisher, status and author' => [['publisher' => 'charentelibre', 'status' => 'published', 'authorId' => 'user-1'], ['fourth']];
        yield 'no match' => [['publisher' => 'sudouest', 'source' => 'article-9'], []];
    }

    /**
     * @param array<string, string> $filters
     * @param list<string> $expectedContents
     */
    #[DataProvider('filterProvider')]
    public function testFiltersNarrowTheResults(array $filters, array $expectedContents): void
    {
        $searchResult = $this->search($filters);

        self::assertSame($expectedContents, self::contents($searchResult));
        self::assertSame(count($expectedContents), $searchResult['total']);
    }

    public function testPaginationReturnsRequestedPageAndOverallTotal(): void
    {
        $searchResult = $this->search(['page' => '2', 'limit' => '2']);

        self::assertSame(['third', 'second'], self::contents($searchResult));
        self::assertSame(5, $searchResult['total']);
        self::assertSame(2, $searchResult['page']);
        self::assertSame(2, $searchResult['limit']);
    }

    public function testPageBeyondTheLastOneIsEmpty(): void
    {
        $searchResult = $this->search(['page' => '4', 'limit' => '2']);

        self::assertSame([], self::contents($searchResult));
        self::assertSame(5, $searchResult['total']);
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function invalidParametersProvider(): iterable
    {
        yield 'unknown status' => [['status' => 'deleted']];
        yield 'publisher is not a slug' => [['publisher' => 'Sud Ouest']];
        yield 'page is zero' => [['page' => '0']];
        yield 'page is not a number' => [['page' => 'two']];
        yield 'limit is zero' => [['limit' => '0']];
        yield 'limit is above maximum' => [['limit' => '101']];
    }

    /**
     * @param array<string, string> $parameters
     */
    #[DataProvider('invalidParametersProvider')]
    public function testInvalidParametersAreUnprocessable(array $parameters): void
    {
        $this->client->request('GET', '/comments', $parameters);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function submitComment(
        string $content,
        string $publisher,
        string $source,
        ?Author $author,
        string $submittedAt,
    ): Comment {
        $comment = Comment::submit(Uuid::v7(), $publisher, $source, $author, $content, new DateTimeImmutable($submittedAt));
        self::entityManager()->persist($comment);

        return $comment;
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return array<string, mixed>
     */
    private function search(array $parameters): array
    {
        $this->client->request('GET', '/comments', $parameters);

        self::assertResponseIsSuccessful();
        $searchResult = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($searchResult);

        return $searchResult;
    }

    /**
     * @param array<string, mixed> $searchResult
     *
     * @return list<string>
     */
    private static function contents(array $searchResult): array
    {
        self::assertIsArray($searchResult['items']);

        return array_map(
            static fn (array $item): string => $item['content'],
            $searchResult['items'],
        );
    }
}
