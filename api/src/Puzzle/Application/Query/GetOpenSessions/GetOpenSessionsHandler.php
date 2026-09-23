<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetOpenSessions;

use App\Puzzle\Application\DTO\SessionDTO;
use App\Puzzle\Application\Service\SessionContributionsCalculator;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetOpenSessionsHandler
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
        private readonly SessionContributionsCalculator $contributionsCalculator,
    ) {}

    /** @return SessionDTO[] */
    public function __invoke(GetOpenSessionsQuery $query): array
    {
        $visible = $this->sessionRepository->findOpen();

        return array_map(
            fn (PuzzleSession $session) => SessionDTO::fromEntityWithContributions(
                $session,
                $this->contributionsCalculator->forSession($session),
            ),
            array_values($visible),
        );
    }
}
