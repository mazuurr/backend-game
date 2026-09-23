<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidResetTokenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid or expired password reset token.', self::HTTP_BAD_REQUEST);
    }
}
