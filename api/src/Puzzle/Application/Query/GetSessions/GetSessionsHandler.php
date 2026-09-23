<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Query\GetSessions;

use App\Puzzle\Application\DTO\SessionDTO;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Shared\Application\DTO\PaginatedResult;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetSessionsHandler
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
    ) {}

    public function __invoke(GetSessionsQuery $query): PaginatedResult
    {
        $offset = ($query->page - 1) * $query->perPage;

        $sessions = $this->sessionRepository->findPaginated($offset, $query->perPage, $query->status);
        $total = $this->sessionRepository->countFiltered($query->status);

        return new PaginatedResult(
            data: array_map(fn ($session) => SessionDTO::fromEntity($session), $sessions),
            page: $query->page,
            perPage: $query->perPage,
            total: $total,
        );
    }
}
