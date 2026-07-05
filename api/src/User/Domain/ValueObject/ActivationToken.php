<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

final class ActivationToken
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Activation token cannot be empty.');
        }
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(32)));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(string $token): bool
    {
        return hash_equals($this->value, $token);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
