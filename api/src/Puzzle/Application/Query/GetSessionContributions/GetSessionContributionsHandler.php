<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetSessionContributions;

use App\Puzzle\Application\DTO\SessionContributionsDTO;
use App\Puzzle\Application\Service\SessionContributionsCalculator;
use App\Puzzle\Domain\Exception\PuzzleSessionNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetSessionContributionsHandler
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
        private readonly SessionContributionsCalculator $calculator,
    ) {}

    public function __invoke(GetSessionContributionsQuery $query): SessionContributionsDTO
    {
        $session = $this->sessionRepository->findByUuid(new PuzzleSessionId($query->sessionUuid));

        if ($session === null) {
            throw new PuzzleSessionNotFoundException();
        }

        return $this->calculator->forSession($session);
    }
}
