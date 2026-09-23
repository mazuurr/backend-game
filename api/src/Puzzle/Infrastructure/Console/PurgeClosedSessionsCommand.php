<?php

declare(strict_types=1);

namespace App\Puzzle\Infrastructure\Console;

use App\Puzzle\Application\Service\SessionStatRecorder;
use App\Puzzle\Domain\Repository\PuzzleBoardPieceRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzlePieceMoveRepositoryInterface;
use App\Puzzle\Domain\Repository\PuzzleSessionRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sessions:purge',
    description: 'Records a stats snapshot and deletes puzzle sessions closed longer than the retention period.',
)]
final class PurgeClosedSessionsCommand extends Command
{
    private const RETENTION_DAYS = 3;

    public function __construct(
        private readonly PuzzleSessionRepositoryInterface $sessionRepository,
        private readonly PuzzlePieceMoveRepositoryInterface $moveRepository,
        private readonly PuzzleBoardPieceRepositoryInterface $boardRepository,
        private readonly SessionStatRecorder $statRecorder,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $threshold = (new \DateTimeImmutable())->modify(sprintf('-%d days', self::RETENTION_DAYS));
        $sessions = $this->sessionRepository->findClosedBefore($threshold);

        foreach ($sessions as $session) {
            $this->statRecorder->recordIfMissing($session);

            $this->moveRepository->deleteBySession($session->getUuid());
            $this->boardRepository->deleteBySession($session->getUuid());
            $this->sessionRepository->delete($session);
        }

        $io->success(sprintf('Purged %d session(s) closed more than %d day(s) ago.', count($sessions), self::RETENTION_DAYS));

        return Command::SUCCESS;
    }
}
