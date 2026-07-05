<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\DeletePuzzle;

final class DeletePuzzleCommand
{
    public function __construct(
        public readonly string $uuid,
    ) {}
}
