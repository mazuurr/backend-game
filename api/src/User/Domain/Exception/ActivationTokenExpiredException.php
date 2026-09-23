<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class ActivationTokenExpiredException extends DomainException
{
    public function __construct(string $message = 'Activation token has expired.')
    {
        parent::__construct($message, self::HTTP_BAD_REQUEST);
    }
}
