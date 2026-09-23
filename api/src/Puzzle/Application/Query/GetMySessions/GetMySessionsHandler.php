<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetMySessions;

use App\Puzzle\Application\DTO\SessionDTO;
use App\Puzzle\Application\Service\SessionContributionsCalculator;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetMySessionsHandler
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
        private readonly SessionContributionsCalculator $contributionsCalculator,
    ) {}

    /** @return SessionDTO[] */
    public function __invoke(GetMySessionsQuery $query): array
    {
        $sessions = $this->sessionRepository->findByUser(
            new UserId($query->requesterUserUuid),
            $query->mode,
            $query->status,
        );

        return array_map(
            fn (PuzzleSession $session) => SessionDTO::fromEntityWithContributions(
                $session,
                $this->contributionsCalculator->forSession($session),
            ),
            $sessions,
        );
    }
}
