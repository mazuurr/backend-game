<?php

declare(strict_types=1);

namespace App\Puzzle\Application\Command\RecordPieceMove;

/**
 * Server-assigned facts about a persisted drop, handed back to the WS layer so
 * it can broadcast a self-contained `peer_drop` (ordering + timestamp).
 * `correct` is the server-resolved value, not the client's original claim —
 * the WS layer must relay this one, not what the request said.
 */
final class RecordPieceMoveResult
{
    public function __construct(
        public readonly int $seq,
        public readonly \DateTimeImmutable $movedAt,
        public readonly bool $correct,
        public readonly bool $sessionCompleted,
    ) {}
}
