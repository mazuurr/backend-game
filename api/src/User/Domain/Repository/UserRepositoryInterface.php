<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\Group\Domain\ValueObject\GroupId;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function remove(User $user): void;

    public function findByUuid(UserId $uuid): ?User;

    public function findByEmail(Email $email): ?User;

    public function findByActivationToken(string $token): ?User;

    /** @return User[] */
    public function findAll(): array;

    /** @return User[] */
    public function findPaginated(int $offset, int $limit, ?bool $active, ?bool $premium, ?string $search): array;

    public function countFiltered(?bool $active, ?bool $premium, ?string $search): int;

    public function emailExists(Email $email): bool;

    public function usernameExists(string $username): bool;

    /** @return User[] */
    public function findByGroupId(GroupId $groupId): array;

    public function countByGroupId(GroupId $groupId): int;

    public function findByResetToken(string $token): ?User;
}
