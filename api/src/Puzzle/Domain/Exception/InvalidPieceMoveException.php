<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Exception;

final class InvalidPieceMoveException extends \DomainException
{
    public static function pieceIndexOutOfRange(int $pieceIndex, int $totalPieces): self
    {
        return new self(sprintf('Piece index %d is out of range [0, %d).', $pieceIndex, $totalPieces));
    }

    public static function positionOutOfGrid(int $x, int $y, int $piecesX, int $piecesY): self
    {
        return new self(sprintf('Position (%d, %d) is outside the grid %dx%d.', $x, $y, $piecesX, $piecesY));
    }
}
