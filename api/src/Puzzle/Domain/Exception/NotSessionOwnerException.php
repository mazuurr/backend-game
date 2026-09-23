<?php

declare(strict_types=1);

namespace App\Puzzle\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class NotSessionOwnerException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Only the session owner can perform this action.', self::HTTP_FORBIDDEN);
    }
}
