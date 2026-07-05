<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetPuzzles;

use App\Puzzle\Application\DTO\PuzzleDTO;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Shared\Application\DTO\PaginatedResult;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetPuzzlesHandler
{
    public function __construct(
        private readonly PuzzleRepositoryInterface $puzzleRepository,
    ) {}

    public function __invoke(GetPuzzlesQuery $query): PaginatedResult
    {
        $offset = ($query->page - 1) * $query->perPage;

        $puzzles = $this->puzzleRepository->findPaginated($offset, $query->perPage, $query->campaignUuid);
        $total = $this->puzzleRepository->countFiltered($query->campaignUuid);

        return new PaginatedResult(
            data: array_map(static fn ($puzzle) => PuzzleDTO::fromEntity($puzzle), $puzzles),
            page: $query->page,
            perPage: $query->perPage,
            total: $total,
        );
    }
}
