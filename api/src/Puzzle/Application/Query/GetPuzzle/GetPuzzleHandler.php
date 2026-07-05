<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetPuzzle;

use App\Puzzle\Application\DTO\PuzzleDTO;
use App\Puzzle\Domain\Exception\PuzzleNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetPuzzleHandler
{
    public function __construct(
        private readonly PuzzleRepositoryInterface $puzzleRepository,
    ) {}

    public function __invoke(GetPuzzleQuery $query): PuzzleDTO
    {
        $puzzle = $this->puzzleRepository->findByUuid(new PuzzleId($query->uuid));

        if ($puzzle === null) {
            throw new PuzzleNotFoundException();
        }

        return PuzzleDTO::fromEntity($puzzle);
    }
}
