<?php

declare(strict_types=1);

namespace App\Group\Application\Query\GetGroupInvitations;

final class GetGroupInvitationsQuery
{
    public function __construct(
        public readonly string $groupUuid,
    ) {}
}
