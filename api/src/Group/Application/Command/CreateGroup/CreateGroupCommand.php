<?php

declare(strict_types=1);

namespace App\Group\Application\Command\CreateGroup;

final class CreateGroupCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
    ) {}
}
