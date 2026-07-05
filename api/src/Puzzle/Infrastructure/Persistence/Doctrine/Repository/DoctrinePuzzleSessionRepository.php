<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Persistence\Doctrine\Repository;

use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
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
}
