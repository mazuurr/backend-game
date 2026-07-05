<?php

declare(strict_types=1);

namespace App\Group\Application\Command\TransferOwnership;

use App\Group\Domain\Exception\GroupNotFoundException;
use App\Group\Domain\Exception\NotGroupOwnerException;
use App\Group\Domain\Exception\UserNotInGroupException;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class TransferOwnershipHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(TransferOwnershipCommand $command): void
    {
        $group = $this->groupRepository->findByUuid(new GroupId($command->groupUuid));

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        if (!$group->isOwnedBy(new UserId($command->requesterUuid))) {
            throw new NotGroupOwnerException();
        }

        $newOwner = $this->userRepository->findByUuid(new UserId($command->newOwnerUuid));

        if ($newOwner === null) {
            throw new UserNotFoundException();
        }

        $groupId = new GroupId($command->groupUuid);
        if ($newOwner->getGroupId() === null || !$newOwner->getGroupId()->equals($groupId)) {
            throw new UserNotInGroupException();
        }

        $group->transferOwnership(new UserId($command->newOwnerUuid));
        $this->groupRepository->save($group);
    }
}
