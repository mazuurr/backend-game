<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class InvalidActivationTokenException extends DomainException
{
    public function __construct(string $message = 'Invalid activation token.')
    {
        parent::__construct($message, self::HTTP_BAD_REQUEST);
    }
}
