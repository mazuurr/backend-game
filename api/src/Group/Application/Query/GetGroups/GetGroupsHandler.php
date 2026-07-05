<?php

declare(strict_types=1);

namespace App\Group\Application\Query\GetGroups;

use App\Group\Application\DTO\GroupDTO;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Shared\Application\DTO\PaginatedResult;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetGroupsHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(GetGroupsQuery $query): PaginatedResult
    {
        $offset = ($query->page - 1) * $query->perPage;

        $groups = $this->groupRepository->findPaginated($offset, $query->perPage, $query->search);
        $total = $this->groupRepository->countFiltered($query->search);

        return new PaginatedResult(
            data: array_map(
                fn ($group) => GroupDTO::fromEntity(
                    $group,
                    membersCount: $this->userRepository->countByGroupId($group->getUuid()),
                ),
                $groups,
            ),
            page: $query->page,
            perPage: $query->perPage,
            total: $total,
        );
    }
}
