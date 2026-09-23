<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetMySessionStats;

use App\Puzzle\Application\DTO\SessionStatDTO;
use App\Puzzle\Domain\Repository\PuzzleSessionStatRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Shared\Application\DTO\PaginatedResult;
use App\Shared\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetMySessionStatsHandler
{
    public function __construct(
        private readonly PuzzleSessionStatRepositoryInterface $statRepository,
    ) {}

    public function __invoke(GetMySessionStatsQuery $query): PaginatedResult
    {
        $userUuid = new UserId($query->requesterUserUuid);
        $puzzleUuid = $query->puzzleUuid !== null ? new PuzzleId($query->puzzleUuid) : null;
        $offset = ($query->page - 1) * $query->perPage;

        $stats = $this->statRepository->findPaginatedByUser($userUuid, $offset, $query->perPage, $puzzleUuid);
        $total = $this->statRepository->countByUser($userUuid, $puzzleUuid);

        return new PaginatedResult(
            data: array_map(fn ($stat) => SessionStatDTO::fromEntity($stat), $stats),
            page: $query->page,
            perPage: $query->perPage,
            total: $total,
        );
    }
}
