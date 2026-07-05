<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Entity;

use App\Puzzle\Domain\ValueObject\PieceMoveId;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\User\Domain\ValueObject\UserId;

/**
 * Append-only log entry: one row per piece drop.
 */
class PuzzlePieceMove
{
    private int $id;
    private PieceMoveId $uuid;
    private PuzzleSessionId $sessionUuid;
    private UserId $userUuid;
    private PuzzleId $puzzleUuid;
    private int $pieceIndex;
    private int $toX;
    private int $toY;
    private bool $correct;
    private int $seq;
    private \DateTimeImmutable $movedAt;

    private function __construct(
        PieceMoveId $uuid,
        PuzzleSessionId $sessionUuid,
        UserId $userUuid,
        PuzzleId $puzzleUuid,
        int $pieceIndex,
        int $toX,
        int $toY,
        bool $correct,
        int $seq,
    ) {
        $this->uuid = $uuid;
        $this->sessionUuid = $sessionUuid;
        $this->userUuid = $userUuid;
        $this->puzzleUuid = $puzzleUuid;
        $this->pieceIndex = $pieceIndex;
        $this->toX = $toX;
        $this->toY = $toY;
        $this->correct = $correct;
        $this->seq = $seq;
        $this->movedAt = new \DateTimeImmutable();
    }

    public static function record(
        PieceMoveId $uuid,
        PuzzleSessionId $sessionUuid,
        UserId $userUuid,
        PuzzleId $puzzleUuid,
        int $pieceIndex,
        int $toX,
        int $toY,
        bool $correct,
        int $seq,
    ): self {
        return new self($uuid, $sessionUuid, $userUuid, $puzzleUuid, $pieceIndex, $toX, $toY, $correct, $seq);
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): PieceMoveId { return $this->uuid; }
    public function getSessionUuid(): PuzzleSessionId { return $this->sessionUuid; }
    public function getUserUuid(): UserId { return $this->userUuid; }
    public function getPuzzleUuid(): PuzzleId { return $this->puzzleUuid; }
    public function getPieceIndex(): int { return $this->pieceIndex; }
    public function getToX(): int { return $this->toX; }
    public function getToY(): int { return $this->toY; }
    public function isCorrect(): bool { return $this->correct; }
    public function getSeq(): int { return $this->seq; }
    public function getMovedAt(): \DateTimeImmutable { return $this->movedAt; }
}
