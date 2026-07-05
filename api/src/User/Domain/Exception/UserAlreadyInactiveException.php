<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

class UserAlreadyInactiveException extends \DomainException
{
    public function __construct(string $message = 'User is already inactive.')
    {
        parent::__construct($message);
    }
}
