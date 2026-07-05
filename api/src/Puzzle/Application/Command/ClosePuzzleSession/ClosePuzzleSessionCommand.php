<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\ClosePuzzleSession;

final class ClosePuzzleSessionCommand
{
    public function __construct(
        public readonly string $sessionUuid,
        public readonly ?string $requestedByUserUuid = null,
    ) {}
}
