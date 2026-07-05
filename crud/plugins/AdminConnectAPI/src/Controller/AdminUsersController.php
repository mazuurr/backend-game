<?php
declare(strict_types=1);

namespace AdminConnectAPI\Controller;

use Cake\Http\Response;

class AdminUsersController extends AppController
{
    public function index(): void
    {
        $filters = [
            'search'   => $this->request->getQuery('search', ''),
            'active'   => $this->request->getQuery('active', ''),
            'premium'  => $this->request->getQuery('premium', ''),
            'page'     => (int) $this->request->getQuery('page', 1),
            'per_page' => (int) $this->request->getQuery('per_page', 20),
        ];
        $query = array_filter($filters, fn($v) => $v !== '' && $v !== null);

        $response = $this->api->get('/admin/users', $query);
        $users = $response['data'] ?? [];
        $meta  = $response['meta'] ?? ['page' => 1, 'pages' => 1, 'total' => count($users), 'per_page' => 20];

        $this->set(compact('users', 'meta', 'filters'));
    }

    public function view(string $uuid): void
    {
        $response = $this->api->get("/admin/users/{$uuid}");
        $user = $response['data'] ?? [];
        $this->set(compact('user'));
    }

    public function add(): ?Response
    {
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $response = $this->api->post('/admin/users', [
                'username' => $data['username'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'premium'  => !empty($data['premium']),
                'active'   => !empty($data['active']),
            ]);
            if ($this->api->isSuccess($response)) {
                $this->Flash->success('Użytkownik został dodany.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error($response['message'] ?? 'Błąd podczas dodawania użytkownika.');
        }
        return null;
    }

    public function edit(string $uuid): ?Response
    {
        $userResponse = $this->api->get("/admin/users/{$uuid}");
        $user = $userResponse['data'] ?? [];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $response = $this->api->patch("/admin/users/{$uuid}", $data);
            if ($this->api->isSuccess($response)) {
                $this->Flash->success('Użytkownik został zaktualizowany.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error($response['message'] ?? 'Błąd podczas aktualizacji użytkownika.');
        }

        $this->set(compact('user', 'uuid'));
        return null;
    }

    public function delete(string $uuid): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $response = $this->api->delete("/admin/users/{$uuid}");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Użytkownik został usunięty.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas usuwania użytkownika.');
        }
        return $this->redirect(['action' => 'index']);
    }

    public function deactivate(string $uuid): Response
    {
        $this->request->allowMethod(['post']);
        $response = $this->api->post("/admin/users/{$uuid}/deactivate");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Użytkownik został dezaktywowany.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas dezaktywacji użytkownika.');
        }
        return $this->redirect(['action' => 'index']);
    }

    public function activate(string $uuid): Response
    {
        $this->request->allowMethod(['post']);
        $response = $this->api->post("/admin/users/{$uuid}/activate");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Użytkownik został aktywowany.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas aktywacji użytkownika.');
        }
        return $this->redirect(['action' => 'index']);
    }

    public function sendPasswordReset(string $uuid): Response
    {
        $this->request->allowMethod(['post']);
        $response = $this->api->post("/admin/users/{$uuid}/send-password-reset");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Email z linkiem resetującym hasło został wysłany.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas wysyłania emaila resetującego.');
        }
        $redirect = $this->request->getData('redirect', 'index');
        return $this->redirect(['action' => $redirect === 'edit' ? 'edit' : 'index', $uuid]);
    }
}
