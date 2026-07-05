<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\ActivationToken;

final class UserCreatedEvent
{
    public function __construct(
        public readonly UserId $userId,
        public readonly Email $email,
        public readonly ActivationToken $activationToken,
    ) {}
}
