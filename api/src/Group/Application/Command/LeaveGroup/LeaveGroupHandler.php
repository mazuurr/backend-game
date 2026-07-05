<?php

declare(strict_types=1);

namespace App\Group\Application\Command\LeaveGroup;

use App\Group\Domain\Exception\GroupNotFoundException;
use App\Group\Domain\Exception\OwnerCannotLeaveGroupException;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class LeaveGroupHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly GroupRepositoryInterface $groupRepository,
    ) {}

    public function __invoke(LeaveGroupCommand $command): void
    {
        $user = $this->userRepository->findByUuid(new UserId($command->userUuid));

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $groupId = $user->getGroupId();

        if ($groupId === null) {
            throw new GroupNotFoundException('You do not belong to any group.');
        }

        $group = $this->groupRepository->findByUuid($groupId);

        if ($group !== null && $group->isOwnedBy(new UserId($command->userUuid))) {
            throw new OwnerCannotLeaveGroupException();
        }

        $user->removeFromGroup();
        $this->userRepository->save($user);
    }
}
