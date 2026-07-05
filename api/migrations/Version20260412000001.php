<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add difficulty column to puzzles table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puzzles ADD difficulty INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puzzles DROP COLUMN difficulty');
    }
}
