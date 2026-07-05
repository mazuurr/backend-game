<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\Event\UserCreatedEvent;
use App\User\Domain\Event\UserActivatedEvent;
use App\User\Domain\Event\UserDeactivatedEvent;
use App\User\Domain\Exception\ActivationTokenExpiredException;
use App\User\Domain\Exception\InvalidActivationTokenException;
use App\User\Domain\Exception\InvalidCurrentPasswordException;
use App\User\Domain\Exception\InvalidResetTokenException;
use App\User\Domain\Exception\UserAlreadyActiveException;
use App\User\Domain\Exception\UserAlreadyInactiveException;
use App\Group\Domain\ValueObject\GroupId;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\Username;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\ActivationToken;

class User
{
    private int $id;
    private UserId $uuid;
    private Username $username;
    private Email $email;
    private HashedPassword $password;
    private bool $premium;
    private bool $active;
    private ?ActivationToken $activationToken;
    private ?\DateTimeImmutable $tokenExpiresAt;
    private ?GroupId $groupId;
    private ?string $resetToken;
    private ?\DateTimeImmutable $resetTokenExpiresAt;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    /** @var object[] */
    private array $domainEvents = [];

    private function __construct(
        UserId $uuid,
        Username $username,
        Email $email,
        HashedPassword $password,
        bool $premium,
        bool $active,
        ?ActivationToken $activationToken,
        ?\DateTimeImmutable $tokenExpiresAt,
    ) {
        $this->uuid = $uuid;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->premium = $premium;
        $this->active = $active;
        $this->activationToken = $activationToken;
        $this->tokenExpiresAt = $tokenExpiresAt;
        $this->groupId = null;
        $this->resetToken = null;
        $this->resetTokenExpiresAt = null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
    }

    /**
     * Factory: rejestracja z aplikacji mobilnej (bez autoryzacji).
     * Użytkownik nieaktywny, generowany token aktywacyjny.
     */
    public static function registerFromApp(
        UserId $uuid,
        Email $email,
        HashedPassword $password,
    ): self {
        $username = Username::generateFromEmail($email);
        $activationToken = ActivationToken::generate();
        $tokenExpiresAt = new \DateTimeImmutable('+24 hours');

        $user = new self(
            uuid: $uuid,
            username: $username,
            email: $email,
            password: $password,
            premium: false,
            active: false,
            activationToken: $activationToken,
            tokenExpiresAt: $tokenExpiresAt,
        );

        $user->recordEvent(new UserCreatedEvent($uuid, $email, $activationToken));

        return $user;
    }

    /**
     * Factory: tworzenie przez admina (po tokenie API).
     * Można ustawić wszystkie pola.
     */
    public static function createByAdmin(
        UserId $uuid,
        Username $username,
        Email $email,
        HashedPassword $password,
        bool $premium,
        bool $active,
    ): self {
        return new self(
            uuid: $uuid,
            username: $username,
            email: $email,
            password: $password,
            premium: $premium,
            active: $active,
            activationToken: null,
            tokenExpiresAt: null,
        );
    }

    /**
     * Edycja przez admina — może zmienić wszystko.
     */
    public function updateByAdmin(
        ?Username $username,
        ?Email $email,
        ?HashedPassword $password,
        ?bool $premium,
        ?bool $active,
    ): void {
        if ($username !== null) {
            $this->username = $username;
        }
        if ($email !== null) {
            $this->email = $email;
        }
        if ($password !== null) {
            $this->password = $password;
        }
        if ($premium !== null) {
            $this->premium = $premium;
        }
        if ($active !== null) {
            $this->active = $active;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Edycja przez samego użytkownika (JWT) — ograniczone pola.
     */
    public function updateBySelf(
        ?Username $username,
        ?Email $email,
        ?HashedPassword $password,
    ): void {
        if ($username !== null) {
            $this->username = $username;
        }
        if ($email !== null) {
            $this->email = $email;
        }
        if ($password !== null) {
            $this->password = $password;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Aktywacja przez link z maila.
     */
    public function activateByToken(string $token): void
    {
        if ($this->active) {
            throw new UserAlreadyActiveException();
        }

        if ($this->activationToken === null || !$this->activationToken->equals($token)) {
            throw new InvalidActivationTokenException();
        }

        if ($this->tokenExpiresAt !== null && $this->tokenExpiresAt < new \DateTimeImmutable()) {
            throw new ActivationTokenExpiredException();
        }

        $this->active = true;
        $this->activationToken = null;
        $this->tokenExpiresAt = null;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserActivatedEvent($this->uuid));
    }

    public function changePassword(string $currentPlain, HashedPassword $newPassword): void
    {
        if (!$this->password->verify($currentPlain)) {
            throw new InvalidCurrentPasswordException();
        }
        $this->password = $newPassword;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function requestPasswordReset(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->resetToken = $token;
        $this->resetTokenExpiresAt = new \DateTimeImmutable('+1 hour');
        $this->updatedAt = new \DateTimeImmutable();
        return $token;
    }

    public function resetPasswordByToken(string $token, HashedPassword $newPassword): void
    {
        if ($this->resetToken === null || !hash_equals($this->resetToken, $token)) {
            throw new InvalidResetTokenException();
        }
        if ($this->resetTokenExpiresAt !== null && $this->resetTokenExpiresAt < new \DateTimeImmutable()) {
            throw new InvalidResetTokenException();
        }
        $this->password = $newPassword;
        $this->resetToken = null;
        $this->resetTokenExpiresAt = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function assignToGroup(GroupId $groupId): void
    {
        $this->groupId = $groupId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeFromGroup(): void
    {
        $this->groupId = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Dezaktywacja (JWT = siebie, token = dowolnego).
     */
    public function activateByAdmin(): void
    {
        if ($this->active) {
            throw new UserAlreadyActiveException();
        }
        $this->active = true;
        $this->activationToken = null;
        $this->tokenExpiresAt = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function deactivate(): void
    {
        if (!$this->active) {
            throw new UserAlreadyInactiveException();
        }

        $this->active = false;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserDeactivatedEvent($this->uuid));
    }

    // --- Gettery ---

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): UserId
    {
        return $this->uuid;
    }

    public function getUsername(): Username
    {
        return $this->username;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): HashedPassword
    {
        return $this->password;
    }

    public function isPremium(): bool
    {
        return $this->premium;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getGroupId(): ?GroupId
    {
        return $this->groupId;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function getActivationToken(): ?ActivationToken
    {
        return $this->activationToken;
    }

    public function getTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->tokenExpiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // --- Domain Events ---

    /** @return object[] */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
