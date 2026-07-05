<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\RecordProgress;

use App\Puzzle\Domain\Entity\PuzzleProgress;
use App\Puzzle\Domain\Exception\PuzzleNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleProgressRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RecordProgressHandler
{
    public function __construct(
        private readonly PuzzleRepositoryInterface $puzzleRepository,
        private readonly PuzzleProgressRepositoryInterface $progressRepository,
    ) {}

    public function __invoke(RecordProgressCommand $command): void
    {
        $puzzleId = new PuzzleId($command->puzzleUuid);
        $userId = new UserId($command->userUuid);

        $puzzle = $this->puzzleRepository->findByUuid($puzzleId);

        if ($puzzle === null) {
            throw new PuzzleNotFoundException();
        }

        $progress = $this->progressRepository->findByUserAndPuzzle($userId, $puzzleId);

        if ($progress === null) {
            $progress = PuzzleProgress::start($userId, $puzzleId);
        }

        $progress->record(
            currentFragment: $command->currentFragment,
            piecesPlaced: $command->piecesPlaced,
            timeSpentSeconds: $command->timeSpentSeconds,
            completed: $command->completed,
            piecesPerFragment: $puzzle->getPiecesPerFragment(),
        );

        $this->progressRepository->save($progress);
    }
}
