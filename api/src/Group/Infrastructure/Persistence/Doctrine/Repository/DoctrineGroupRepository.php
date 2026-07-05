<?php

declare(strict_types=1);

namespace App\Group\Infrastructure\Persistence\Doctrine\Repository;

use App\Group\Domain\Entity\Group;
use App\Group\Domain\Repository\GroupRepositoryInterface;
use App\Group\Domain\ValueObject\GroupId;
use App\Group\Domain\ValueObject\GroupName;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineGroupRepository implements GroupRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(Group $group): void
    {
        $this->em->persist($group);
        $this->em->flush();
    }

    public function remove(Group $group): void
    {
        $this->em->remove($group);
        $this->em->flush();
    }

    public function findByUuid(GroupId $uuid): ?Group
    {
        return $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.uuid = :uuid')
            ->setParameter('uuid', $uuid->value())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Group[] */
    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Group[] */
    public function findPaginated(int $offset, int $limit, ?string $search): array
    {
        $qb = $this->buildFilterQuery('g', $search)
            ->orderBy('g.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countFiltered(?string $search): int
    {
        $qb = $this->buildFilterQuery('g', $search)
            ->select('COUNT(g.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function buildFilterQuery(string $alias, ?string $search): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->em->createQueryBuilder()
            ->select($alias)
            ->from(Group::class, $alias);

        if ($search !== null && $search !== '') {
            $qb->andWhere("$alias.name LIKE :search")
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb;
    }

    public function nameExists(GroupName $name): bool
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(g.id)')
            ->from(Group::class, 'g')
            ->where('g.name = :name')
            ->setParameter('name', $name->value())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function nameExistsExcluding(GroupName $name, GroupId $excludeUuid): bool
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(g.id)')
            ->from(Group::class, 'g')
            ->where('g.name = :name')
            ->andWhere('g.uuid != :uuid')
            ->setParameter('name', $name->value())
            ->setParameter('uuid', $excludeUuid->value())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
