<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Command;

interface CommandBusInterface
{
    public function dispatch(object $command): void;

    /**
     * Dispatch a command and return its handler's result. Use for the rare
     * command that must hand server-assigned data back to the caller.
     */
    public function dispatchWithResult(object $command): mixed;
}
