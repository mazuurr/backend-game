<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class PuzzleSessionNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Puzzle session not found.', self::HTTP_NOT_FOUND);
    }
}
