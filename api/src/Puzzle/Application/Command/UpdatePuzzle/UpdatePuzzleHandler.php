<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\UpdatePuzzle;

use App\Puzzle\Domain\Exception\PuzzleNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdatePuzzleHandler
{
    public function __construct(
        private readonly PuzzleRepositoryInterface $puzzleRepository,
    ) {}

    public function __invoke(UpdatePuzzleCommand $command): void
    {
        $puzzle = $this->puzzleRepository->findByUuid(new PuzzleId($command->uuid));

        if ($puzzle === null) {
            throw new PuzzleNotFoundException();
        }

        if ($command->hasDifficulty) {
            $puzzle->changeDifficulty($command->difficulty);
        }

        if ($command->hasTotalPieces) {
            $puzzle->changeTotalPieces($command->totalPieces);
        }

        if ($command->hasPiecesPerFragment) {
            $puzzle->changePiecesPerFragment($command->piecesPerFragment);
        }

        if ($command->hasPiecesX) {
            $puzzle->changePiecesX($command->piecesX);
        }

        if ($command->hasPiecesY) {
            $puzzle->changePiecesY($command->piecesY);
        }

        $this->puzzleRepository->save($puzzle);
    }
}
