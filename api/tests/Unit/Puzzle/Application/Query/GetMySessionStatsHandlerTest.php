<?php

declare(strict_types=1);

namespace App\Tests\Unit\Puzzle\Application\Query;

use App\Puzzle\Application\DTO\SessionStatDTO;
use App\Puzzle\Application\Query\GetMySessionStats\GetMySessionStatsHandler;
use App\Puzzle\Application\Query\GetMySessionStats\GetMySessionStatsQuery;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Entity\PuzzleSessionStat;
use App\Puzzle\Domain\Repository\PuzzleSessionStatRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\Shared\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetMySessionStatsHandler::class)]
final class GetMySessionStatsHandlerTest extends TestCase
{
    private PuzzleSessionStatRepositoryInterface&MockObject $statRepository;
    private UserId $userId;

    protected function setUp(): void
    {
        $this->statRepository = $this->createMock(PuzzleSessionStatRepositoryInterface::class);
        $this->userId = UserId::generate();
    }

    public function testIsScopedToTheRequesterAndPaginated(): void
    {
        $this->statRepository->expects(self::once())
            ->method('findPaginatedByUser')
            ->with(self::callback(fn (UserId $id) => $id->equals($this->userId)), 40, 20, null)
            ->willReturn([$this->stat()]);
        $this->statRepository->expects(self::once())
            ->method('countByUser')
            ->with(self::callback(fn (UserId $id) => $id->equals($this->userId)), null)
            ->willReturn(41);

        $payload = (new GetMySessionStatsHandler($this->statRepository))(
            new GetMySessionStatsQuery(requesterUserUuid: $this->userId->value(), page: 3, perPage: 20),
        )->jsonSerialize();

        self::assertCount(1, $payload['data']);
        self::assertContainsOnlyInstancesOf(SessionStatDTO::class, $payload['data']);
        self::assertSame(['page' => 3, 'per_page' => 20, 'total' => 41, 'pages' => 3], $payload['meta']);
    }

    public function testFiltersByPuzzleWhenGiven(): void
    {
        $puzzleId = PuzzleId::generate();

        $this->statRepository->expects(self::once())
            ->method('findPaginatedByUser')
            ->with(
                self::callback(fn (UserId $id) => $id->equals($this->userId)),
                0,
                20,
                self::callback(fn (PuzzleId $id) => $id->equals($puzzleId)),
            )
            ->willReturn([]);
        $this->statRepository->method('countByUser')->willReturn(0);

        (new GetMySessionStatsHandler($this->statRepository))(
            new GetMySessionStatsQuery(requesterUserUuid: $this->userId->value(), puzzleUuid: $puzzleId->value()),
        );
    }

    private function stat(): PuzzleSessionStat
    {
        return PuzzleSessionStat::capture(
            sessionUuid: PuzzleSessionId::generate(),
            puzzleUuid: PuzzleId::generate(),
            createdByUserUuid: $this->userId,
            mode: PuzzleSession::MODE_INDIVIDUAL,
            totalPieces: 500,
            correctPieces: 500,
            startedAt: new \DateTimeImmutable('-1 hour'),
            finishedAt: new \DateTimeImmutable(),
        );
    }
}
