<?php

declare(strict_types=1);

namespace SudOuest\Comment\Tests\Domain\Comment;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SudOuest\Comment\Domain\Author\Author;
use SudOuest\Comment\Domain\Comment\Comment;
use SudOuest\Comment\Domain\Comment\Exception\CommentAlreadyModeratedException;
use SudOuest\Comment\Domain\Comment\IllegalContentCategory;
use SudOuest\Comment\Domain\Comment\ModerationStatus;
use SudOuest\Comment\Domain\Comment\RejectionReason;
use Symfony\Component\Uid\Uuid;

final class CommentTest extends TestCase
{
    /**
     * @return iterable<string, array{?Author}>
     */
    public static function notBannedAuthorProvider(): iterable
    {
        yield 'without author' => [null];
        yield 'with not banned author' => [new Author(Uuid::v7(), 'user-42')];
    }

    #[DataProvider('notBannedAuthorProvider')]
    public function testSubmitByNotBannedAuthorIsPending(?Author $author): void
    {
        $comment = self::submitComment($author);

        self::assertSame(ModerationStatus::Pending, $comment->status());
        self::assertNull($comment->rejectionReason());
        self::assertNull($comment->moderatedAt());
    }

    public function testSubmitByBannedAuthorIsRejectedImmediately(): void
    {
        $submittedAt = new DateTimeImmutable('2026-10-01 10:00:00');
        $bannedAuthor = new Author(Uuid::v7(), 'user-42');
        $bannedAuthor->ban(new DateTimeImmutable('2026-09-01 10:00:00'));

        $comment = self::submitComment($bannedAuthor, $submittedAt);

        self::assertSame(ModerationStatus::Rejected, $comment->status());
        self::assertSame(RejectionReason::AuthorBanned, $comment->rejectionReason());
        self::assertNull($comment->category());
        self::assertEquals($submittedAt, $comment->moderatedAt());
    }

    public function testRejectMarksCommentAsIllegalContent(): void
    {
        $moderatedAt = new DateTimeImmutable('2026-10-01 10:05:00');
        $comment = self::submitComment();

        $comment->reject(IllegalContentCategory::HateSpeech, 'Provocation à la haine raciale.', $moderatedAt);

        self::assertSame(ModerationStatus::Rejected, $comment->status());
        self::assertSame(RejectionReason::IllegalContent, $comment->rejectionReason());
        self::assertSame(IllegalContentCategory::HateSpeech, $comment->category());
        self::assertSame('Provocation à la haine raciale.', $comment->moderationExplanation());
        self::assertEquals($moderatedAt, $comment->moderatedAt());
    }

    public function testPublishMarksCommentAsPublished(): void
    {
        $moderatedAt = new DateTimeImmutable('2026-10-01 10:05:00');
        $comment = self::submitComment();

        $comment->publish('Aucun contenu illicite.', $moderatedAt);

        self::assertSame(ModerationStatus::Published, $comment->status());
        self::assertNull($comment->rejectionReason());
        self::assertNull($comment->category());
        self::assertSame('Aucun contenu illicite.', $comment->moderationExplanation());
        self::assertEquals($moderatedAt, $comment->moderatedAt());
    }

    public function testPublishAlreadyModeratedCommentThrows(): void
    {
        $comment = self::submitComment();
        $comment->publish('Aucun contenu illicite.', new DateTimeImmutable());

        $this->expectException(CommentAlreadyModeratedException::class);

        $comment->publish('Aucun contenu illicite.', new DateTimeImmutable());
    }

    public function testRejectAlreadyModeratedCommentThrows(): void
    {
        $comment = self::submitComment();
        $comment->publish('Aucun contenu illicite.', new DateTimeImmutable());

        $this->expectException(CommentAlreadyModeratedException::class);

        $comment->reject(IllegalContentCategory::Insult, 'Injure.', new DateTimeImmutable());
    }

    private static function submitComment(
        ?Author $author = null,
        DateTimeImmutable $submittedAt = new DateTimeImmutable('2026-10-01 10:00:00'),
    ): Comment {
        return Comment::submit(
            Uuid::v7(),
            'sudouest',
            'article-123',
            $author,
            'Très bon article.',
            $submittedAt,
        );
    }
}
