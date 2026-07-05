<?php

declare(strict_types=1);

namespace App\Group\Application\Command\UpdateGroup;

use App\Group\Domain\Exception\GroupNameAlreadyExistsException;
use App\Group\Domain\Exception\GroupNotFoundException;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\Group\Domain\ValueObject\GroupName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateGroupHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
    ) {}

    public function __invoke(UpdateGroupCommand $command): void
    {
        $uuid = new GroupId($command->uuid);
        $group = $this->groupRepository->findByUuid($uuid);

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $name = $command->name !== null ? new GroupName($command->name) : null;

        if ($name !== null && $this->groupRepository->nameExistsExcluding($name, $uuid)) {
            throw new GroupNameAlreadyExistsException();
        }

        $group->update(
            name: $name,
            description: $command->description,
            clearDescription: $command->clearDescription,
        );

        $this->groupRepository->save($group);
    }
}
