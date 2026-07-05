<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create groups table and add group_uuid to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `groups` (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            uuid VARCHAR(36) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(500) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_F06D39705E237E06 (name),
            UNIQUE INDEX UNIQ_F06D3970D17F50A6 (uuid),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE users ADD group_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_1483A5E9B2948FF8 ON users (group_uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_1483A5E9B2948FF8 ON users');
        $this->addSql('ALTER TABLE users DROP group_uuid');
        $this->addSql('DROP TABLE `groups`');
    }
}
