<?php
declare(strict_types=1);

namespace AdminConnectAPI\Controller;

use Cake\Http\Response;

class AdminGroupsController extends AppController
{
    public function index(): void
    {
        $filters = [
            'search'   => $this->request->getQuery('search', ''),
            'page'     => (int) $this->request->getQuery('page', 1),
            'per_page' => (int) $this->request->getQuery('per_page', 20),
        ];
        $query = array_filter($filters, fn($v) => $v !== '' && $v !== null);

        $response = $this->api->get('/admin/groups', $query);
        $groups = $response['data'] ?? [];
        $meta   = $response['meta'] ?? ['page' => 1, 'pages' => 1, 'total' => count($groups), 'per_page' => 20];

        $this->set(compact('groups', 'meta', 'filters'));
    }

    public function view(string $uuid): void
    {
        $response = $this->api->get("/admin/groups/{$uuid}");
        $group = $response['data'] ?? [];
        $this->set(compact('group'));
    }

    public function add(): ?Response
    {
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $response = $this->api->post('/admin/groups', [
                'name'        => $data['name'],
                'description' => $data['description'] ?? '',
            ]);
            if ($this->api->isSuccess($response)) {
                $this->Flash->success('Grupa została dodana.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error($response['message'] ?? 'Błąd podczas dodawania grupy.');
        }
        return null;
    }

    public function edit(string $uuid): ?Response
    {
        $groupResponse = $this->api->get("/admin/groups/{$uuid}");
        $group = $groupResponse['data'] ?? [];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $response = $this->api->patch("/admin/groups/{$uuid}", [
                'name'        => $data['name'],
                'description' => $data['description'] ?? '',
            ]);
            if ($this->api->isSuccess($response)) {
                $this->Flash->success('Grupa została zaktualizowana.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error($response['message'] ?? 'Błąd podczas aktualizacji grupy.');
        }

        $this->set(compact('group', 'uuid'));
        return null;
    }

    public function delete(string $uuid): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $response = $this->api->delete("/admin/groups/{$uuid}");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Grupa została usunięta.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas usuwania grupy.');
        }
        return $this->redirect(['action' => 'index']);
    }

    public function addUser(string $groupUuid, string $userUuid): Response
    {
        $this->request->allowMethod(['post']);
        $response = $this->api->post("/admin/groups/{$groupUuid}/users/{$userUuid}");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Użytkownik został przypisany do grupy.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas przypisywania użytkownika.');
        }
        return $this->redirect(['action' => 'view', $groupUuid]);
    }

    public function removeUser(string $groupUuid, string $userUuid): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $response = $this->api->delete("/admin/groups/{$groupUuid}/users/{$userUuid}");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Użytkownik został wypisany z grupy.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas wypisywania użytkownika.');
        }
        return $this->redirect(['action' => 'view', $groupUuid]);
    }
}
