<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\ValueObject;

/**
 * Number of puzzle pieces along each axis.
 *
 * Pieces are square, so a piece's width (imageWidth / x) must equal its height
 * (imageHeight / y). That constrains the grid to the image aspect ratio:
 *
 *     x / y = imageWidth / imageHeight
 *
 * Combined with x * y ≈ totalPieces this yields:
 *
 *     x = sqrt(totalPieces * aspect),  y = sqrt(totalPieces / aspect)
 *
 * Rounding to whole pieces means the real total (x * y) can drift slightly from
 * the requested count; use total() as the authoritative piece count.
 */
final class PieceGrid
{
    private function __construct(
        public readonly int $x,
        public readonly int $y,
    ) {}

    public static function fromTotalPieces(int $imageWidth, int $imageHeight, int $totalPieces): self
    {
        if ($totalPieces < 1) {
            throw new \InvalidArgumentException('Total pieces must be at least 1.');
        }

        $aspect = ($imageWidth > 0 && $imageHeight > 0)
            ? $imageWidth / $imageHeight
            : 1.0;

        $x = (int) max(1, round(sqrt($totalPieces * $aspect)));
        $y = (int) max(1, round(sqrt($totalPieces / $aspect)));

        return new self($x, $y);
    }

    public function total(): int
    {
        return $this->x * $this->y;
    }
}
