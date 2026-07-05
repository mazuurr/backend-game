<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create group_invitations table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE group_invitations (
                id INT AUTO_INCREMENT NOT NULL,
                uuid VARCHAR(36) NOT NULL,
                group_uuid VARCHAR(36) NOT NULL,
                user_uuid VARCHAR(36) NOT NULL,
                type VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT \'pending\',
                initiator_uuid VARCHAR(36) NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                responded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE KEY unique_uuid (uuid),
                UNIQUE KEY unique_pending_invitation (group_uuid, user_uuid, type, status),
                INDEX idx_group_invitations_group (group_uuid),
                INDEX idx_group_invitations_user (user_uuid),
                INDEX idx_group_invitations_status (status),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE group_invitations');
    }
}
