<?php

declare(strict_types=1);

namespace App\Tests\Unit\Puzzle\Application\Query;

use App\Puzzle\Application\DTO\SessionStatDTO;
use App\Puzzle\Application\Query\GetSessionStats\GetSessionStatsHandler;
use App\Puzzle\Application\Query\GetSessionStats\GetSessionStatsQuery;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Entity\PuzzleSessionStat;
use App\Puzzle\Domain\Repository\PuzzleSessionStatRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\Shared\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetSessionStatsHandler::class)]
final class GetSessionStatsHandlerTest extends TestCase
{
    private PuzzleSessionStatRepositoryInterface&MockObject $statRepository;

    protected function setUp(): void
    {
        $this->statRepository = $this->createMock(PuzzleSessionStatRepositoryInterface::class);
    }

    public function testIsPaginated(): void
    {
        $this->statRepository->expects(self::once())
            ->method('findPaginated')
            ->with(40, 20)
            ->willReturn([$this->stat()]);
        $this->statRepository->expects(self::once())
            ->method('count')
            ->willReturn(41);

        $payload = (new GetSessionStatsHandler($this->statRepository))(
            new GetSessionStatsQuery(page: 3, perPage: 20),
        )->jsonSerialize();

        self::assertCount(1, $payload['data']);
        self::assertContainsOnlyInstancesOf(SessionStatDTO::class, $payload['data']);
        self::assertSame(['page' => 3, 'per_page' => 20, 'total' => 41, 'pages' => 3], $payload['meta']);
    }

    public function testDefaultsToFirstPage(): void
    {
        $this->statRepository->expects(self::once())
            ->method('findPaginated')
            ->with(0, 20)
            ->willReturn([]);
        $this->statRepository->method('count')->willReturn(0);

        $result = (new GetSessionStatsHandler($this->statRepository))(new GetSessionStatsQuery());

        self::assertSame([], $result->jsonSerialize()['data']);
    }

    private function stat(): PuzzleSessionStat
    {
        return PuzzleSessionStat::capture(
            sessionUuid: PuzzleSessionId::generate(),
            puzzleUuid: PuzzleId::generate(),
            createdByUserUuid: UserId::generate(),
            mode: PuzzleSession::MODE_INDIVIDUAL,
            totalPieces: 500,
            correctPieces: 500,
            startedAt: new \DateTimeImmutable('-1 hour'),
            finishedAt: new \DateTimeImmutable(),
        );
    }
}
