<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

class Admin extends Entity
{
    protected array $_accessible = [
        'username' => true,
        'email' => true,
        'password' => true,
        'role' => true,
    ];

    protected array $_hidden = ['password'];

    protected function _setPassword(string $password): string
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher())->hash($password);
        }
        return $password;
    }
}
