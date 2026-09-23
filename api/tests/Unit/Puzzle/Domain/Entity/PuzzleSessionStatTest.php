<?php

declare(strict_types=1);

namespace App\Tests\Unit\Puzzle\Domain\Entity;

use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Entity\PuzzleSessionStat;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\Shared\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PuzzleSessionStat::class)]
final class PuzzleSessionStatTest extends TestCase
{
    public function testComputesTimeSpentFromStartAndFinish(): void
    {
        $started = new \DateTimeImmutable('2026-01-01 10:00:00');
        $finished = new \DateTimeImmutable('2026-01-01 10:05:30');

        $stat = PuzzleSessionStat::capture(
            sessionUuid: PuzzleSessionId::generate(),
            puzzleUuid: PuzzleId::generate(),
            createdByUserUuid: UserId::generate(),
            mode: PuzzleSession::MODE_INDIVIDUAL,
            totalPieces: 500,
            correctPieces: 500,
            startedAt: $started,
            finishedAt: $finished,
        );

        self::assertSame(330, $stat->getTimeSpentSeconds());
        self::assertSame(500, $stat->getTotalPieces());
        self::assertSame(500, $stat->getCorrectPieces());
        self::assertEquals($started, $stat->getStartedAt());
        self::assertEquals($finished, $stat->getFinishedAt());
    }

    public function testNeverGoesNegativeIfFinishedIsBeforeStarted(): void
    {
        $started = new \DateTimeImmutable('2026-01-01 10:00:00');
        $finished = $started->modify('-1 hour');

        $stat = PuzzleSessionStat::capture(
            sessionUuid: PuzzleSessionId::generate(),
            puzzleUuid: PuzzleId::generate(),
            createdByUserUuid: UserId::generate(),
            mode: PuzzleSession::MODE_INDIVIDUAL,
            totalPieces: 100,
            correctPieces: 10,
            startedAt: $started,
            finishedAt: $finished,
        );

        self::assertSame(0, $stat->getTimeSpentSeconds());
    }
}
