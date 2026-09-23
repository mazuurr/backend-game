<?php

declare(strict_types=1);

namespace App\Tests\Unit\Puzzle\Application\Query;

use App\Puzzle\Application\DTO\PuzzleProgressDTO;
use App\Puzzle\Application\Query\GetMyProgress\GetMyProgressHandler;
use App\Puzzle\Application\Query\GetMyProgress\GetMyProgressQuery;
use App\Puzzle\Application\Query\GetPuzzleProgress\GetPuzzleProgressHandler;
use App\Puzzle\Application\Query\GetPuzzleProgress\GetPuzzleProgressQuery;
use App\Puzzle\Domain\Entity\Puzzle;
use App\Puzzle\Domain\Entity\PuzzleProgress;
use App\Puzzle\Domain\Exception\PuzzleNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleProgressRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Shared\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetMyProgressHandler::class)]
#[CoversClass(GetPuzzleProgressHandler::class)]
final class ProgressQueryHandlersTest extends TestCase
{
    private PuzzleProgressRepositoryInterface&MockObject $progressRepository;
    private PuzzleRepositoryInterface&MockObject $puzzleRepository;
    private PuzzleId $puzzleId;
    private UserId $userId;

    protected function setUp(): void
    {
        $this->progressRepository = $this->createMock(PuzzleProgressRepositoryInterface::class);
        $this->puzzleRepository = $this->createMock(PuzzleRepositoryInterface::class);
        $this->puzzleId = PuzzleId::generate();
        $this->userId = UserId::generate();
    }

    public function testMyProgressIsEmptyWhenNothingWasStarted(): void
    {
        $this->progressRepository->method('findByUser')->willReturn([]);

        $result = (new GetMyProgressHandler($this->progressRepository, $this->puzzleRepository))(
            new GetMyProgressQuery($this->userId->value()),
        );

        self::assertSame([], $result);
    }

    public function testMyProgressIsLookedUpForTheRequestingUser(): void
    {
        $this->progressRepository->expects(self::once())
            ->method('findByUser')
            ->with(self::callback(fn (UserId $id) => $id->equals($this->userId)))
            ->willReturn([]);

        (new GetMyProgressHandler($this->progressRepository, $this->puzzleRepository))(
            new GetMyProgressQuery($this->userId->value()),
        );
    }

    public function testMyProgressEnrichesEachEntryWithFragmentSize(): void
    {
        $this->progressRepository->method('findByUser')->willReturn([$this->progress(10)]);
        $this->puzzleRepository->method('findByUuid')->willReturn($this->puzzle());

        $result = (new GetMyProgressHandler($this->progressRepository, $this->puzzleRepository))(
            new GetMyProgressQuery($this->userId->value()),
        );

        self::assertCount(1, $result);
        self::assertContainsOnlyInstancesOf(PuzzleProgressDTO::class, $result);
        self::assertSame(25, $result[0]->piecesPerFragment);
        self::assertSame(40.0, $result[0]->fragmentPercent);
    }

    public function testMyProgressSurvivesADeletedPuzzle(): void
    {
        $this->progressRepository->method('findByUser')->willReturn([$this->progress(10)]);
        $this->puzzleRepository->method('findByUuid')->willReturn(null);

        $result = (new GetMyProgressHandler($this->progressRepository, $this->puzzleRepository))(
            new GetMyProgressQuery($this->userId->value()),
        );

        self::assertNull($result[0]->piecesPerFragment);
        self::assertNull($result[0]->fragmentPercent);
    }

    public function testPuzzleProgressListsEveryPlayer(): void
    {
        $this->puzzleRepository->method('findByUuid')->willReturn($this->puzzle());
        $this->progressRepository->method('findByPuzzle')->willReturn([
            $this->progress(10),
            $this->progress(25),
        ]);

        $result = (new GetPuzzleProgressHandler($this->progressRepository, $this->puzzleRepository))(
            new GetPuzzleProgressQuery($this->puzzleId->value()),
        );

        self::assertCount(2, $result);
        self::assertSame(40.0, $result[0]->fragmentPercent);
        self::assertSame(100.0, $result[1]->fragmentPercent);
    }

    public function testPuzzleProgressThrowsWhenPuzzleMissing(): void
    {
        $this->puzzleRepository->method('findByUuid')->willReturn(null);
        $this->progressRepository->expects(self::never())->method('findByPuzzle');

        $this->expectException(PuzzleNotFoundException::class);

        (new GetPuzzleProgressHandler($this->progressRepository, $this->puzzleRepository))(
            new GetPuzzleProgressQuery($this->puzzleId->value()),
        );
    }

    private function progress(int $piecesPlaced): PuzzleProgress
    {
        $progress = PuzzleProgress::start($this->userId, $this->puzzleId);
        $progress->record(1, $piecesPlaced, 120, false, null);

        return $progress;
    }

    private function puzzle(): Puzzle
    {
        return Puzzle::create(
            uuid: $this->puzzleId,
            originalName: 'obrazek.jpg',
            storedFilename: 'stored.jpg',
            mimeType: 'image/jpeg',
            size: 1024,
            difficulty: null,
            totalPieces: 100,
            piecesPerFragment: 25,
            piecesX: 10,
            piecesY: 10,
        );
    }
}
