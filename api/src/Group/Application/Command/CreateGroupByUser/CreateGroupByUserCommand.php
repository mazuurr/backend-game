<?php

declare(strict_types=1);

namespace App\Group\Application\Command\CreateGroupByUser;

final class CreateGroupByUserCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $ownerUuid,
    ) {}
}
