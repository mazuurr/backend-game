<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class InvalidResetTokenException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid or expired password reset token.');
    }
}
