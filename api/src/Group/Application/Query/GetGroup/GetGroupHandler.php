<?php

declare(strict_types=1);

namespace App\Group\Application\Query\GetGroup;

use App\Group\Application\DTO\GroupDTO;
use App\Group\Domain\Exception\GroupNotFoundException;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\User\Application\DTO\UserDTO;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetGroupHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(GetGroupQuery $query): GroupDTO
    {
        $uuid = new GroupId($query->uuid);
        $group = $this->groupRepository->findByUuid($uuid);

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $users = array_map(
            static fn ($user) => UserDTO::fromEntity($user),
            $this->userRepository->findByGroupId($uuid),
        );

        return GroupDTO::fromEntity($group, membersCount: count($users), users: $users);
    }
}
