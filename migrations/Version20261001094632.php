<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001094632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align comment indexes with search filters and sort';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX comment_publisher_status_idx');
        $this->addSql('DROP INDEX comment_status_idx');
        $this->addSql('CREATE INDEX comment_publisher_status_submitted_at_idx ON comment (publisher, status, submitted_at)');
        $this->addSql('CREATE INDEX comment_status_submitted_at_idx ON comment (status, submitted_at)');
        $this->addSql('CREATE INDEX comment_submitted_at_idx ON comment (submitted_at)');
        $this->addSql('CREATE INDEX comment_source_idx ON comment (source)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX comment_publisher_status_submitted_at_idx');
        $this->addSql('DROP INDEX comment_status_submitted_at_idx');
        $this->addSql('DROP INDEX comment_submitted_at_idx');
        $this->addSql('DROP INDEX comment_source_idx');
        $this->addSql('CREATE INDEX comment_publisher_status_idx ON comment (publisher, status)');
        $this->addSql('CREATE INDEX comment_status_idx ON comment (status)');
    }
}
