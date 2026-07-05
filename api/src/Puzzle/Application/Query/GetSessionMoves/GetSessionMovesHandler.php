<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetSessionMoves;

use App\Puzzle\Application\DTO\PieceMoveDTO;
use App\Puzzle\Domain\Exception\PuzzleSessionNotFoundException;
use App\Puzzle\Domain\Repository\PuzzlePieceMoveRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetSessionMovesHandler
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
        private readonly PuzzlePieceMoveRepositoryInterface $moveRepository,
    ) {}

    /** @return PieceMoveDTO[] */
    public function __invoke(GetSessionMovesQuery $query): array
    {
        $sessionId = new PuzzleSessionId($query->sessionUuid);

        if ($this->sessionRepository->findByUuid($sessionId) === null) {
            throw new PuzzleSessionNotFoundException();
        }

        return array_map(
            static fn ($move) => PieceMoveDTO::fromEntity($move),
            $this->moveRepository->findBySession($sessionId),
        );
    }
}
