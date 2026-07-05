<?php

declare(strict_types=1);

namespace App\Group\Application\Command\DeleteGroupByUser;

use App\Group\Domain\Exception\GroupNotFoundException;
use App\Group\Domain\Exception\NotGroupOwnerException;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteGroupByUserHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(DeleteGroupByUserCommand $command): void
    {
        $groupId = new GroupId($command->groupUuid);
        $group = $this->groupRepository->findByUuid($groupId);

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        if (!$group->isOwnedBy(new UserId($command->requesterUuid))) {
            throw new NotGroupOwnerException();
        }

        foreach ($this->userRepository->findByGroupId($groupId) as $user) {
            $user->removeFromGroup();
            $this->userRepository->save($user);
        }

        $this->groupRepository->remove($group);
    }
}
