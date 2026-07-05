<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetPuzzleProgress;

use App\Puzzle\Application\DTO\PuzzleProgressDTO;
use App\Puzzle\Domain\Exception\PuzzleNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleProgressRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetPuzzleProgressHandler
{
    public function __construct(
        private readonly PuzzleProgressRepositoryInterface $progressRepository,
        private readonly PuzzleRepositoryInterface $puzzleRepository,
    ) {}

    /** @return PuzzleProgressDTO[] */
    public function __invoke(GetPuzzleProgressQuery $query): array
    {
        $puzzleId = new PuzzleId($query->puzzleUuid);
        $puzzle = $this->puzzleRepository->findByUuid($puzzleId);

        if ($puzzle === null) {
            throw new PuzzleNotFoundException();
        }

        $progressList = $this->progressRepository->findByPuzzle($puzzleId);

        return array_map(
            static fn ($progress) => PuzzleProgressDTO::fromEntity($progress, $puzzle->getPiecesCount()),
            $progressList,
        );
    }
}
