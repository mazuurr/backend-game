<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create real-time puzzle session tables: sessions, piece moves log and materialized board state';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE puzzle_sessions (
            id INT AUTO_INCREMENT NOT NULL,
            uuid VARCHAR(36) NOT NULL,
            puzzle_uuid VARCHAR(36) NOT NULL,
            visibility VARCHAR(16) NOT NULL,
            group_uuid VARCHAR(36) DEFAULT NULL,
            created_by_user_uuid VARCHAR(36) NOT NULL,
            status VARCHAR(16) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_puzzle_sessions_uuid (uuid),
            INDEX idx_session_status (status),
            INDEX idx_session_expires_at (expires_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE puzzle_piece_moves (
            id INT AUTO_INCREMENT NOT NULL,
            uuid VARCHAR(36) NOT NULL,
            session_uuid VARCHAR(36) NOT NULL,
            user_uuid VARCHAR(36) NOT NULL,
            puzzle_uuid VARCHAR(36) NOT NULL,
            piece_index INT NOT NULL,
            to_x INT NOT NULL,
            to_y INT NOT NULL,
            correct TINYINT(1) NOT NULL,
            seq INT NOT NULL,
            moved_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_puzzle_piece_moves_uuid (uuid),
            INDEX idx_move_session_seq (session_uuid, seq),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE puzzle_board_state (
            id INT AUTO_INCREMENT NOT NULL,
            session_uuid VARCHAR(36) NOT NULL,
            piece_index INT NOT NULL,
            to_x INT NOT NULL,
            to_y INT NOT NULL,
            correct TINYINT(1) NOT NULL,
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            last_move_uuid VARCHAR(36) NOT NULL,
            UNIQUE INDEX unique_session_piece (session_uuid, piece_index),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE puzzle_board_state');
        $this->addSql('DROP TABLE puzzle_piece_moves');
        $this->addSql('DROP TABLE puzzle_sessions');
    }
}
