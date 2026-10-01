<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001083115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add comment status index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX comment_status_idx ON comment (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX comment_status_idx');
    }
}
