<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetSession;

use App\Puzzle\Application\DTO\SessionDTO;
use App\Puzzle\Domain\Exception\PuzzleSessionNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetSessionHandler
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
    ) {}

    public function __invoke(GetSessionQuery $query): SessionDTO
    {
        $session = $this->sessionRepository->findByUuid(new PuzzleSessionId($query->sessionUuid));

        if ($session === null) {
            throw new PuzzleSessionNotFoundException();
        }

        return SessionDTO::fromEntity($session);
    }
}
