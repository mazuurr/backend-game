<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

final class HashedPassword
{
    private string $value;

    private function __construct(string $hashedValue)
    {
        $this->value = $hashedValue;
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public static function fromPlain(string $plainPassword): self
    {
        if (mb_strlen($plainPassword) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long.');
        }

        return new self(password_hash($plainPassword, PASSWORD_ARGON2ID));
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
