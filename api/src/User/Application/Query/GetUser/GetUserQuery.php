<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUser;

final class GetUserQuery
{
    public function __construct(
        public readonly string $uuid,
    ) {}
}
