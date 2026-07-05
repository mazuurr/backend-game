<?php

declare(strict_types=1);

namespace App\Group\Application\Command\JoinGroup;

final class JoinGroupCommand
{
    public function __construct(
        public readonly string $groupUuid,
        public readonly string $userUuid,
    ) {}
}
