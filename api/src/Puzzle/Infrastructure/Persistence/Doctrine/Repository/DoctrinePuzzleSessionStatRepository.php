<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Persistence\Doctrine\Repository;

use App\Puzzle\Domain\Entity\PuzzleSessionStat;
use App\Puzzle\Domain\Repository\PuzzleSessionStatRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Shared\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class DoctrinePuzzleSessionStatRepository implements PuzzleSessionStatRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function insertIfMissing(PuzzleSessionStat $stat): void
    {
        // Raw INSERT IGNORE instead of ORM persist()+flush(): the unique index on
        // session_uuid (migration Version20260921000004) makes this atomic and
        // silently idempotent under a race, with no exception to catch and no
        // risk of aborting the surrounding doctrine_transaction on a duplicate.
        $this->em->getConnection()->executeStatement(
            'INSERT IGNORE INTO puzzle_session_stats
                (session_uuid, puzzle_uuid, created_by_user_uuid, mode, total_pieces, correct_pieces, time_spent_seconds, started_at, finished_at, recorded_at)
             VALUES
                (:sessionUuid, :puzzleUuid, :createdByUserUuid, :mode, :totalPieces, :correctPieces, :timeSpentSeconds, :startedAt, :finishedAt, :recordedAt)',
            [
                'sessionUuid' => $stat->getSessionUuid()->value(),
                'puzzleUuid' => $stat->getPuzzleUuid()->value(),
                'createdByUserUuid' => $stat->getCreatedByUserUuid()->value(),
                'mode' => $stat->getMode(),
                'totalPieces' => $stat->getTotalPieces(),
                'correctPieces' => $stat->getCorrectPieces(),
                'timeSpentSeconds' => $stat->getTimeSpentSeconds(),
                'startedAt' => $stat->getStartedAt()->format('Y-m-d H:i:s'),
                'finishedAt' => $stat->getFinishedAt()->format('Y-m-d H:i:s'),
                'recordedAt' => $stat->getRecordedAt()->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function hasCompletedPuzzle(UserId $userUuid, PuzzleId $puzzleUuid): bool
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(PuzzleSessionStat::class, 's')
            ->where('s.createdByUserUuid = :userUuid')
            ->andWhere('s.puzzleUuid = :puzzleUuid')
            ->andWhere('s.totalPieces > 0')
            ->andWhere('s.correctPieces >= s.totalPieces')
            ->setParameter('userUuid', $userUuid->value())
            ->setParameter('puzzleUuid', $puzzleUuid->value())
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $count) > 0;
    }

    /** @return PuzzleSessionStat[] */
    public function findPaginated(int $offset, int $limit): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(PuzzleSessionStat::class, 's')
            ->orderBy('s.recordedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(PuzzleSessionStat::class, 's')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return PuzzleSessionStat[] */
    public function findPaginatedByUser(UserId $userUuid, int $offset, int $limit, ?PuzzleId $puzzleUuid = null): array
    {
        return $this->byUserQuery($userUuid, $puzzleUuid)
            ->orderBy('s.recordedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByUser(UserId $userUuid, ?PuzzleId $puzzleUuid = null): int
    {
        return (int) $this->byUserQuery($userUuid, $puzzleUuid)
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function byUserQuery(UserId $userUuid, ?PuzzleId $puzzleUuid): QueryBuilder
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from(PuzzleSessionStat::class, 's')
            ->where('s.createdByUserUuid = :userUuid')
            ->setParameter('userUuid', $userUuid->value());

        if ($puzzleUuid !== null) {
            $qb->andWhere('s.puzzleUuid = :puzzleUuid')->setParameter('puzzleUuid', $puzzleUuid->value());
        }

        return $qb;
    }
}
