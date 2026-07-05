<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class AddAdminCommand extends Command
{
    public static function defaultName(): string
    {
        return 'add_admin';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Dodaje nowego administratora do panelu.');
        $parser->addArgument('username', [
            'help' => 'Nazwa użytkownika',
            'required' => true,
        ]);
        $parser->addArgument('email', [
            'help' => 'Adres email',
            'required' => true,
        ]);
        $parser->addArgument('password', [
            'help' => 'Hasło (min. 8 znaków)',
            'required' => true,
        ]);
        $parser->addArgument('role', [
            'help' => 'Rola: superadmin, admin, moderator (domyślnie: admin)',
            'required' => false,
        ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $role = $args->getArgument('role') ?? 'admin';

        if (!in_array($role, ['superadmin', 'admin', 'moderator'], true)) {
            $io->error("Nieprawidłowa rola \"{$role}\". Dostępne: superadmin, admin, moderator.");
            return static::CODE_ERROR;
        }

        $admins = $this->fetchTable('Admins');

        $admin = $admins->newEntity([
            'username' => $args->getArgument('username'),
            'email'    => $args->getArgument('email'),
            'password' => $args->getArgument('password'),
            'role'     => $role,
        ]);

        if ($admin->getErrors()) {
            $io->error('Błąd walidacji:');
            foreach ($admin->getErrors() as $field => $errors) {
                foreach ($errors as $error) {
                    $io->error("  [{$field}] {$error}");
                }
            }
            return static::CODE_ERROR;
        }

        if ($admins->save($admin)) {
            $io->success("Administrator \"{$args->getArgument('username')}\" ({$role}) został dodany (ID: {$admin->id}).");
            return static::CODE_SUCCESS;
        }

        $io->error('Nie można zapisać administratora.');
        return static::CODE_ERROR;
    }
}
