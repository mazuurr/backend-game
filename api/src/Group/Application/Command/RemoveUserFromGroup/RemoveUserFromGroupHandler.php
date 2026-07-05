<?php

declare(strict_types=1);

namespace App\Group\Application\Command\RemoveUserFromGroup;

use App\Group\Domain\Exception\GroupNotFoundException;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RemoveUserFromGroupHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(RemoveUserFromGroupCommand $command): void
    {
        $groupId = new GroupId($command->groupUuid);

        if ($this->groupRepository->findByUuid($groupId) === null) {
            throw new GroupNotFoundException();
        }

        $user = $this->userRepository->findByUuid(new UserId($command->userUuid));

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $user->removeFromGroup();
        $this->userRepository->save($user);
    }
}
