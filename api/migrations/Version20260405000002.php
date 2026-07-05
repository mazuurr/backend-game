<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add owner_uuid to groups table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `groups` ADD owner_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_F06D39702B18554A ON `groups` (owner_uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_F06D39702B18554A ON `groups`');
        $this->addSql('ALTER TABLE `groups` DROP owner_uuid');
    }
}
