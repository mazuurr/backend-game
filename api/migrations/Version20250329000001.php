<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250329000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            uuid VARCHAR(36) NOT NULL,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(180) NOT NULL,
            password VARCHAR(255) NOT NULL,
            premium TINYINT(1) NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 0,
            activation_token VARCHAR(64) DEFAULT NULL,
            token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_users_uuid ON users (uuid)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_users_email ON users (email)');
        $this->addSql('CREATE INDEX IDX_users_activation_token ON users (activation_token)');
        $this->addSql('CREATE INDEX IDX_users_active ON users (active)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
    }
}
