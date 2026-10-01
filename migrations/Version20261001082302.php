<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001082302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create author and comment tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE author (banned_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id UUID NOT NULL, external_id VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BDAFD8C89F75D7B0 ON author (external_id)');
        $this->addSql('CREATE TABLE comment (status VARCHAR(32) NOT NULL, rejection_reason VARCHAR(32) DEFAULT NULL, category VARCHAR(64) DEFAULT NULL, moderation_explanation TEXT DEFAULT NULL, moderated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id UUID NOT NULL, publisher VARCHAR(100) NOT NULL, source VARCHAR(255) NOT NULL, content TEXT NOT NULL, submitted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, author_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9474526CF675F31B ON comment (author_id)');
        $this->addSql('CREATE INDEX comment_publisher_status_idx ON comment (publisher, status)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES author (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526CF675F31B');
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE comment');
    }
}
