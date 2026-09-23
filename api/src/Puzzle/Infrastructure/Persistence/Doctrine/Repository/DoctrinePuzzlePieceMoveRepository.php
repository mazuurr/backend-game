<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Persistence\Doctrine\Repository;

use App\Puzzle\Domain\Entity\PuzzlePieceMove;
use App\Puzzle\Domain\Repository\PuzzlePieceMoveRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePuzzlePieceMoveRepository implements PuzzlePieceMoveRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(PuzzlePieceMove $move): void
    {
        $this->em->persist($move);
        $this->em->flush();
    }

    public function nextSeq(PuzzleSessionId $sessionUuid): int
    {
        $max = $this->em->createQueryBuilder()
            ->select('MAX(m.seq)')
            ->from(PuzzlePieceMove::class, 'm')
            ->where('m.sessionUuid = :sessionUuid')
            ->setParameter('sessionUuid', $sessionUuid->value())
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    /** @return PuzzlePieceMove[] */
    public function findBySession(PuzzleSessionId $sessionUuid): array
    {
        return $this->em->createQueryBuilder()
            ->select('m')
            ->from(PuzzlePieceMove::class, 'm')
            ->where('m.sessionUuid = :sessionUuid')
            ->setParameter('sessionUuid', $sessionUuid->value())
            ->orderBy('m.seq', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteBySession(PuzzleSessionId $sessionUuid): void
    {
        $this->em->createQueryBuilder()
            ->delete(PuzzlePieceMove::class, 'm')
            ->where('m.sessionUuid = :sessionUuid')
            ->setParameter('sessionUuid', $sessionUuid->value())
            ->getQuery()
            ->execute();
    }
}
