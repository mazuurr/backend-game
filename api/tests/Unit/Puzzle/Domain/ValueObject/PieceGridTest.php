<?php

declare(strict_types=1);

namespace App\Tests\Unit\Puzzle\Domain\ValueObject;

use App\Puzzle\Domain\ValueObject\PieceGrid;
use App\Puzzle\Domain\ValueObject\PiecePosition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PieceGrid::class)]
#[CoversClass(PiecePosition::class)]
final class PieceGridTest extends TestCase
{
    public function testSquareImageGivesSquareGrid(): void
    {
        $grid = PieceGrid::fromTotalPieces(1000, 1000, 100);

        self::assertSame(10, $grid->x);
        self::assertSame(10, $grid->y);
        self::assertSame(100, $grid->total());
    }

    public function testWideImageGetsMoreColumnsThanRows(): void
    {
        $grid = PieceGrid::fromTotalPieces(1600, 900, 100);

        self::assertGreaterThan($grid->y, $grid->x);
    }

    public function testTallImageGetsMoreRowsThanColumns(): void
    {
        $grid = PieceGrid::fromTotalPieces(900, 1600, 100);

        self::assertGreaterThan($grid->x, $grid->y);
    }

    public function testPiecesStaySquareForTheGivenAspectRatio(): void
    {
        $grid = PieceGrid::fromTotalPieces(1600, 900, 100);

        $pieceWidth = 1600 / $grid->x;
        $pieceHeight = 900 / $grid->y;

        // Rounding to whole pieces makes them near-square rather than exactly square.
        self::assertEqualsWithDelta($pieceWidth, $pieceHeight, $pieceWidth * 0.1);
    }

    #[DataProvider('unknownDimensions')]
    public function testUnknownDimensionsFallBackToASquareGrid(int $width, int $height): void
    {
        $grid = PieceGrid::fromTotalPieces($width, $height, 49);

        self::assertSame(7, $grid->x);
        self::assertSame(7, $grid->y);
    }

    public static function unknownDimensions(): array
    {
        return [
            'both zero' => [0, 0],
            'zero width' => [0, 1000],
            'zero height' => [1000, 0],
            'negative' => [-100, -100],
        ];
    }

    public function testTotalCanDriftFromTheRequestedCount(): void
    {
        // 100 pieces cannot tile a 16:9 image exactly; total() is the authority.
        $grid = PieceGrid::fromTotalPieces(1600, 900, 100);

        self::assertSame($grid->x * $grid->y, $grid->total());
        self::assertEqualsWithDelta(100, $grid->total(), 15);
    }

    public function testSinglePiece(): void
    {
        $grid = PieceGrid::fromTotalPieces(1000, 1000, 1);

        self::assertSame(1, $grid->total());
    }

    public function testExtremeAspectRatioStillYieldsAtLeastOnePiecePerAxis(): void
    {
        $grid = PieceGrid::fromTotalPieces(10000, 10, 4);

        self::assertGreaterThanOrEqual(1, $grid->x);
        self::assertGreaterThanOrEqual(1, $grid->y);
    }

    #[DataProvider('invalidTotals')]
    public function testRejectsNonPositiveTotal(int $total): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PieceGrid::fromTotalPieces(1000, 1000, $total);
    }

    public static function invalidTotals(): array
    {
        return ['zero' => [0], 'negative' => [-5]];
    }

    // --- PiecePosition ---

    public function testPositionAcceptsOrigin(): void
    {
        $position = new PiecePosition(0, 0);

        self::assertSame(0, $position->x);
        self::assertSame(0, $position->y);
    }

    #[DataProvider('negativePositions')]
    public function testPositionRejectsNegativeCoordinates(int $x, int $y): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PiecePosition($x, $y);
    }

    public static function negativePositions(): array
    {
        return [
            'negative x' => [-1, 0],
            'negative y' => [0, -1],
            'both negative' => [-1, -1],
        ];
    }

    public function testPositionEquals(): void
    {
        self::assertTrue((new PiecePosition(2, 3))->equals(new PiecePosition(2, 3)));
        self::assertFalse((new PiecePosition(2, 3))->equals(new PiecePosition(3, 2)));
    }
}
