<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Persistence\Doctrine\Repository;

use App\Puzzle\Domain\Entity\PuzzlePieceMove;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\Shared\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePuzzleSessionRepository implements PuzzleSessionRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(PuzzleSession $session): void
    {
        $this->em->persist($session);
        $this->em->flush();
    }

    public function delete(PuzzleSession $session): void
    {
        $this->em->remove($session);
        $this->em->flush();
    }

    public function findByUuid(PuzzleSessionId $uuid): ?PuzzleSession
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(PuzzleSession::class, 's')
            ->where('s.uuid = :uuid')
            ->setParameter('uuid', $uuid->value())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return PuzzleSession[] */
    public function findOpen(): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(PuzzleSession::class, 's')
            ->where('s.status = :status')
            ->setParameter('status', PuzzleSession::STATUS_OPEN)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return PuzzleSession[] */
    public function findByUser(UserId $userUuid, ?string $mode = null, ?string $status = null): array
    {
        $playedSessionUuidsDql = $this->em->createQueryBuilder()
            ->select('m.sessionUuid')
            ->from(PuzzlePieceMove::class, 'm')
            ->where('m.userUuid = :userUuid')
            ->getDQL();

        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from(PuzzleSession::class, 's')
            ->where('s.createdByUserUuid = :userUuid')
            ->orWhere(sprintf('s.uuid IN (%s)', $playedSessionUuidsDql))
            ->setParameter('userUuid', $userUuid);

        if ($mode !== null) {
            $qb->andWhere('s.mode = :mode')->setParameter('mode', $mode);
        }

        if ($status !== null) {
            $qb->andWhere('s.status = :status')->setParameter('status', $status);
        }

        return $qb->orderBy('s.createdAt', 'DESC')->getQuery()->getResult();
    }

    /** @return PuzzleSession[] */
    public function findExpiredOpen(\DateTimeImmutable $now): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(PuzzleSession::class, 's')
            ->where('s.status = :status')
            ->andWhere('s.expiresAt <= :now')
            ->setParameter('status', PuzzleSession::STATUS_OPEN)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /** @return PuzzleSession[] */
    public function findClosedBefore(\DateTimeImmutable $threshold): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(PuzzleSession::class, 's')
            ->where('s.status = :status')
            ->andWhere('s.closedAt < :threshold')
            ->setParameter('status', PuzzleSession::STATUS_CLOSED)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    /** @return PuzzleSession[] */
    public function findPaginated(int $offset, int $limit, ?string $status): array
    {
        return $this->buildFilterQuery($status)
            ->orderBy('s.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countFiltered(?string $status): int
    {
        return (int) $this->buildFilterQuery($status)
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildFilterQuery(?string $status): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from(PuzzleSession::class, 's');

        if ($status !== null) {
            $qb->andWhere('s.status = :status')->setParameter('status', $status);
        }

        return $qb;
    }
}
