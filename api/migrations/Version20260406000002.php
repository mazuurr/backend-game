<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create campaigns table and add campaign_uuid to puzzles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE campaigns (
            id INT AUTO_INCREMENT NOT NULL,
            uuid VARCHAR(36) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(500) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY (id),
            UNIQUE INDEX UNIQ_CAMPAIGNS_UUID (uuid),
            UNIQUE INDEX UNIQ_CAMPAIGNS_NAME (name)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE puzzles ADD campaign_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_PUZZLES_CAMPAIGN_UUID ON puzzles (campaign_uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_PUZZLES_CAMPAIGN_UUID ON puzzles');
        $this->addSql('ALTER TABLE puzzles DROP campaign_uuid');
        $this->addSql('DROP TABLE campaigns');
    }
}
