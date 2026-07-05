<?php

declare(strict_types=1);

namespace App\Group\Application\Command\DeleteGroup;

use App\Group\Domain\Exception\GroupNotFoundException;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteGroupHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(DeleteGroupCommand $command): void
    {
        $uuid = new GroupId($command->uuid);
        $group = $this->groupRepository->findByUuid($uuid);

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        foreach ($this->userRepository->findByGroupId($uuid) as $user) {
            $user->removeFromGroup();
            $this->userRepository->save($user);
        }

        $this->groupRepository->remove($group);
    }
}
