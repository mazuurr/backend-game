<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetBoardState;

use App\Puzzle\Application\DTO\BoardPieceDTO;
use App\Puzzle\Domain\Exception\PuzzleSessionNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleBoardPieceRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetBoardStateHandler
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
        private readonly PuzzleBoardPieceRepositoryInterface $boardRepository,
    ) {}

    /** @return BoardPieceDTO[] */
    public function __invoke(GetBoardStateQuery $query): array
    {
        $sessionId = new PuzzleSessionId($query->sessionUuid);

        if ($this->sessionRepository->findByUuid($sessionId) === null) {
            throw new PuzzleSessionNotFoundException();
        }

        return array_map(
            static fn ($piece) => BoardPieceDTO::fromEntity($piece),
            $this->boardRepository->findBySession($sessionId),
        );
    }
}
