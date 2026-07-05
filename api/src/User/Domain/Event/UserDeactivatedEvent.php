<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\User\Domain\ValueObject\UserId;

final class UserDeactivatedEvent
{
    public function __construct(
        public readonly UserId $userId,
    ) {}
}
