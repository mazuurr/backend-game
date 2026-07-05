<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pieces_x and pieces_y columns to puzzles table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puzzles ADD pieces_x INT DEFAULT NULL, ADD pieces_y INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puzzles DROP COLUMN pieces_x, DROP COLUMN pieces_y');
    }
}
