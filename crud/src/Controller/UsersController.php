<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

class UsersController extends AppController
{
    public function login(): ?Response
    {
        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            $redirect = $this->request->getQuery('redirect', ['controller' => 'Admins', 'action' => 'index']);
            return $this->redirect($redirect);
        }

        if ($this->request->is('post') && !$result->isValid()) {
            $this->Flash->error('Nieprawidłowa nazwa użytkownika lub hasło.');
        }

        $this->viewBuilder()->setLayout('login');
        return null;
    }

    public function logout(): Response
    {
        $this->Authentication->logout();
        return $this->redirect(['controller' => 'Users', 'action' => 'login']);
    }
}
