<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Query;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class SymfonyQueryBus implements QueryBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
    ) {}

    public function ask(object $query): mixed
    {
        try {
            $envelope = $this->queryBus->dispatch($query);
            $handledStamp = $envelope->last(HandledStamp::class);

            if ($handledStamp === null) {
                throw new \LogicException('Query was not handled.');
            }

            return $handledStamp->getResult();
        } catch (HandlerFailedException $e) {
            while ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? throw $e;
            }
            throw $e;
        }
    }
}
