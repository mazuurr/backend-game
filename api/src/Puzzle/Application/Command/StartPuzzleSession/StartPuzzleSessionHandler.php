<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\StartPuzzleSession;

use App\Group\Domain\ValueObject\GroupId;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Exception\PuzzleNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class StartPuzzleSessionHandler
{
    public function __construct(
        private readonly PuzzleRepositoryInterface $puzzleRepository,
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
    ) {}

    public function __invoke(StartPuzzleSessionCommand $command): void
    {
        $puzzleId = new PuzzleId($command->puzzleUuid);

        if ($this->puzzleRepository->findByUuid($puzzleId) === null) {
            throw new PuzzleNotFoundException();
        }

        $groupId = $command->groupUuid !== null ? new GroupId($command->groupUuid) : null;

        $session = PuzzleSession::start(
            uuid: new PuzzleSessionId($command->sessionUuid),
            puzzleUuid: $puzzleId,
            visibility: $command->visibility,
            groupUuid: $groupId,
            createdByUserUuid: new UserId($command->createdByUserUuid),
        );

        $this->sessionRepository->save($session);
    }
}
