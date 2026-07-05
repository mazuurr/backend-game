<?php

declare(strict_types=1);

namespace App\Group\Domain\Exception;

final class GroupNotFoundException extends \DomainException
{
    public function __construct(string $message = 'Group not found.')
    {
        parent::__construct($message);
    }
}
