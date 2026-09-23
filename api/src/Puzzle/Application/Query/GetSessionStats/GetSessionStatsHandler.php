<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetSessionStats;

use App\Puzzle\Application\DTO\SessionStatDTO;
use App\Puzzle\Domain\Repository\PuzzleSessionStatRepositoryInterface;
use App\Shared\Application\DTO\PaginatedResult;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetSessionStatsHandler
{
    public function __construct(
        private readonly PuzzleSessionStatRepositoryInterface $statRepository,
    ) {}

    public function __invoke(GetSessionStatsQuery $query): PaginatedResult
    {
        $offset = ($query->page - 1) * $query->perPage;

        $stats = $this->statRepository->findPaginated($offset, $query->perPage);
        $total = $this->statRepository->count();

        return new PaginatedResult(
            data: array_map(fn ($stat) => SessionStatDTO::fromEntity($stat), $stats),
            page: $query->page,
            perPage: $query->perPage,
            total: $total,
        );
    }
}
