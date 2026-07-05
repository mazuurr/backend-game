<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Command;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class SymfonyCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {}

    public function dispatch(object $command): void
    {
        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            /** @var \Throwable $nested */
            while ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? throw $e;
            }
            throw $e;
        }
    }
}
