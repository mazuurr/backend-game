<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\UploadPuzzle;

use App\Puzzle\Application\Storage\PuzzleStorageInterface;
use App\Puzzle\Domain\Entity\Puzzle;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UploadPuzzleHandler
{
    public function __construct(
        private readonly PuzzleRepositoryInterface $puzzleRepository,
        private readonly PuzzleStorageInterface $puzzleStorage,
    ) {}

    public function __invoke(UploadPuzzleCommand $command): void
    {
        $uuid = new PuzzleId($command->uuid);

        $extension = pathinfo($command->originalName, PATHINFO_EXTENSION);
        $storedFilename = $command->uuid . ($extension !== '' ? '.' . $extension : '');

        $this->puzzleStorage->store($command->tmpPath, $storedFilename);

        $puzzle = Puzzle::create(
            uuid: $uuid,
            originalName: $command->originalName,
            storedFilename: $storedFilename,
            mimeType: $command->mimeType,
            size: $command->size,
            difficulty: $command->difficulty,
            totalPieces: $command->totalPieces,
            piecesPerFragment: $command->piecesPerFragment,
            piecesX: $command->piecesX,
            piecesY: $command->piecesY,
        );

        $this->puzzleRepository->save($puzzle);
    }
}
