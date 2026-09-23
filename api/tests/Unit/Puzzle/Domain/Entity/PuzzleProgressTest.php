<?php

declare(strict_types=1);

namespace App\Tests\Unit\Puzzle\Domain\Entity;

use App\Puzzle\Application\DTO\PuzzleProgressDTO;
use App\Puzzle\Domain\Entity\PuzzleProgress;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Shared\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PuzzleProgress::class)]
#[CoversClass(PuzzleProgressDTO::class)]
final class PuzzleProgressTest extends TestCase
{
    public function testStartsAtTheFirstFragmentWithNothingPlaced(): void
    {
        $progress = $this->progress();

        self::assertSame(1, $progress->getCurrentFragment());
        self::assertSame(0, $progress->getPiecesPlaced());
        self::assertSame(0, $progress->getTimeSpentSeconds());
        self::assertFalse($progress->isCompleted());
        self::assertNull($progress->getCompletedAt());
    }

    public function testRecordStoresReportedValues(): void
    {
        $progress = $this->progress();

        $progress->record(currentFragment: 2, piecesPlaced: 12, timeSpentSeconds: 300, completed: false, piecesPerFragment: 40);

        self::assertSame(2, $progress->getCurrentFragment());
        self::assertSame(12, $progress->getPiecesPlaced());
        self::assertSame(300, $progress->getTimeSpentSeconds());
        self::assertFalse($progress->isCompleted());
    }

    public function testExplicitCompletionFlagCompletesTheProgress(): void
    {
        $progress = $this->progress();

        $progress->record(1, 5, 60, completed: true, piecesPerFragment: null);

        self::assertTrue($progress->isCompleted());
        self::assertNotNull($progress->getCompletedAt());
    }

    public function testReachingTheFragmentSizeCompletesTheProgress(): void
    {
        $progress = $this->progress();

        $progress->record(1, 40, 600, completed: false, piecesPerFragment: 40);

        self::assertTrue($progress->isCompleted());
    }

    public function testStayingBelowTheFragmentSizeDoesNotComplete(): void
    {
        $progress = $this->progress();

        $progress->record(1, 39, 600, completed: false, piecesPerFragment: 40);

        self::assertFalse($progress->isCompleted());
    }

    public function testUnknownFragmentSizeNeverAutoCompletes(): void
    {
        $progress = $this->progress();

        $progress->record(1, 9999, 600, completed: false, piecesPerFragment: null);

        self::assertFalse($progress->isCompleted());
    }

    public function testCompletionTimestampIsSetOnlyOnce(): void
    {
        $progress = $this->progress();
        $progress->record(1, 40, 600, false, 40);
        $completedAt = $progress->getCompletedAt();

        $progress->record(2, 80, 1200, false, 40);

        self::assertEquals($completedAt, $progress->getCompletedAt());
    }

    public function testDtoComputesFragmentPercent(): void
    {
        $progress = $this->progress();
        $progress->record(1, 10, 60, false, 40);

        $dto = PuzzleProgressDTO::fromEntity($progress, 40);

        self::assertSame(25.0, $dto->fragmentPercent);
        self::assertSame(40, $dto->piecesPerFragment);
    }

    public function testDtoLeavesPercentNullWithoutFragmentSize(): void
    {
        $dto = PuzzleProgressDTO::fromEntity($this->progress(), null);

        self::assertNull($dto->fragmentPercent);
    }

    public function testDtoAvoidsDivisionByZero(): void
    {
        $dto = PuzzleProgressDTO::fromEntity($this->progress(), 0);

        self::assertNull($dto->fragmentPercent);
    }

    public function testDtoPercentCanExceed100(): void
    {
        $progress = $this->progress();
        $progress->record(1, 50, 60, false, 40);

        self::assertSame(125.0, PuzzleProgressDTO::fromEntity($progress, 40)->fragmentPercent);
    }

    private function progress(): PuzzleProgress
    {
        return PuzzleProgress::start(UserId::generate(), PuzzleId::generate());
    }
}
