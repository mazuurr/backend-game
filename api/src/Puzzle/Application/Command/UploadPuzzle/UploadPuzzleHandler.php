<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\UploadPuzzle;

use App\Puzzle\Application\Storage\PuzzleStorageInterface;
use App\Puzzle\Domain\Entity\Puzzle;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PieceGrid;
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

        $totalPieces = $command->totalPieces;
        $piecesX = $command->piecesX;
        $piecesY = $command->piecesY;

        if ($totalPieces !== null && $totalPieces > 0) {
            [$width, $height] = $this->readImageDimensions($command->tmpPath);
            $grid = PieceGrid::fromTotalPieces($width, $height, $totalPieces);
            $piecesX = $grid->x;
            $piecesY = $grid->y;
            $totalPieces = $grid->total();
        }

        $this->puzzleStorage->store($command->tmpPath, $storedFilename);

        $puzzle = Puzzle::create(
            uuid: $uuid,
            originalName: $command->originalName,
            storedFilename: $storedFilename,
            mimeType: $command->mimeType,
            size: $command->size,
            difficulty: $command->difficulty,
            totalPieces: $totalPieces,
            piecesPerFragment: $command->piecesPerFragment,
            piecesX: $piecesX,
            piecesY: $piecesY,
        );

        $this->puzzleRepository->save($puzzle);
    }

    /**
     * @return array{0: int, 1: int} [width, height] in pixels, [0, 0] when unreadable.
     */
    private function readImageDimensions(string $path): array
    {
        $info = @getimagesize($path);

        if ($info === false) {
            return [0, 0];
        }

        return [(int) $info[0], (int) $info[1]];
    }
}
