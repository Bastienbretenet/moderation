<?php

declare(strict_types=1);

namespace SudOuest\Comment\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait CreatesClientWithDatabase
{
    private static function createClientWithDatabase(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        $entityManager = self::entityManager();
        (new SchemaTool($entityManager))->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        return $client;
    }

    private static function entityManager(): EntityManagerInterface
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
