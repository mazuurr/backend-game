<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pieces_count to puzzles; create puzzle_progress table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puzzles ADD pieces_count INT DEFAULT NULL');

        $this->addSql('
            CREATE TABLE puzzle_progress (
                id INT AUTO_INCREMENT NOT NULL,
                user_uuid VARCHAR(36) NOT NULL,
                puzzle_uuid VARCHAR(36) NOT NULL,
                pieces_placed INT NOT NULL DEFAULT 0,
                completed TINYINT(1) NOT NULL DEFAULT 0,
                started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY (id),
                UNIQUE KEY unique_user_puzzle (user_uuid, puzzle_uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE puzzle_progress');
        $this->addSql('ALTER TABLE puzzles DROP COLUMN pieces_count');
    }
}
