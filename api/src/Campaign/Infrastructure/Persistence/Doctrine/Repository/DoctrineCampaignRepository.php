<?php

declare(strict_types=1);

namespace App\Campaign\Infrastructure\Persistence\Doctrine\Repository;

use App\Campaign\Domain\Entity\Campaign;
use App\Campaign\Domain\Repository\CampaignRepositoryInterface;
use App\Campaign\Domain\ValueObject\CampaignId;
use App\Campaign\Domain\ValueObject\CampaignName;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineCampaignRepository implements CampaignRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(Campaign $campaign): void
    {
        $this->em->persist($campaign);
        $this->em->flush();
    }

    public function remove(Campaign $campaign): void
    {
        $this->em->remove($campaign);
        $this->em->flush();
    }

    public function findByUuid(CampaignId $uuid): ?Campaign
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Campaign::class, 'c')
            ->where('c.uuid = :uuid')
            ->setParameter('uuid', $uuid->value())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Campaign[] */
    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Campaign::class, 'c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Campaign[] */
    public function findPaginated(int $offset, int $limit, ?string $search): array
    {
        $qb = $this->buildFilterQuery('c', $search)
            ->orderBy('c.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countFiltered(?string $search): int
    {
        $qb = $this->buildFilterQuery('c', $search)
            ->select('COUNT(c.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function buildFilterQuery(string $alias, ?string $search): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->em->createQueryBuilder()
            ->select($alias)
            ->from(Campaign::class, $alias);

        if ($search !== null && $search !== '') {
            $qb->andWhere("$alias.name LIKE :search")
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb;
    }

    public function nameExists(CampaignName $name): bool
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Campaign::class, 'c')
            ->where('c.name = :name')
            ->setParameter('name', $name->value())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function nameExistsExcluding(CampaignName $name, CampaignId $excludeUuid): bool
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Campaign::class, 'c')
            ->where('c.name = :name')
            ->andWhere('c.uuid != :uuid')
            ->setParameter('name', $name->value())
            ->setParameter('uuid', $excludeUuid->value())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
