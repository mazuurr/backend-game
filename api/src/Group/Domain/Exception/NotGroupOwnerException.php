<?php

declare(strict_types=1);

namespace App\Group\Domain\Exception;

final class NotGroupOwnerException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('You are not the owner of this group.');
    }
}
