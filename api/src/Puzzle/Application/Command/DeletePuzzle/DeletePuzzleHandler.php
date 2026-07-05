<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\DeletePuzzle;

use App\Puzzle\Application\Storage\PuzzleStorageInterface;
use App\Puzzle\Domain\Exception\PuzzleNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeletePuzzleHandler
{
    public function __construct(
        private readonly PuzzleRepositoryInterface $puzzleRepository,
        private readonly PuzzleStorageInterface $puzzleStorage,
    ) {}

    public function __invoke(DeletePuzzleCommand $command): void
    {
        $puzzle = $this->puzzleRepository->findByUuid(new PuzzleId($command->uuid));

        if ($puzzle === null) {
            throw new PuzzleNotFoundException();
        }

        $this->puzzleStorage->delete($puzzle->getStoredFilename());
        $this->puzzleRepository->remove($puzzle);
    }
}
