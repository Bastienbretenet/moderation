<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001130434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add comment status history and optimistic locking version';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE comment_status_change (id UUID NOT NULL, previous_status VARCHAR(32) DEFAULT NULL, new_status VARCHAR(32) NOT NULL, origin VARCHAR(32) NOT NULL, reason TEXT DEFAULT NULL, changed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, comment_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D331B220F8697D13 ON comment_status_change (comment_id)');
        $this->addSql('ALTER TABLE comment_status_change ADD CONSTRAINT FK_D331B220F8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE comment ADD version INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment_status_change DROP CONSTRAINT FK_D331B220F8697D13');
        $this->addSql('DROP TABLE comment_status_change');
        $this->addSql('ALTER TABLE comment DROP version');
    }
}
