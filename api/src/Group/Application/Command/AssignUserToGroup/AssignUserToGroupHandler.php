<?php

declare(strict_types=1);

namespace App\Group\Application\Command\AssignUserToGroup;

use App\Group\Domain\Exception\GroupNotFoundException;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AssignUserToGroupHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(AssignUserToGroupCommand $command): void
    {
        $groupId = new GroupId($command->groupUuid);
        $group = $this->groupRepository->findByUuid($groupId);

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $user = $this->userRepository->findByUuid(new UserId($command->userUuid));

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $user->assignToGroup($groupId);
        $this->userRepository->save($user);
    }
}
