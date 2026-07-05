<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

class ActivationTokenExpiredException extends \DomainException
{
    public function __construct(string $message = 'Activation token has expired.')
    {
        parent::__construct($message);
    }
}
