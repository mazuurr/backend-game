<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Service;

use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Entity\PuzzleSessionStat;
use App\Puzzle\Domain\Repository\PuzzleSessionStatRepositoryInterface;

/**
 * Captures the permanent stats snapshot (time spent, pieces placed) for a closed
 * session, idempotently — a session may already have been snapshotted right when
 * it auto-closed on completion (see RecordPieceMoveHandler), so a later purge run
 * must not try to insert a second row for the same session. Idempotency is
 * enforced at the DB level (see PuzzleSessionStatRepositoryInterface::insertIfMissing),
 * not by a check-then-insert here, so two concurrent completions of the same
 * session can't race each other into a duplicate-row exception.
 */
final class SessionStatRecorder
{
    public function __construct(
        private readonly SessionContributionsCalculator $contributionsCalculator,
        private readonly PuzzleSessionStatRepositoryInterface $statRepository,
    ) {}

    public function recordIfMissing(PuzzleSession $session): void
    {
        $contributions = $this->contributionsCalculator->forSession($session);

        $this->recordCompletionIfMissing($session, $contributions->total, $contributions->correct);
    }

    /**
     * Same as {@see recordIfMissing()}, but for a caller that already knows
     * the totals (e.g. the completion check in RecordPieceMoveHandler, which
     * just counted them off the board) — skips re-deriving them from a full
     * move-log rescan.
     */
    public function recordCompletionIfMissing(PuzzleSession $session, int $totalPieces, int $correctPieces): void
    {
        $this->statRepository->insertIfMissing(PuzzleSessionStat::capture(
            sessionUuid: $session->getUuid(),
            puzzleUuid: $session->getPuzzleUuid(),
            createdByUserUuid: $session->getCreatedByUserUuid(),
            mode: $session->getMode(),
            totalPieces: $totalPieces,
            correctPieces: $correctPieces,
            startedAt: $session->getCreatedAt(),
            finishedAt: $session->getClosedAt() ?? new \DateTimeImmutable(),
        ));
    }
}
