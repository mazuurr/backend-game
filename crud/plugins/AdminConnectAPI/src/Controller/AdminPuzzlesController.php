<?php
declare(strict_types=1);

namespace AdminConnectAPI\Controller;

use Cake\Http\Response;

class AdminPuzzlesController extends AppController
{
    public function index(): void
    {
        $filters = [
            'campaign_uuid' => $this->request->getQuery('campaign_uuid', ''),
            'page'          => (int) $this->request->getQuery('page', 1),
            'per_page'      => (int) $this->request->getQuery('per_page', 20),
        ];
        $query = array_filter($filters, fn($v) => $v !== '' && $v !== null);

        $response = $this->api->get('/admin/puzzles', $query);
        $puzzles = $response['data'] ?? [];
        $meta    = $response['meta'] ?? ['page' => 1, 'pages' => 1, 'total' => count($puzzles), 'per_page' => 20];

        $campaignsResponse = $this->api->get('/admin/campaigns', ['per_page' => 100]);
        $campaigns = $campaignsResponse['data'] ?? [];
        $campaignMap = array_column($campaigns, 'name', 'uuid');

        $this->set(compact('puzzles', 'meta', 'filters', 'campaignMap', 'campaigns'));
    }

    public function view(string $uuid): void
    {
        $response = $this->api->get("/admin/puzzles/{$uuid}");
        $puzzle = $response['data'] ?? [];

        $campaignsResponse = $this->api->get('/admin/campaigns');
        $campaigns = $campaignsResponse['data'] ?? [];

        $this->set(compact('puzzle', 'campaigns'));
    }

    public function assignCampaign(string $uuid): Response
    {
        $this->request->allowMethod(['post']);
        $campaignUuid = $this->request->getData('campaign_uuid');
        $response = $this->api->post("/admin/campaigns/{$campaignUuid}/puzzles/{$uuid}");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Puzzle zostało przypisane do kampanii.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas przypisywania do kampanii.');
        }
        return $this->redirect(['action' => 'view', $uuid]);
    }

    public function removeCampaign(string $uuid, string $campaignUuid): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $response = $this->api->delete("/admin/campaigns/{$campaignUuid}/puzzles/{$uuid}");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Puzzle zostało odpięte od kampanii.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas odpinania kampanii.');
        }
        return $this->redirect(['action' => 'view', $uuid]);
    }

    public function add(): ?Response
    {
        $campaignsResponse = $this->api->get('/admin/campaigns', ['per_page' => 100]);
        $campaigns = $campaignsResponse['data'] ?? [];

        if ($this->request->is('post')) {
            $file = $this->request->getUploadedFile('image');
            if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
                $this->Flash->error('Wybierz plik grafiki.');
                $this->set(compact('campaigns'));
                return null;
            }
            $payload = ['image' => $file];
            foreach (['difficulty', 'total_pieces', 'pieces_per_fragment', 'pieces_x', 'pieces_y'] as $field) {
                $val = $this->request->getData($field);
                if ($val !== '' && $val !== null) {
                    $payload[$field] = (int) $val;
                }
            }
            $response = $this->api->postMultipart('/admin/puzzles', $payload);
            if ($this->api->isSuccess($response)) {
                $puzzleUuid = $response['uuid'] ?? null;
                $campaignUuid = $this->request->getData('campaign_uuid');
                if ($puzzleUuid && $campaignUuid) {
                    $this->api->post("/admin/campaigns/{$campaignUuid}/puzzles/{$puzzleUuid}");
                }
                $this->Flash->success('Puzzle zostało dodane.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error($response['message'] ?? 'Błąd podczas dodawania puzzla.');
        }

        $this->set(compact('campaigns'));
        return null;
    }

    public function update(string $uuid): Response
    {
        $this->request->allowMethod(['post', 'patch']);
        $data = $this->request->getData();
        $patch = [];
        foreach (['difficulty', 'total_pieces', 'pieces_per_fragment', 'pieces_x', 'pieces_y'] as $field) {
            if (array_key_exists($field, $data)) {
                $patch[$field] = ($data[$field] !== '' && $data[$field] !== null) ? (int) $data[$field] : null;
            }
        }
        $response = $this->api->patch("/admin/puzzles/{$uuid}", $patch);
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Dane puzzla zostały zaktualizowane.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas aktualizacji puzzla.');
        }
        return $this->redirect(['action' => 'view', $uuid]);
    }

    public function image(string $uuid): Response
    {
        $response = $this->api->getRaw("/admin/puzzles/{$uuid}/image");
        return $this->response
            ->withType($response['content_type'])
            ->withStringBody($response['body']);
    }

    public function delete(string $uuid): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $response = $this->api->delete("/admin/puzzles/{$uuid}");
        if ($this->api->isSuccess($response)) {
            $this->Flash->success('Puzzle zostało usunięte.');
        } else {
            $this->Flash->error($response['message'] ?? 'Błąd podczas usuwania puzzla.');
        }
        return $this->redirect(['action' => 'index']);
    }
}
