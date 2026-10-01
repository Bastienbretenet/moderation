<?php

declare(strict_types=1);

namespace SudOuest\Comment\Infrastructure\Persistence\Fixture;

use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use SudOuest\Comment\Domain\Author\Author;
use SudOuest\Comment\Domain\Comment\Comment;
use SudOuest\Comment\Domain\Comment\IllegalContentCategory;
use SudOuest\Comment\Domain\Moderation\ModerationDecision;
use Symfony\Component\Uid\Uuid;

final class DemoFixtures implements ORMFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $bannedAuthor = new Author(Uuid::v7(), 'banned-user');
        $bannedAuthor->ban(new DateTimeImmutable('2026-09-01 12:00:00'));
        $regularAuthor = new Author(Uuid::v7(), 'user-1');
        $otherRegularAuthor = new Author(Uuid::v7(), 'user-2');

        $publishedComment = Comment::submit(
            Uuid::v7(),
            'sudouest',
            'article-123',
            $regularAuthor,
            'Article très complet, merci pour ce travail.',
            new DateTimeImmutable('2026-09-30 08:00:00'),
        );
        $publishedComment->applyModerationDecision(
            ModerationDecision::approve('Avis sur l\'article, aucun contenu illicite.'),
            new DateTimeImmutable('2026-09-30 08:00:05'),
        );

        $illegalContentComment = Comment::submit(
            Uuid::v7(),
            'sudouest',
            'article-123',
            $otherRegularAuthor,
            'Commentaire injurieux envers un autre lecteur.',
            new DateTimeImmutable('2026-09-30 09:00:00'),
        );
        $illegalContentComment->applyModerationDecision(
            ModerationDecision::reject(IllegalContentCategory::Insult, 'Expression outrageante visant une personne identifiable.'),
            new DateTimeImmutable('2026-09-30 09:00:05'),
        );

        $overruledComment = Comment::submit(
            Uuid::v7(),
            'sudouest',
            'article-456',
            $regularAuthor,
            'Ce maire est un incapable, il faut voter contre lui.',
            new DateTimeImmutable('2026-09-30 09:30:00'),
        );
        $overruledComment->applyModerationDecision(
            ModerationDecision::reject(IllegalContentCategory::Insult, 'Terme méprisant visant un élu.'),
            new DateTimeImmutable('2026-09-30 09:30:05'),
        );
        $overruledComment->publishManually(
            'Critique politique d\'un élu, rejet automatique abusif.',
            new DateTimeImmutable('2026-09-30 14:00:00'),
        );

        $bannedAuthorComment = Comment::submit(
            Uuid::v7(),
            'sudouest',
            'article-456',
            $bannedAuthor,
            'Nouveau commentaire d\'un utilisateur banni.',
            new DateTimeImmutable('2026-09-30 10:00:00'),
        );

        $pendingComment = Comment::submit(
            Uuid::v7(),
            'charentelibre',
            'article-789',
            null,
            'Commentaire anonyme en attente de modération.',
            new DateTimeImmutable('2026-09-30 11:00:00'),
        );

        foreach ([$bannedAuthor, $regularAuthor, $otherRegularAuthor] as $author) {
            $manager->persist($author);
        }

        foreach ([$publishedComment, $illegalContentComment, $overruledComment, $bannedAuthorComment, $pendingComment] as $comment) {
            $manager->persist($comment);
        }

        $manager->flush();
    }
}
