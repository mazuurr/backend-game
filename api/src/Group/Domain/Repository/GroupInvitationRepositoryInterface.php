<?php

declare(strict_types=1);

namespace App\Group\Domain\Repository;

use App\Group\Domain\Entity\GroupInvitation;
use App\Group\Domain\ValueObject\GroupId;
use App\Group\Domain\ValueObject\GroupInvitationId;
use App\User\Domain\ValueObject\UserId;

interface GroupInvitationRepositoryInterface
{
    public function save(GroupInvitation $invitation): void;

    public function findByUuid(GroupInvitationId $uuid): ?GroupInvitation;

    /** @return GroupInvitation[] */
    public function findByUser(UserId $userUuid): array;

    /** @return GroupInvitation[] */
    public function findByGroup(GroupId $groupUuid): array;

    public function findPendingByUserAndGroup(UserId $userUuid, GroupId $groupUuid, string $type): ?GroupInvitation;
}
