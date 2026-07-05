<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class AdminsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('admins');
        $this->setEntityClass('App\Model\Entity\Admin');
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('username')
            ->minLength('username', 3, 'Nazwa użytkownika musi mieć co najmniej 3 znaki.')
            ->maxLength('username', 100)
            ->requirePresence('username', 'create')
            ->notEmptyString('username')
            ->add('username', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => 'Ta nazwa użytkownika jest już zajęta.',
            ]);

        $validator
            ->email('email', false, 'Podaj poprawny adres email.')
            ->requirePresence('email', 'create')
            ->notEmptyString('email')
            ->add('email', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => 'Ten adres email jest już zajęty.',
            ]);

        $validator
            ->scalar('password')
            ->minLength('password', 8, 'Hasło musi mieć co najmniej 8 znaków.')
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        $validator
            ->inList('role', ['superadmin', 'admin', 'moderator'], 'Nieprawidłowa rola.')
            ->requirePresence('role', 'create')
            ->notEmptyString('role');

        return $validator;
    }
}
