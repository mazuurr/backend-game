<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Entity;

use App\Puzzle\Domain\ValueObject\PieceMoveId;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;

/**
 * Materialized current board state: exactly one row per (session, piece).
 */
class PuzzleBoardPiece
{
    private int $id;
    private PuzzleSessionId $sessionUuid;
    private int $pieceIndex;
    private int $toX;
    private int $toY;
    private bool $correct;
    private \DateTimeImmutable $updatedAt;
    private PieceMoveId $lastMoveUuid;

    private function __construct(
        PuzzleSessionId $sessionUuid,
        int $pieceIndex,
        int $toX,
        int $toY,
        bool $correct,
        PieceMoveId $lastMoveUuid,
    ) {
        $this->sessionUuid = $sessionUuid;
        $this->pieceIndex = $pieceIndex;
        $this->toX = $toX;
        $this->toY = $toY;
        $this->correct = $correct;
        $this->lastMoveUuid = $lastMoveUuid;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function place(
        PuzzleSessionId $sessionUuid,
        int $pieceIndex,
        int $toX,
        int $toY,
        bool $correct,
        PieceMoveId $lastMoveUuid,
    ): self {
        return new self($sessionUuid, $pieceIndex, $toX, $toY, $correct, $lastMoveUuid);
    }

    public function moveTo(int $toX, int $toY, bool $correct, PieceMoveId $lastMoveUuid): void
    {
        $this->toX = $toX;
        $this->toY = $toY;
        $this->correct = $correct;
        $this->lastMoveUuid = $lastMoveUuid;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getSessionUuid(): PuzzleSessionId { return $this->sessionUuid; }
    public function getPieceIndex(): int { return $this->pieceIndex; }
    public function getToX(): int { return $this->toX; }
    public function getToY(): int { return $this->toY; }
    public function isCorrect(): bool { return $this->correct; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getLastMoveUuid(): PieceMoveId { return $this->lastMoveUuid; }
}
