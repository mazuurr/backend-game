<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add total_pieces to puzzles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puzzles ADD total_pieces INT DEFAULT NULL AFTER difficulty');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puzzles DROP COLUMN total_pieces');
    }
}
