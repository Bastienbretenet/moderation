<?php

declare(strict_types=1);

namespace SudOuest\Comment\Tests\Domain\Author;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SudOuest\Comment\Domain\Author\Author;
use SudOuest\Comment\Domain\Author\Exception\AuthorBanUnchangedException;
use Symfony\Component\Uid\Uuid;

final class AuthorTest extends TestCase
{
    public function testBanMarksAuthorAsBanned(): void
    {
        $bannedAt = new DateTimeImmutable('2026-10-01 10:00:00');
        $author = new Author(Uuid::v7(), 'user-42');

        $author->ban($bannedAt);

        self::assertTrue($author->isBanned());
        self::assertEquals($bannedAt, $author->bannedAt());
    }

    public function testUnbanLiftsTheBan(): void
    {
        $author = new Author(Uuid::v7(), 'user-42');
        $author->ban(new DateTimeImmutable());

        $author->unban();

        self::assertFalse($author->isBanned());
        self::assertNull($author->bannedAt());
    }

    public function testBanningBannedAuthorThrows(): void
    {
        $author = new Author(Uuid::v7(), 'user-42');
        $author->ban(new DateTimeImmutable());

        $this->expectException(AuthorBanUnchangedException::class);

        $author->ban(new DateTimeImmutable());
    }

    public function testUnbanningNotBannedAuthorThrows(): void
    {
        $author = new Author(Uuid::v7(), 'user-42');

        $this->expectException(AuthorBanUnchangedException::class);

        $author->unban();
    }
}
