<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\RecordPieceMove;

use App\Puzzle\Application\Service\SessionStatRecorder;
use App\Puzzle\Domain\Entity\Puzzle;
use App\Puzzle\Domain\Entity\PuzzleBoardPiece;
use App\Puzzle\Domain\Entity\PuzzlePieceMove;
use App\Puzzle\Domain\Entity\PuzzleSession;
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
use App\Shared\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Persists a piece drop in one transaction (doctrine_transaction middleware):
 * append to the move log AND upsert the materialized board state. When that
 * drop completes the puzzle (every piece now correctly placed), the session is
 * closed immediately — same as a manual close — and its stats snapshot is
 * recorded right away rather than waiting for the purge cron.
 *
 * "Correct" is never taken on the client's word: a piece belongs at the cell
 * whose index equals its own pieceIndex (row-major, same convention the app
 * uses — see puzzle-tel's board_cubit `_cellAt`/`correct: pieceA == cellB`),
 * so whenever the grid size is known the server re-derives it from
 * toX/toY/pieceIndex itself. The client's claim is only trusted as a fallback
 * for puzzles without grid metadata, where there is nothing to check it against.
 */
#[AsMessageHandler(bus: 'command.bus')]
final class RecordPieceMoveHandler
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
        private readonly PuzzleRepositoryInterface $puzzleRepository,
        private readonly PuzzlePieceMoveRepositoryInterface $moveRepository,
        private readonly PuzzleBoardPieceRepositoryInterface $boardRepository,
        private readonly SessionStatRecorder $statRecorder,
    ) {}

    public function __invoke(RecordPieceMoveCommand $command): RecordPieceMoveResult
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

        $correct = $this->resolveCorrectness($puzzle, $command->pieceIndex, $position, $command->correct);

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
            correct: $correct,
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
                correct: $correct,
                lastMoveUuid: $moveUuid,
            );
        } else {
            $piece->moveTo($position->x, $position->y, $correct, $moveUuid);
        }

        $this->boardRepository->save($piece);

        $completed = $this->closeSessionIfCompleted($session, $puzzle, $sessionId);

        return new RecordPieceMoveResult($seq, $move->getMovedAt(), $correct, $completed);
    }

    /**
     * A piece belongs at the cell sharing its own index (row-major: cell =
     * y * columns + x). Falls back to the client's claim only when the grid
     * size isn't known, since there is then nothing to check it against.
     */
    private function resolveCorrectness(Puzzle $puzzle, int $pieceIndex, PiecePosition $position, bool $clientClaim): bool
    {
        $columns = $puzzle->getPiecesX();
        $rows = $puzzle->getPiecesY();

        if ($columns === null || $rows === null) {
            return $clientClaim;
        }

        return $pieceIndex === ($position->y * $columns + $position->x);
    }

    private function closeSessionIfCompleted(PuzzleSession $session, Puzzle $puzzle, PuzzleSessionId $sessionId): bool
    {
        $total = $puzzle->getEffectivePieceCount();

        if ($total <= 0) {
            return false;
        }

        $correct = 0;
        foreach ($this->boardRepository->findBySession($sessionId) as $boardPiece) {
            if ($boardPiece->isCorrect()) {
                ++$correct;
            }
        }

        if ($correct < $total) {
            return false;
        }

        $session->close();
        $this->sessionRepository->save($session);
        // $correct === $total here by construction, so the just-computed tally
        // can be handed straight to the recorder instead of re-deriving it from
        // a full move-log rescan (SessionContributionsCalculator::forSession).
        $this->statRecorder->recordCompletionIfMissing($session, $total, $correct);

        return true;
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
