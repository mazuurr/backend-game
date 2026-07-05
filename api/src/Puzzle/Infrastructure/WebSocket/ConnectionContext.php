<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\WebSocket;

final class ConnectionContext
{
    public ?string $sessionUuid = null;

    public function __construct(
        public readonly AuthenticatedClient $client,
    ) {}
}
