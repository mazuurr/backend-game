<?php
declare(strict_types=1);

namespace AdminConnectAPI\Controller;

use Cake\Http\Response;

class AdminPuzzlesController extends AppController
{
    public function index(): void
    {
        $filters = [
            'page'          => (int) $this->request->getQuery('page', 1),
            'per_page'      => (int) $this->request->getQuery('per_page', 20),
        ];
        $query = array_filter($filters, fn($v) => $v !== '' && $v !== null);

        $response = $this->api->get('/admin/puzzles', $query);
        $puzzles = $response['data'] ?? [];
        $meta    = $response['meta'] ?? ['page' => 1, 'pages' => 1, 'total' => count($puzzles), 'per_page' => 20];

        $this->set(compact('puzzles', 'meta', 'filters'));
    }

    public function view(string $uuid): void
    {
        $response = $this->api->get("/admin/puzzles/{$uuid}");
        $puzzle = $response['data'] ?? [];

        $this->set(compact('puzzle'));
    }

    public function add(): ?Response
    {
        if ($this->request->is('post')) {
            $file = $this->request->getUploadedFile('image');
            if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
                $this->Flash->error('Wybierz plik grafiki.');
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
                $this->Flash->success('Puzzle zostało dodane.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error($response['message'] ?? 'Błąd podczas dodawania puzzla.');
        }

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
