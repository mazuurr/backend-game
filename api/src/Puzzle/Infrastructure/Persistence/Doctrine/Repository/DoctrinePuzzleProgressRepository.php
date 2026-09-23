<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Persistence\Doctrine\Repository;

use App\Puzzle\Domain\Entity\PuzzleProgress;
use App\Puzzle\Domain\Repository\PuzzleProgressRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleId;
use App\Shared\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePuzzleProgressRepository implements PuzzleProgressRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(PuzzleProgress $progress): void
    {
        $this->em->persist($progress);
        $this->em->flush();
    }

    public function findByUserAndPuzzle(UserId $userUuid, PuzzleId $puzzleUuid): ?PuzzleProgress
    {
        return $this->em->createQueryBuilder()
            ->select('pp')
            ->from(PuzzleProgress::class, 'pp')
            ->where('pp.userUuid = :userUuid')
            ->andWhere('pp.puzzleUuid = :puzzleUuid')
            ->setParameter('userUuid', $userUuid->value())
            ->setParameter('puzzleUuid', $puzzleUuid->value())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return PuzzleProgress[] */
    public function findByUser(UserId $userUuid): array
    {
        return $this->em->createQueryBuilder()
            ->select('pp')
            ->from(PuzzleProgress::class, 'pp')
            ->where('pp.userUuid = :userUuid')
            ->setParameter('userUuid', $userUuid->value())
            ->orderBy('pp.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return PuzzleProgress[] */
    public function findByPuzzle(PuzzleId $puzzleUuid): array
    {
        return $this->em->createQueryBuilder()
            ->select('pp')
            ->from(PuzzleProgress::class, 'pp')
            ->where('pp.puzzleUuid = :puzzleUuid')
            ->setParameter('puzzleUuid', $puzzleUuid->value())
            ->orderBy('pp.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
