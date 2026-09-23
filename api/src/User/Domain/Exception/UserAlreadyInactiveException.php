<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class UserAlreadyInactiveException extends DomainException
{
    public function __construct(string $message = 'User is already inactive.')
    {
        parent::__construct($message, self::HTTP_CONFLICT);
    }
}
