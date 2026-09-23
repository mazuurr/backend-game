<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Service;

use App\Puzzle\Application\DTO\SessionContributionsDTO;
use App\Puzzle\Application\DTO\UserContributionDTO;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Puzzle\Domain\Repository\PuzzlePieceMoveRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleRepositoryInterface;
use App\Puzzle\Domain\ValueObject\PuzzleSessionId;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\UserId;

/**
 * Answers "who assembled how many pieces" for a session. A piece counts for the
 * user whose *latest* move placed it correctly — so the tally reflects the board
 * as it currently stands (a piece that was later knocked out of place no longer
 * counts for anyone). Derived from the append-only move log, which is the same
 * source `puzzle_board_state` is materialised from.
 */
final class SessionContributionsCalculator
{
    public function __construct(
        private readonly PuzzleRepositoryInterface $puzzleRepository,
        private readonly PuzzlePieceMoveRepositoryInterface $moveRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function forSession(PuzzleSession $session): SessionContributionsDTO
    {
        $puzzle = $this->puzzleRepository->findByUuid($session->getPuzzleUuid());
        $total = $puzzle?->getEffectivePieceCount() ?? 0;

        $sessionId = new PuzzleSessionId($session->getUuid()->value());

        // Per-user totals over the whole log, plus the latest move per piece so
        // we can also count what's *currently* correct (not just ever-correct).
        $totalMoves = [];
        $correctMoves = [];
        $latestByPiece = [];
        foreach ($this->moveRepository->findBySession($sessionId) as $move) {
            $userUuid = $move->getUserUuid()->value();
            $totalMoves[$userUuid] = ($totalMoves[$userUuid] ?? 0) + 1;
            if ($move->isCorrect()) {
                $correctMoves[$userUuid] = ($correctMoves[$userUuid] ?? 0) + 1;
            }

            $piece = $move->getPieceIndex();
            if (!isset($latestByPiece[$piece]) || $move->getSeq() > $latestByPiece[$piece]->getSeq()) {
                $latestByPiece[$piece] = $move;
            }
        }

        // Pieces standing correct right now, attributed to whoever placed them.
        $currentCorrect = [];
        foreach ($latestByPiece as $move) {
            if ($move->isCorrect()) {
                $userUuid = $move->getUserUuid()->value();
                $currentCorrect[$userUuid] = ($currentCorrect[$userUuid] ?? 0) + 1;
            }
        }

        // Rank every participant (anyone who moved), best current board first.
        uksort($totalMoves, static function (string $a, string $b) use ($currentCorrect, $totalMoves): int {
            return ($currentCorrect[$b] ?? 0) <=> ($currentCorrect[$a] ?? 0)
                ?: ($totalMoves[$b] ?? 0) <=> ($totalMoves[$a] ?? 0);
        });

        $contributions = [];
        $correct = 0;
        foreach ($totalMoves as $userUuid => $moves) {
            $correct += $currentCorrect[$userUuid] ?? 0;
            $contributions[] = new UserContributionDTO(
                userUuid: $userUuid,
                username: $this->resolveUsername($userUuid),
                correctCount: $currentCorrect[$userUuid] ?? 0,
                totalMoves: $moves,
                correctMoves: $correctMoves[$userUuid] ?? 0,
            );
        }

        return new SessionContributionsDTO($total, $correct, $contributions);
    }

    private function resolveUsername(string $userUuid): string
    {
        $user = $this->userRepository->findByUuid(new UserId($userUuid));

        return $user !== null ? $user->getUsername()->value() : substr($userUuid, 0, 8);
    }
}
