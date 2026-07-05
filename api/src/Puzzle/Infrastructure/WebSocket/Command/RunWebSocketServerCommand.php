<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\WebSocket\Command;

use App\Puzzle\Infrastructure\WebSocket\PuzzleBoardServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Workerman\Worker;

#[AsCommand(
    name: 'app:ws:serve',
    description: 'Runs the puzzle board WebSocket server (long-running process).',
)]
final class RunWebSocketServerCommand extends Command
{
    public function __construct(
        private readonly PuzzleBoardServer $server,
        private readonly string $defaultHost,
        private readonly int $defaultPort,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Bind address', $this->defaultHost)
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Listen port', (string) $this->defaultPort);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $host = (string) $input->getOption('host');
        $port = (int) $input->getOption('port');

        $io->title('Puzzle board WebSocket server');
        $io->writeln(sprintf('Listening on <info>ws://%s:%d</info>', $host, $port));

        $worker = new Worker(sprintf('websocket://%s:%d', $host, $port));
        $worker->name = 'puzzle-ws';
        $worker->count = 1;

        $this->server->bindTo($worker);

        // Workerman reads the global $argv for its start/stop control command;
        // run in the foreground so the container stays attached.
        Worker::$daemonize = false;
        $GLOBALS['argv'] = ['app:ws:serve', 'start'];

        Worker::runAll();

        return Command::SUCCESS;
    }
}
