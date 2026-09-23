<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Persistence\Doctrine\Repository;

use App\Puzzle\Domain\Entity\Puzzle;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePuzzleRepository implements PuzzleRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(Puzzle $puzzle): void
    {
        $this->em->persist($puzzle);
        $this->em->flush();
    }

    public function remove(Puzzle $puzzle): void
    {
        $this->em->remove($puzzle);
        $this->em->flush();
    }

    public function findByUuid(PuzzleId $uuid): ?Puzzle
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Puzzle::class, 'p')
            ->where('p.uuid = :uuid')
            ->setParameter('uuid', $uuid->value())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Puzzle[] */
    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Puzzle::class, 'p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Puzzle[] */
    public function findPaginated(int $offset, int $limit): array
    {
        $qb = $this->buildFilterQuery('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countFiltered(): int
    {
        $qb = $this->buildFilterQuery('p')
            ->select('COUNT(p.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function buildFilterQuery(string $alias): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->em->createQueryBuilder()
            ->select($alias)
            ->from(Puzzle::class, $alias);

        return $qb;
    }
}
