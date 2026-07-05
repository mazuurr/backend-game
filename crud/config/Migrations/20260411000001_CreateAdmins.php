<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateAdmins extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('admins');
        $table
            ->addColumn('username', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('role', 'enum', [
                'values' => ['superadmin', 'admin', 'moderator'],
                'default' => 'admin',
                'null' => false,
            ])
            ->addColumn('created', 'datetime', ['default' => null, 'null' => true])
            ->addColumn('modified', 'datetime', ['default' => null, 'null' => true])
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->create();
    }
}
