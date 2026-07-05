<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\WebSocket;

use App\Puzzle\Application\Command\RecordPieceMove\RecordPieceMoveCommand;
use App\Puzzle\Application\DTO\SessionDTO;
use App\Puzzle\Application\Query\GetBoardState\GetBoardStateQuery;
use App\Puzzle\Application\Query\GetSession\GetSessionQuery;
use App\Puzzle\Domain\Entity\PuzzleSession;
use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Bus\Query\QueryBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * Raw WebSocket hub for shared puzzle boards (Workerman transport).
 *
 * Two kinds of events flow through here:
 *  - "drag": ephemeral, relayed to peers only (never persisted),
 *  - "drop": durable, dispatched as RecordPieceMove (log + board state) then broadcast.
 *
 * Long-running process: the EntityManager is cleared after every handled message
 * to avoid a stale identity map and unbounded memory growth.
 */
final class PuzzleBoardServer
{
    /** @var \SplObjectStorage<TcpConnection, ConnectionContext> */
    private \SplObjectStorage $contexts;

    /** @var array<string, \SplObjectStorage<TcpConnection, ConnectionContext>> */
    private array $rooms = [];

    public function __construct(
        private readonly ConnectionAuthenticator $authenticator,
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
        $this->contexts = new \SplObjectStorage();
    }

    public function bindTo(Worker $worker): void
    {
        $worker->onConnect = function (TcpConnection $connection): void {
            // The handshake carries the JWT (query ?token= or Authorization header).
            $connection->onWebSocketConnect = function (TcpConnection $conn, string $header): void {
                $this->onHandshake($conn, $header);
            };
        };

        $worker->onMessage = function (TcpConnection $connection, $data): void {
            $this->onMessage($connection, (string) $data);
        };

        $worker->onClose = function (TcpConnection $connection): void {
            $this->onClose($connection);
        };
    }

    private function onHandshake(TcpConnection $conn, string $header): void
    {
        try {
            $client = $this->authenticator->authenticate($this->extractToken($header));
        } catch (AuthenticationFailedException $e) {
            $this->send($conn, ['type' => 'error', 'message' => $e->getMessage()]);
            $conn->close();

            return;
        }

        $this->contexts->attach($conn, new ConnectionContext($client));
        $this->send($conn, ['type' => 'welcome', 'user_uuid' => $client->userUuid]);
    }

