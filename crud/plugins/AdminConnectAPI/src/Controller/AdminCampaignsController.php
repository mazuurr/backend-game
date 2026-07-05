<?php
declare(strict_types=1);

namespace AdminConnectAPI\Controller;

use Cake\Http\Response;

class AdminCampaignsController extends AppController
{
    public function index(): void
    {
        $filters = [
            'search'   => $this->request->getQuery('search', ''),
            'page'     => (int) $this->request->getQuery('page', 1),
            'per_page' => (int) $this->request->getQuery('per_page', 20),
        ];
        $query = array_filter($filters, fn($v) => $v !== '' && $v !== null);

        $response = $this->api->get('/admin/campaigns', $query);
        $campaigns = $response['data'] ?? [];
        $meta      = $response['meta'] ?? ['page' => 1, 'pages' => 1, 'total' => count($campaigns), 'per_page' => 20];

        $this->set(compact('campaigns', 'meta', 'filters'));
    }

    public function view(string $uuid): void
    {
        $response = $this->api->get("/admin/campaigns/{$uuid}");
        $campaign = $response['data'] ?? [];

        $puzzlesResponse = $this->api->get('/admin/puzzles');
        $allPuzzles = $puzzlesResponse['data'] ?? [];

        $assignedUuids = array_column($campaign['puzzles'] ?? [], 'uuid');
        $availablePuzzles = array_filter($allPuzzles, fn($p) => !in_array($p['uuid'], $assignedUuids, true));

        $this->set(compact('campaign', 'availablePuzzles'));
    }

    public function add(): ?Response
    {
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $response = $this->api->post('/admin/campaigns', [
                'name'        => $data['name'],
                'description' => $data['description'] ?? '',
            ]);
            if ($this->api->isSuccess($response)) {
                $this->Flash->success('Kampania została dodana.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error($response['message'] ?? 'Błąd podczas dodawania kampanii.');
        }
        return null;
    }

    public function edit(string $uuid): ?Response
    {
        $campaignResponse = $this->api->get("/admin/campaigns/{$uuid}");
        $campaign = $campaignResponse['data'] ?? [];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $response = $this->api->patch("/admin/campaigns/{$uuid}", [
                'name'        => $data['name'],
                'description' => $data['description'] ?? '',
            ]);
            if ($this->api->isSuccess($response)) {
                $this->Flash->success('Kampania została zaktualizowana.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error($response['message'] ?? 'Błąd podczas aktualizacji kampanii.');
        }

        $this->set(compact('campaign', 'uuid'));
        return null;
    }

    public function delete(string $uuid): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $response = $this->api->delete("/admin/campaigns/{$uuid}");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Kampania została usunięta.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas usuwania kampanii.');
        }
        return $this->redirect(['action' => 'index']);
    }

    public function assignPuzzle(string $campaignUuid, string $puzzleUuid): Response
    {
        $this->request->allowMethod(['post']);
        $response = $this->api->post("/admin/campaigns/{$campaignUuid}/puzzles/{$puzzleUuid}");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Puzzle zostało przypisane do kampanii.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas przypisywania puzzla.');
        }
        return $this->redirect(['action' => 'view', $campaignUuid]);
    }

    public function removePuzzle(string $campaignUuid, string $puzzleUuid): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $response = $this->api->delete("/admin/campaigns/{$campaignUuid}/puzzles/{$puzzleUuid}");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Puzzle zostało odpięte od kampanii.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas odpinania puzzla.');
        }
        return $this->redirect(['action' => 'view', $campaignUuid]);
    }
}
