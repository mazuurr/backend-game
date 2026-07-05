<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUsers;

final class GetUsersQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly ?bool $active = null,
        public readonly ?bool $premium = null,
        public readonly ?string $search = null,
    ) {}
}
