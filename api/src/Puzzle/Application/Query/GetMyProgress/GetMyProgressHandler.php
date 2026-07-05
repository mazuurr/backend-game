<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetMyProgress;

use App\Puzzle\Application\DTO\PuzzleProgressDTO;
use App\Puzzle\Domain\Repository\PuzzleProgressRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetMyProgressHandler
{
    public function __construct(
        private readonly PuzzleProgressRepositoryInterface $progressRepository,
        private readonly PuzzleRepositoryInterface $puzzleRepository,
    ) {}

    /** @return PuzzleProgressDTO[] */
    public function __invoke(GetMyProgressQuery $query): array
    {
        $progressList = $this->progressRepository->findByUser(new UserId($query->userUuid));

        return array_map(function ($progress) {
            $puzzle = $this->puzzleRepository->findByUuid($progress->getPuzzleUuid());

            return PuzzleProgressDTO::fromEntity($progress, $puzzle?->getPiecesCount());
        }, $progressList);
    }
}
