<?php
declare(strict_types=1);

namespace AdminConnectAPI\Controller;

use Cake\Http\Response;

class AdminSessionsController extends AppController
{
    public function index(): void
    {
        $filters = [
            'status'   => $this->request->getQuery('status', ''),
            'page'     => (int) $this->request->getQuery('page', 1),
            'per_page' => (int) $this->request->getQuery('per_page', 20),
        ];
        $query = array_filter($filters, fn($v) => $v !== '' && $v !== null);

        $response = $this->api->get('/admin/sessions', $query);
        $sessions = $response['data'] ?? [];
        $meta     = $response['meta'] ?? ['page' => 1, 'pages' => 1, 'total' => count($sessions), 'per_page' => 20];

        $this->set(compact('sessions', 'meta', 'filters'));
    }

    public function stats(): void
    {
        $filters = [
            'page'     => (int) $this->request->getQuery('page', 1),
            'per_page' => (int) $this->request->getQuery('per_page', 20),
        ];

        $response = $this->api->get('/admin/sessions/stats', $filters);
        $stats = $response['data'] ?? [];
        $meta  = $response['meta'] ?? ['page' => 1, 'pages' => 1, 'total' => count($stats), 'per_page' => 20];

        $this->set(compact('stats', 'meta', 'filters'));
    }

    public function view(string $uuid): void
    {
        $response = $this->api->get("/admin/sessions/{$uuid}");
        $session = $response['data'] ?? [];
        $this->set(compact('session'));
    }

    public function close(string $uuid): Response
    {
        $this->request->allowMethod(['post']);
        $response = $this->api->post("/admin/sessions/{$uuid}/close");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Sesja została zamknięta.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas zamykania sesji.');
        }
        $redirect = $this->request->getData('redirect', 'index');
        return $this->redirect($redirect === 'view' ? ['action' => 'view', $uuid] : ['action' => 'index']);
    }
}
