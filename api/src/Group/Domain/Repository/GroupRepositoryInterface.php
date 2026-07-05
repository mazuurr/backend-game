<?php

declare(strict_types=1);

namespace App\Group\Domain\Repository;

use App\Group\Domain\Entity\Group;
use App\Group\Domain\ValueObject\GroupId;
use App\Group\Domain\ValueObject\GroupName;

interface GroupRepositoryInterface
{
    public function save(Group $group): void;

    public function remove(Group $group): void;

    public function findByUuid(GroupId $uuid): ?Group;

    /** @return Group[] */
    public function findAll(): array;

    /** @return Group[] */
    public function findPaginated(int $offset, int $limit, ?string $search): array;

    public function countFiltered(?string $search): int;

    public function nameExists(GroupName $name): bool;

    public function nameExistsExcluding(GroupName $name, GroupId $excludeUuid): bool;
}
