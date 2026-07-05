<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\ClosePuzzleSession;

use App\Puzzle\Domain\Exception\NotSessionOwnerException;
use App\Puzzle\Domain\Exception\PuzzleSessionNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ClosePuzzleSessionHandler
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
    ) {}

    public function __invoke(ClosePuzzleSessionCommand $command): void
    {
        $session = $this->sessionRepository->findByUuid(new PuzzleSessionId($command->sessionUuid));

        if ($session === null) {
            throw new PuzzleSessionNotFoundException();
        }

        // A null requester means a system action (e.g. the auto-expiry cron).
        if ($command->requestedByUserUuid !== null
            && !$session->getCreatedByUserUuid()->equals(new UserId($command->requestedByUserUuid))
        ) {
            throw new NotSessionOwnerException();
        }

        $session->close();
        $this->sessionRepository->save($session);
    }
}
