<?php

declare(strict_types=1);

namespace App\Puzzle\Application\DTO;

use App\Puzzle\Domain\Entity\PuzzlePieceMove;

final class PieceMoveDTO implements \JsonSerializable
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $sessionUuid,
        public readonly string $userUuid,
        public readonly int $pieceIndex,
        public readonly int $toX,
        public readonly int $toY,
        public readonly bool $correct,
        public readonly int $seq,
        public readonly string $movedAt,
    ) {}

    public static function fromEntity(PuzzlePieceMove $move): self
    {
        return new self(
            uuid: $move->getUuid()->value(),
            sessionUuid: $move->getSessionUuid()->value(),
            userUuid: $move->getUserUuid()->value(),
            pieceIndex: $move->getPieceIndex(),
            toX: $move->getToX(),
            toY: $move->getToY(),
            correct: $move->isCorrect(),
            seq: $move->getSeq(),
            movedAt: $move->getMovedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'session_uuid' => $this->sessionUuid,
            'user_uuid' => $this->userUuid,
            'piece_index' => $this->pieceIndex,
            'to_x' => $this->toX,
            'to_y' => $this->toY,
            'correct' => $this->correct,
            'seq' => $this->seq,
            'moved_at' => $this->movedAt,
        ];
    }
}
