<?php

declare(strict_types=1);

namespace SudOuest\Comment\Tests\Application\Command\ModerateComment;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use SudOuest\Comment\Application\Command\ModerateComment\ModerateCommentCommand;
use SudOuest\Comment\Application\Command\ModerateComment\ModerateCommentHandler;
use SudOuest\Comment\Domain\Comment\Comment;
use SudOuest\Comment\Domain\Comment\IllegalContentCategory;
use SudOuest\Comment\Domain\Comment\ModerationStatus;
use SudOuest\Comment\Domain\Moderation\Exception\ModerationFailedException;
use SudOuest\Comment\Domain\Moderation\Exception\ModerationUnavailableException;
use SudOuest\Comment\Domain\Moderation\ModerationDecision;
use SudOuest\Comment\Domain\Moderation\Moderator;
use SudOuest\Comment\Infrastructure\Persistence\Doctrine\DoctrineCommentRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validation;

final class ModerateCommentHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineCommentRepository $commentRepository;

    protected function setUp(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        (new SchemaTool($entityManager))->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $this->entityManager = $entityManager;
        $this->commentRepository = new DoctrineCommentRepository($entityManager);
    }

    public function testRejectionDecisionIsApplied(): void
    {
        $comment = $this->persistPendingComment();
        $moderator = $this->createStub(Moderator::class);
        $moderator->method('moderate')->willReturn(
            ModerationDecision::reject(IllegalContentCategory::Insult, 'Injure envers un tiers.'),
        );

        $this->handle($moderator, $comment);

        $moderatedComment = $this->reloadComment($comment);
        self::assertSame(ModerationStatus::Rejected, $moderatedComment->status());
        self::assertSame(IllegalContentCategory::Insult, $moderatedComment->category());
        self::assertSame('Injure envers un tiers.', $moderatedComment->moderationExplanation());
    }

    public function testAlreadyModeratedCommentIsNotModeratedAgain(): void
    {
        $comment = $this->persistPendingComment();
        $comment->applyModerationDecision(ModerationDecision::approve('Aucun contenu illicite.'), new DateTimeImmutable());
        $this->entityManager->flush();
        $moderator = $this->createMock(Moderator::class);
        $moderator->expects(self::never())->method('moderate');

        $this->handle($moderator, $comment);

        self::assertSame(ModerationStatus::Published, $this->reloadComment($comment)->status());
    }

    public function testUnavailableModerationLeavesCommentPending(): void
    {
        $comment = $this->persistPendingComment();
        $moderator = $this->createStub(Moderator::class);
        $moderator->method('moderate')->willThrowException(ModerationUnavailableException::because('timeout'));

        try {
            $this->handle($moderator, $comment);
            self::fail('The moderation failure must be propagated so the message is retried.');
        } catch (ModerationUnavailableException) {
        }

        self::assertSame(ModerationStatus::Pending, $this->reloadComment($comment)->status());
    }

    public function testPermanentModerationFailureIsNotRetried(): void
    {
        $comment = $this->persistPendingComment();
        $moderator = $this->createStub(Moderator::class);
        $moderator->method('moderate')->willThrowException(ModerationFailedException::because('invalid response'));

        try {
            $this->handle($moderator, $comment);
            self::fail('A permanent moderation failure must not be retried.');
        } catch (UnrecoverableMessageHandlingException) {
        }

        self::assertSame(ModerationStatus::Pending, $this->reloadComment($comment)->status());
    }

    public function testUnknownCommentIsNotRetried(): void
    {
        $handler = $this->createHandler($this->createStub(Moderator::class));

        $this->expectException(UnrecoverableMessageHandlingException::class);

        $handler(new ModerateCommentCommand(Uuid::v7()->toRfc4122()));
    }

    private function persistPendingComment(): Comment
    {
        $comment = Comment::submit(
            Uuid::v7(),
            'sudouest',
            'article-123',
            null,
            'Très bon article.',
            new DateTimeImmutable(),
        );
        $this->commentRepository->save($comment);

        return $comment;
    }

    private function handle(Moderator $moderator, Comment $comment): void
    {
        $handler = $this->createHandler($moderator);

        $handler(new ModerateCommentCommand($comment->id()->toRfc4122()));
    }

    private function createHandler(Moderator $moderator): ModerateCommentHandler
    {
        return new ModerateCommentHandler(
            $this->commentRepository,
            $moderator,
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
            new MockClock(),
        );
    }

    private function reloadComment(Comment $comment): Comment
    {
        $this->entityManager->clear();
        $reloadedComment = $this->commentRepository->findById($comment->id());
        self::assertNotNull($reloadedComment);

        return $reloadedComment;
    }
}
