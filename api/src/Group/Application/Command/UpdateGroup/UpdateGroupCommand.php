<?php

declare(strict_types=1);

namespace App\Group\Application\Command\UpdateGroup;

final class UpdateGroupCommand
{
    public function __construct(
        public readonly string $uuid,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly bool $clearDescription = false,
    ) {}
}
