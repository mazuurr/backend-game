<?php

declare(strict_types=1);

namespace App\Group\Application\Query\GetMyGroupInvitations;

final class GetMyGroupInvitationsQuery
{
    public function __construct(
        public readonly string $userUuid,
    ) {}
}
