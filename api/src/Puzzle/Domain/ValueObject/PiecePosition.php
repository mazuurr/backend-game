<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\ValueObject;

final class PiecePosition
{
    public function __construct(
        public readonly int $x,
        public readonly int $y,
    ) {
        if ($x < 0 || $y < 0) {
            throw new \InvalidArgumentException('Piece position coordinates must be >= 0.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->x === $other->x && $this->y === $other->y;
    }
}
