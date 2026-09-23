<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Command;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class SymfonyCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {}

    public function dispatch(object $command): void
    {
        $this->handle($command);
    }

    public function dispatchWithResult(object $command): mixed
    {
        $envelope = $this->handle($command);
        $handledStamp = $envelope->last(HandledStamp::class);

        if ($handledStamp === null) {
            throw new \LogicException('Command was not handled.');
        }

        return $handledStamp->getResult();
    }

    private function handle(object $command): \Symfony\Component\Messenger\Envelope
    {
        try {
            return $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            while ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? throw $e;
            }
            throw $e;
        }
    }
}
