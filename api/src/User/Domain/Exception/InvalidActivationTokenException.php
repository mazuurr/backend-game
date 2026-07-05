<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

class InvalidActivationTokenException extends \DomainException
{
    public function __construct(string $message = 'Invalid activation token.')
    {
        parent::__construct($message);
    }
}
