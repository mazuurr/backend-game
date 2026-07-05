<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\RecordPieceMove;

use App\Puzzle\Domain\Entity\PuzzleBoardPiece;
use App\Puzzle\Domain\Entity\PuzzlePieceMove;
use App\Puzzle\Domain\Exception\InvalidPieceMoveException;
use App\Puzzle\Domain\Exception\PuzzleNotFoundException;
use App\Puzzle\Domain\Exception\PuzzleSessionClosedException;
use App\Puzzle\Domain\Exception\PuzzleSessionNotFoundException;
use App\Puzzle\Domain\Repository\PuzzleBoardPieceRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzlePieceMoveRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PieceMoveId;
use App\Puzzle\Domain\ValueObject\PiecePosition;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Persists a piece drop in one transaction (doctrine_transaction middleware):
 * append to the move log AND upsert the materialized board state.
 */
#[AsMessageHandler(bus: 'command.bus')]
final class RecordPieceMoveHandler
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
        private readonly PuzzleRepositoryInterface $puzzleRepository,
        private readonly PuzzlePieceMoveRepositoryInterface $moveRepository,
        private readonly PuzzleBoardPieceRepositoryInterface $boardRepository,
    ) {}

    public function __invoke(RecordPieceMoveCommand $command): void
    {
        $sessionId = new PuzzleSessionId($command->sessionUuid);
        $session = $this->sessionRepository->findByUuid($sessionId);

        if ($session === null) {
            throw new PuzzleSessionNotFoundException();
        }

        if (!$session->isOpen()) {
            throw new PuzzleSessionClosedException();
        }

        $puzzle = $this->puzzleRepository->findByUuid($session->getPuzzleUuid());

        if ($puzzle === null) {
            throw new PuzzleNotFoundException();
        }

        // Coordinates must be non-negative; further bounds checked against the grid.
        $position = new PiecePosition($command->toX, $command->toY);

        $this->assertWithinPuzzle($puzzle->getTotalPieces(), $puzzle->getPiecesX(), $puzzle->getPiecesY(), $command->pieceIndex, $position);

        $moveUuid = PieceMoveId::generate();
        $seq = $this->moveRepository->nextSeq($sessionId);

        $move = PuzzlePieceMove::record(
            uuid: $moveUuid,
            sessionUuid: $sessionId,
            userUuid: new UserId($command->userUuid),
            puzzleUuid: $session->getPuzzleUuid(),
            pieceIndex: $command->pieceIndex,
            toX: $position->x,
            toY: $position->y,
            correct: $command->correct,
            seq: $seq,
        );
        $this->moveRepository->save($move);

        $piece = $this->boardRepository->findBySessionAndPiece($sessionId, $command->pieceIndex);

        if ($piece === null) {
            $piece = PuzzleBoardPiece::place(
                sessionUuid: $sessionId,
                pieceIndex: $command->pieceIndex,
                toX: $position->x,
                toY: $position->y,
                correct: $command->correct,
                lastMoveUuid: $moveUuid,
            );
        } else {
            $piece->moveTo($position->x, $position->y, $command->correct, $moveUuid);
        }

        $this->boardRepository->save($piece);
    }

    private function assertWithinPuzzle(
        ?int $totalPieces,
        ?int $piecesX,
        ?int $piecesY,
        int $pieceIndex,
        PiecePosition $position,
    ): void {
        if ($pieceIndex < 0 || ($totalPieces !== null && $pieceIndex >= $totalPieces)) {
            throw InvalidPieceMoveException::pieceIndexOutOfRange($pieceIndex, $totalPieces ?? 0);
        }

        if ($piecesX !== null && $piecesY !== null
            && ($position->x >= $piecesX || $position->y >= $piecesY)
        ) {
            throw InvalidPieceMoveException::positionOutOfGrid($position->x, $position->y, $piecesX, $piecesY);
        }
    }
}
