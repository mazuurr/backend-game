<?php

declare(strict_types=1);

namespace App\Group\Application\Command\CreateGroup;

use App\Group\Domain\Entity\Group;
use App\Group\Domain\Exception\GroupNameAlreadyExistsException;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\Group\Domain\ValueObject\GroupName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateGroupHandler
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
    ) {}

    public function __invoke(CreateGroupCommand $command): void
    {
        $name = new GroupName($command->name);

        if ($this->groupRepository->nameExists($name)) {
            throw new GroupNameAlreadyExistsException();
        }

        $group = Group::create(
            uuid: GroupId::generate(),
            name: $name,
            description: $command->description,
        );

        $this->groupRepository->save($group);
    }
}
