<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\StartPuzzleSession;

use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Exception\PuzzleAlreadyCompletedException;
use App\Puzzle\Domain\Exception\PuzzleNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleSessionStatRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class StartPuzzleSessionHandler
{
    public function __construct(
        private readonly PuzzleRepositoryInterface $puzzleRepository,
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
        private readonly PuzzleSessionStatRepositoryInterface $statRepository,
    ) {}

    public function __invoke(StartPuzzleSessionCommand $command): void
    {
        $puzzleId = new PuzzleId($command->puzzleUuid);

        if ($this->puzzleRepository->findByUuid($puzzleId) === null) {
            throw new PuzzleNotFoundException();
        }

        $createdByUserUuid = new UserId($command->createdByUserUuid);

        if ($this->statRepository->hasCompletedPuzzle($createdByUserUuid, $puzzleId)) {
            throw new PuzzleAlreadyCompletedException();
        }

        $session = PuzzleSession::start(
            uuid: new PuzzleSessionId($command->sessionUuid),
            puzzleUuid: $puzzleId,
            visibility: $command->visibility,
            createdByUserUuid: $createdByUserUuid,
            mode: $command->mode,
        );

        $this->sessionRepository->save($session);
    }
}
