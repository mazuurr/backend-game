<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename pieces_count to pieces_per_fragment; add current_fragment and time_spent_seconds to puzzle_progress';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puzzles CHANGE pieces_count pieces_per_fragment INT DEFAULT NULL');

        $this->addSql('ALTER TABLE puzzle_progress
            ADD current_fragment INT NOT NULL DEFAULT 1,
            ADD time_spent_seconds INT NOT NULL DEFAULT 0
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puzzles CHANGE pieces_per_fragment pieces_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE puzzle_progress DROP COLUMN current_fragment, DROP COLUMN time_spent_seconds');
    }
}
