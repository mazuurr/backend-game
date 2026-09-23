<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the initial users and puzzles schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                uuid VARCHAR(36) NOT NULL,
                username VARCHAR(50) NOT NULL,
                email VARCHAR(180) NOT NULL,
                password VARCHAR(255) NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 0,
                activation_token VARCHAR(64) DEFAULT NULL,
                token_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                reset_token VARCHAR(64) DEFAULT NULL,
                reset_token_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_users_uuid (uuid),
                UNIQUE INDEX UNIQ_users_email (email),
                INDEX IDX_users_activation_token (activation_token),
                INDEX IDX_users_active (active),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE puzzles (
                id INT AUTO_INCREMENT NOT NULL,
                uuid VARCHAR(36) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                stored_filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                size INT NOT NULL,
                difficulty INT DEFAULT NULL,
                total_pieces INT DEFAULT NULL,
                pieces_per_fragment INT DEFAULT NULL,
                pieces_x INT DEFAULT NULL,
                pieces_y INT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_puzzles_uuid (uuid),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE puzzle_progress (
                id INT AUTO_INCREMENT NOT NULL,
                user_uuid VARCHAR(36) NOT NULL,
                puzzle_uuid VARCHAR(36) NOT NULL,
                current_fragment INT NOT NULL DEFAULT 1,
                pieces_placed INT NOT NULL DEFAULT 0,
                time_spent_seconds INT NOT NULL DEFAULT 0,
                completed TINYINT(1) NOT NULL DEFAULT 0,
                started_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX unique_user_puzzle (user_uuid, puzzle_uuid),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE puzzle_sessions (
                id INT AUTO_INCREMENT NOT NULL,
                uuid VARCHAR(36) NOT NULL,
                puzzle_uuid VARCHAR(36) NOT NULL,
                visibility VARCHAR(16) NOT NULL,
                mode VARCHAR(16) NOT NULL DEFAULT 'individual',
                created_by_user_uuid VARCHAR(36) NOT NULL,
                status VARCHAR(16) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                closed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_puzzle_sessions_uuid (uuid),
                INDEX idx_session_status (status),
                INDEX idx_session_expires_at (expires_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE puzzle_piece_moves (
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
                moved_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_puzzle_piece_moves_uuid (uuid),
                INDEX idx_move_session_seq (session_uuid, seq),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE puzzle_board_state (
                id INT AUTO_INCREMENT NOT NULL,
                session_uuid VARCHAR(36) NOT NULL,
                piece_index INT NOT NULL,
                to_x INT NOT NULL,
                to_y INT NOT NULL,
                correct TINYINT(1) NOT NULL,
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                last_move_uuid VARCHAR(36) NOT NULL,
                UNIQUE INDEX unique_session_piece (session_uuid, piece_index),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE puzzle_session_stats (
                id INT AUTO_INCREMENT NOT NULL,
                session_uuid VARCHAR(36) NOT NULL,
                puzzle_uuid VARCHAR(36) NOT NULL,
                created_by_user_uuid VARCHAR(36) NOT NULL,
                mode VARCHAR(16) NOT NULL,
                total_pieces INT NOT NULL,
                correct_pieces INT NOT NULL,
                time_spent_seconds INT NOT NULL,
                started_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                finished_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                recorded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_session_stat_session_uuid (session_uuid),
                INDEX idx_session_stat_user (created_by_user_uuid),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE puzzle_session_stats');
        $this->addSql('DROP TABLE puzzle_board_state');
        $this->addSql('DROP TABLE puzzle_piece_moves');
        $this->addSql('DROP TABLE puzzle_sessions');
        $this->addSql('DROP TABLE puzzle_progress');
        $this->addSql('DROP TABLE puzzles');
        $this->addSql('DROP TABLE users');
    }
}
