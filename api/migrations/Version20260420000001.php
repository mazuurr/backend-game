<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unique_pending_invitation constraint from group_invitations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE group_invitations DROP INDEX unique_pending_invitation');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE group_invitations ADD UNIQUE KEY unique_pending_invitation (group_uuid, user_uuid, type, status)');
    }
}