    private function onMessage(TcpConnection $from, string $msg): void
    {
        if (!$this->contexts->contains($from)) {
            return;
        }

        $context = $this->contexts[$from];

        try {
            $data = json_decode($msg, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->send($from, ['type' => 'error', 'message' => 'Malformed JSON.']);

            return;
        }

        $type = is_array($data) ? ($data['type'] ?? null) : null;

        try {
            match ($type) {
                'join' => $this->handleJoin($from, $context, $data),
                'drag' => $this->handleDrag($from, $context, $data),
                'drop' => $this->handleDrop($from, $context, $data),
                'leave' => $this->handleLeave($from, $context),
                default => $this->send($from, ['type' => 'error', 'message' => sprintf('Unknown message type: %s', (string) $type)]),
            };
        } catch (\DomainException $e) {
            $this->send($from, ['type' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->logger->error('WebSocket message handling failed', ['exception' => $e]);
            $this->send($from, ['type' => 'error', 'message' => 'Internal server error.']);
        } finally {
            // Keep the long-running EM clean between messages.
            $this->em->clear();
        }
    }

    private function onClose(TcpConnection $conn): void
    {
        if ($this->contexts->contains($conn)) {
            $context = $this->contexts[$conn];
            $this->leaveRoom($conn, $context);
            $this->contexts->detach($conn);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleJoin(TcpConnection $conn, ConnectionContext $context, array $data): void
    {
        $sessionUuid = $this->requireString($data, 'sessionUuid');

        /** @var SessionDTO $session */
        $session = $this->queryBus->ask(new GetSessionQuery($sessionUuid));

        if ($session->status !== PuzzleSession::STATUS_OPEN) {
            $this->send($conn, ['type' => 'error', 'message' => 'Session is closed.']);

            return;
        }

        if ($session->visibility === PuzzleSession::VISIBILITY_GROUP
            && $session->groupUuid !== $context->client->groupUuid
        ) {
            $this->send($conn, ['type' => 'error', 'message' => 'You are not a member of this group session.']);

            return;
        }

        // One room per connection: leave a previous one first.
        if ($context->sessionUuid !== null && $context->sessionUuid !== $sessionUuid) {
            $this->leaveRoom($conn, $context);
        }

        $context->sessionUuid = $sessionUuid;
        $this->rooms[$sessionUuid] ??= new \SplObjectStorage();
        $this->rooms[$sessionUuid]->attach($conn, $context);

        $board = $this->queryBus->ask(new GetBoardStateQuery($sessionUuid));

        $this->send($conn, [
            'type' => 'board_state',
            'session_uuid' => $sessionUuid,
            'pieces' => $board,
        ]);

        $this->broadcast($sessionUuid, [
            'type' => 'presence',
            'event' => 'joined',
            'user_uuid' => $context->client->userUuid,
            'count' => $this->rooms[$sessionUuid]->count(),
        ], exclude: $conn);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleDrag(TcpConnection $conn, ConnectionContext $context, array $data): void
    {
        $sessionUuid = $this->requireJoined($context);

        $this->broadcast($sessionUuid, [
            'type' => 'peer_drag',
            'user_uuid' => $context->client->userUuid,
            'piece_index' => $this->requireInt($data, 'pieceIndex'),
            'x' => $this->requireInt($data, 'x'),
            'y' => $this->requireInt($data, 'y'),
        ], exclude: $conn);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleDrop(TcpConnection $conn, ConnectionContext $context, array $data): void
    {
        $sessionUuid = $this->requireJoined($context);

        $pieceIndex = $this->requireInt($data, 'pieceIndex');
        $x = $this->requireInt($data, 'x');
        $y = $this->requireInt($data, 'y');
        $correct = (bool) ($data['correct'] ?? false);

        $this->commandBus->dispatch(new RecordPieceMoveCommand(
            sessionUuid: $sessionUuid,
            userUuid: $context->client->userUuid,
            pieceIndex: $pieceIndex,
            toX: $x,
            toY: $y,
            correct: $correct,
        ));

        $payload = [
            'type' => 'peer_drop',
            'user_uuid' => $context->client->userUuid,
            'piece_index' => $pieceIndex,
            'x' => $x,
            'y' => $y,
            'correct' => $correct,
        ];

        // Confirm to the sender, broadcast to everyone else in the room.
        $this->send($conn, ['type' => 'drop_ack'] + $payload);
        $this->broadcast($sessionUuid, $payload, exclude: $conn);
    }

    private function handleLeave(TcpConnection $conn, ConnectionContext $context): void
    {
        $this->leaveRoom($conn, $context);
    }

    private function leaveRoom(TcpConnection $conn, ConnectionContext $context): void
    {
        $sessionUuid = $context->sessionUuid;

        if ($sessionUuid === null || !isset($this->rooms[$sessionUuid])) {
            return;
        }

        $this->rooms[$sessionUuid]->detach($conn);
        $context->sessionUuid = null;

        if ($this->rooms[$sessionUuid]->count() === 0) {
            unset($this->rooms[$sessionUuid]);

            return;
        }

        $this->broadcast($sessionUuid, [
            'type' => 'presence',
            'event' => 'left',
            'user_uuid' => $context->client->userUuid,
            'count' => $this->rooms[$sessionUuid]->count(),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function broadcast(string $sessionUuid, array $payload, ?TcpConnection $exclude = null): void
    {
        if (!isset($this->rooms[$sessionUuid])) {
            return;
        }

        $message = json_encode($payload, JSON_THROW_ON_ERROR);

        foreach ($this->rooms[$sessionUuid] as $conn) {
            if ($conn !== $exclude) {
                $conn->send($message);
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function send(TcpConnection $conn, array $payload): void
    {
        $conn->send(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function requireJoined(ConnectionContext $context): string
    {
        if ($context->sessionUuid === null) {
            throw new \DomainException('Join a session before sending moves.');
        }

        return $context->sessionUuid;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requireString(array $data, string $key): string
    {
        if (!isset($data[$key]) || !is_string($data[$key]) || $data[$key] === '') {
            throw new \DomainException(sprintf('Field "%s" is required.', $key));
        }

        return $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requireInt(array $data, string $key): int
    {
        if (!isset($data[$key]) || !is_numeric($data[$key])) {
            throw new \DomainException(sprintf('Field "%s" must be a number.', $key));
        }

        return (int) $data[$key];
    }

    /**
     * Pulls the JWT from the raw handshake: query param ?token= or Authorization: Bearer.
     */
    private function extractToken(string $header): ?string
    {
        $token = null;

        if (preg_match('#^GET\s+(\S+)\s+HTTP#i', $header, $m) === 1) {
            $query = parse_url($m[1], PHP_URL_QUERY);

            if (is_string($query)) {
                parse_str($query, $params);

                if (isset($params['token']) && is_string($params['token']) && $params['token'] !== '') {
                    $token = $params['token'];
                }
            }
        }

        if ($token === null && preg_match('#^Authorization:\s*Bearer\s+(\S+)#im', $header, $m) === 1) {
            $token = $m[1];
        }

        return $token;
    }
}
