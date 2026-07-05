<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Persistence\Doctrine\Repository;

use App\Puzzle\Domain\Entity\PuzzleBoardPiece;
use App\Puzzle\Domain\Repository\PuzzleBoardPieceRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePuzzleBoardPieceRepository implements PuzzleBoardPieceRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(PuzzleBoardPiece $piece): void
    {
        $this->em->persist($piece);
        $this->em->flush();
    }

    public function findBySessionAndPiece(PuzzleSessionId $sessionUuid, int $pieceIndex): ?PuzzleBoardPiece
    {
        return $this->em->createQueryBuilder()
            ->select('b')
            ->from(PuzzleBoardPiece::class, 'b')
            ->where('b.sessionUuid = :sessionUuid')
            ->andWhere('b.pieceIndex = :pieceIndex')
            ->setParameter('sessionUuid', $sessionUuid->value())
            ->setParameter('pieceIndex', $pieceIndex)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return PuzzleBoardPiece[] */
    public function findBySession(PuzzleSessionId $sessionUuid): array
    {
        return $this->em->createQueryBuilder()
            ->select('b')
            ->from(PuzzleBoardPiece::class, 'b')
            ->where('b.sessionUuid = :sessionUuid')
            ->setParameter('sessionUuid', $sessionUuid->value())
            ->orderBy('b.pieceIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
