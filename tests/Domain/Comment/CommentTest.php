<?php

declare(strict_types=1);

namespace SudOuest\Comment\Tests\Domain\Comment;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SudOuest\Comment\Domain\Author\Author;
use SudOuest\Comment\Domain\Comment\Comment;
use SudOuest\Comment\Domain\Comment\CommentStatusChange;
use SudOuest\Comment\Domain\Comment\Exception\CommentAlreadyModeratedException;
use SudOuest\Comment\Domain\Comment\Exception\CommentStatusUnchangedException;
use SudOuest\Comment\Domain\Comment\IllegalContentCategory;
use SudOuest\Comment\Domain\Comment\ModerationStatus;
use SudOuest\Comment\Domain\Comment\RejectionReason;
use SudOuest\Comment\Domain\Comment\StatusChangeOrigin;
use SudOuest\Comment\Domain\Moderation\ModerationDecision;
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
        $submittedAt = new DateTimeImmutable('2026-10-01 10:00:00');

        $comment = self::submitComment($author, $submittedAt);

        self::assertSame(ModerationStatus::Pending, $comment->status());
        self::assertNull($comment->rejectionReason());
        self::assertNull($comment->moderatedAt());
        self::assertStatusHistory(
            [[null, ModerationStatus::Pending, StatusChangeOrigin::Submission, null, $submittedAt]],
            $comment,
        );
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
        self::assertStatusHistory(
            [[null, ModerationStatus::Rejected, StatusChangeOrigin::AuthorBan, null, $submittedAt]],
            $comment,
        );
    }

    public function testRejectionDecisionMarksCommentAsIllegalContent(): void
    {
        $moderatedAt = new DateTimeImmutable('2026-10-01 10:05:00');
        $comment = self::submitComment();

        $comment->applyModerationDecision(
            ModerationDecision::reject(IllegalContentCategory::HateSpeech, 'Provocation à la haine.'),
            $moderatedAt,
        );

        self::assertSame(ModerationStatus::Rejected, $comment->status());
        self::assertSame(RejectionReason::IllegalContent, $comment->rejectionReason());
        self::assertSame(IllegalContentCategory::HateSpeech, $comment->category());
        self::assertSame('Provocation à la haine.', $comment->moderationExplanation());
        self::assertEquals($moderatedAt, $comment->moderatedAt());
        self::assertSame(StatusChangeOrigin::Llm, $comment->statusHistory()[1]->origin());
    }

    public function testApprovalDecisionMarksCommentAsPublished(): void
    {
        $moderatedAt = new DateTimeImmutable('2026-10-01 10:05:00');
        $comment = self::submitComment();

        $comment->applyModerationDecision(ModerationDecision::approve('Aucun contenu illicite.'), $moderatedAt);

        self::assertSame(ModerationStatus::Published, $comment->status());
        self::assertNull($comment->rejectionReason());
        self::assertNull($comment->category());
        self::assertSame('Aucun contenu illicite.', $comment->moderationExplanation());
        self::assertEquals($moderatedAt, $comment->moderatedAt());
    }

    public function testModerationDecisionOnAlreadyModeratedCommentThrows(): void
    {
        $comment = self::submitComment();
        $comment->applyModerationDecision(ModerationDecision::approve('Aucun contenu illicite.'), new DateTimeImmutable());

        $this->expectException(CommentAlreadyModeratedException::class);

        $comment->applyModerationDecision(
            ModerationDecision::reject(IllegalContentCategory::Insult, 'Injure.'),
            new DateTimeImmutable(),
        );
    }

    public function testOperatorCanPublishARejectedComment(): void
    {
        $submittedAt = new DateTimeImmutable('2026-10-01 10:00:00');
        $moderatedAt = new DateTimeImmutable('2026-10-01 10:05:00');
        $manuallyModeratedAt = new DateTimeImmutable('2026-10-01 14:00:00');
        $comment = self::submitComment(null, $submittedAt);
        $comment->applyModerationDecision(ModerationDecision::reject(IllegalContentCategory::Insult, 'Injure.'), $moderatedAt);

        $comment->publishManually('Critique légitime.', $manuallyModeratedAt);

        self::assertSame(ModerationStatus::Published, $comment->status());
        self::assertNull($comment->rejectionReason());
        self::assertNull($comment->category());
        self::assertSame('Critique légitime.', $comment->moderationExplanation());
        self::assertEquals($manuallyModeratedAt, $comment->moderatedAt());
        self::assertStatusHistory(
            [
                [null, ModerationStatus::Pending, StatusChangeOrigin::Submission, null, $submittedAt],
                [ModerationStatus::Pending, ModerationStatus::Rejected, StatusChangeOrigin::Llm, 'Injure.', $moderatedAt],
                [ModerationStatus::Rejected, ModerationStatus::Published, StatusChangeOrigin::Operator, 'Critique légitime.', $manuallyModeratedAt],
            ],
            $comment,
        );
    }

    public function testOperatorCanRejectAPendingCommentWithoutReason(): void
    {
        $manuallyModeratedAt = new DateTimeImmutable('2026-10-01 10:01:00');
        $comment = self::submitComment();

        $comment->rejectManually(null, $manuallyModeratedAt);

        self::assertSame(ModerationStatus::Rejected, $comment->status());
        self::assertSame(RejectionReason::Operator, $comment->rejectionReason());
        self::assertNull($comment->category());
        self::assertNull($comment->moderationExplanation());
        self::assertSame(StatusChangeOrigin::Operator, $comment->statusHistory()[1]->origin());
        self::assertNull($comment->statusHistory()[1]->reason());
    }

    public function testOperatorDecisionMakesLaterModerationDecisionFail(): void
    {
        $comment = self::submitComment();
        $comment->publishManually(null, new DateTimeImmutable());

        $this->expectException(CommentAlreadyModeratedException::class);

        $comment->applyModerationDecision(ModerationDecision::approve('Aucun contenu illicite.'), new DateTimeImmutable());
    }

    public function testManualModerationToCurrentStatusThrows(): void
    {
        $comment = self::submitComment();
        $comment->rejectManually('Spam.', new DateTimeImmutable());

        $this->expectException(CommentStatusUnchangedException::class);

        $comment->rejectManually('Spam.', new DateTimeImmutable());
    }

    /**
     * @param list<array{?ModerationStatus, ModerationStatus, StatusChangeOrigin, ?string, DateTimeImmutable}> $expectedChanges
     */
    private static function assertStatusHistory(array $expectedChanges, Comment $comment): void
    {
        self::assertSame(
            array_map(
                static fn (array $expectedChange): array => [
                    $expectedChange[0],
                    $expectedChange[1],
                    $expectedChange[2],
                    $expectedChange[3],
                    $expectedChange[4]->format(DATE_ATOM),
                ],
                $expectedChanges,
            ),
            array_map(
                static fn (CommentStatusChange $statusChange): array => [
                    $statusChange->previousStatus(),
                    $statusChange->newStatus(),
                    $statusChange->origin(),
                    $statusChange->reason(),
                    $statusChange->changedAt()->format(DATE_ATOM),
                ],
                $comment->statusHistory(),
            ),
        );
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
