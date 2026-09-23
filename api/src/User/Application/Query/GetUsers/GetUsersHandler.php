<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUsers;

use App\Shared\Application\DTO\PaginatedResult;
use App\User\Application\DTO\UserDTO;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUsersHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(GetUsersQuery $query): PaginatedResult
    {
        $offset = ($query->page - 1) * $query->perPage;

        $users = $this->userRepository->findPaginated(
            $offset,
            $query->perPage,
            $query->active,
            $query->search,
        );

        $total = $this->userRepository->countFiltered(
            $query->active,
            $query->search,
        );

        return new PaginatedResult(
            data: array_map(fn ($user) => UserDTO::fromEntity($user), $users),
            page: $query->page,
            perPage: $query->perPage,
            total: $total,
        );
    }
}
