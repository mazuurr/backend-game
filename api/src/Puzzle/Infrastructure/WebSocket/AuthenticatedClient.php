<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\WebSocket;

final class AuthenticatedClient
{
    public function __construct(
        public readonly string $userUuid,
        public readonly string $email,
        public readonly ?string $groupUuid,
    ) {}
}
