<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Console;

use App\Puzzle\Application\Command\ClosePuzzleSession\ClosePuzzleSessionCommand;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use App\Shared\Infrastructure\Bus\Command\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Closes puzzle sessions that have passed their 24h lifetime without being finished.
 * Intended to run periodically (cron).
 */
#[AsCommand(
    name: 'app:sessions:close-expired',
    description: 'Closes open puzzle sessions whose lifetime has expired.',
)]
final class CloseExpiredSessionsCommand extends Command
{
    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
        private readonly CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $expired = $this->sessionRepository->findExpiredOpen(new \DateTimeImmutable());
        $closed = 0;

        foreach ($expired as $session) {
            // requestedByUserUuid = null -> system action, bypasses owner check.
            $this->commandBus->dispatch(new ClosePuzzleSessionCommand($session->getUuid()->value()));
            ++$closed;
        }

        $io->success(sprintf('Closed %d expired session(s).', $closed));

        return Command::SUCCESS;
    }
}
