<?php

declare(strict_types=1);

namespace App\Group\Application\Command\CreateGroupByUser;

use App\Group\Domain\Entity\Group;
use App\Group\Domain\Exception\GroupNameAlreadyExistsException;
use App\Group\Domain\Exception\UserAlreadyInGroupException;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\Group\Domain\ValueObject\GroupName;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateGroupByUserHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(CreateGroupByUserCommand $command): void
    {
        $ownerUuid = new UserId($command->ownerUuid);
        $owner = $this->userRepository->findByUuid($ownerUuid);

        if ($owner === null) {
            throw new UserNotFoundException();
        }

        if ($owner->getGroupId() !== null) {
            throw new UserAlreadyInGroupException();
        }

        $name = new GroupName($command->name);

        if ($this->groupRepository->nameExists($name)) {
            throw new GroupNameAlreadyExistsException();
        }

        $groupId = GroupId::generate();

        $group = Group::create(
            uuid: $groupId,
            name: $name,
            description: $command->description,
            ownerUuid: $ownerUuid,
        );

        $this->groupRepository->save($group);

        $owner->assignToGroup($groupId);
        $this->userRepository->save($owner);
    }
}
