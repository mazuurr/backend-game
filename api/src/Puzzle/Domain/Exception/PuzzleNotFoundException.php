<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Exception;

final class PuzzleNotFoundException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Puzzle not found.');
    }
}
