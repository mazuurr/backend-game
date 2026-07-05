<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Group\Domain\ValueObject\GroupId;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    public function remove(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }

    public function findByUuid(UserId $uuid): ?User
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.uuid = :uuid')
            ->setParameter('uuid', $uuid->value())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.email = :email')
            ->setParameter('email', $email->value())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByActivationToken(string $token): ?User
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.activationToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return User[] */
    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function emailExists(Email $email): bool
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.email = :email')
            ->setParameter('email', $email->value())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function usernameExists(string $username): bool
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /** @return User[] */
    public function findByGroupId(GroupId $groupId): array
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.groupId = :groupId')
            ->setParameter('groupId', $groupId->value())
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByGroupId(GroupId $groupId): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.groupId = :groupId')
            ->setParameter('groupId', $groupId->value())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return User[] */
    public function findPaginated(int $offset, int $limit, ?bool $active, ?bool $premium, ?string $search): array
    {
        $qb = $this->buildFilterQuery('u', $active, $premium, $search)
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countFiltered(?bool $active, ?bool $premium, ?string $search): int
    {
        $qb = $this->buildFilterQuery('u', $active, $premium, $search)
            ->select('COUNT(u.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function buildFilterQuery(string $alias, ?bool $active, ?bool $premium, ?string $search): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->em->createQueryBuilder()
            ->select($alias)
            ->from(User::class, $alias);

        if ($active !== null) {
            $qb->andWhere("$alias.active = :active")->setParameter('active', $active);
        }
        if ($premium !== null) {
            $qb->andWhere("$alias.premium = :premium")->setParameter('premium', $premium);
        }
        if ($search !== null && $search !== '') {
            $qb->andWhere("$alias.username LIKE :search OR $alias.email LIKE :search")
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb;
    }

    public function findByResetToken(string $token): ?User
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.resetToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
