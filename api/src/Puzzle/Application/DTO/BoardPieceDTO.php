<?php

declare(strict_types=1);

namespace App\Puzzle\Application\DTO;

use App\Puzzle\Domain\Entity\PuzzleBoardPiece;

final class BoardPieceDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $pieceIndex,
        public readonly int $toX,
        public readonly int $toY,
        public readonly bool $correct,
        public readonly string $updatedAt,
    ) {}

    public static function fromEntity(PuzzleBoardPiece $piece): self
    {
        return new self(
            pieceIndex: $piece->getPieceIndex(),
            toX: $piece->getToX(),
            toY: $piece->getToY(),
            correct: $piece->isCorrect(),
            updatedAt: $piece->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'piece_index' => $this->pieceIndex,
            'to_x' => $this->toX,
            'to_y' => $this->toY,
            'correct' => $this->correct,
            'updated_at' => $this->updatedAt,
        ];
    }
}
