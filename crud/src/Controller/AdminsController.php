<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

class AdminsController extends AppController
{
    public function index(): void
    {
        $admins = $this->paginate($this->Admins);
        $this->set(compact('admins'));
    }

    public function add(): ?Response
    {
        $admin = $this->Admins->newEmptyEntity();

        if ($this->request->is('post')) {
            $admin = $this->Admins->patchEntity($admin, $this->request->getData());
            if ($this->Admins->save($admin)) {
                $this->Flash->success('Administrator został dodany.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('Nie można dodać administratora. Sprawdź błędy w formularzu.');
        }

        $roles = $this->_getRoles();
        $this->set(compact('admin', 'roles'));
        return null;
    }

    public function edit(int $id): ?Response
    {
        $admin = $this->Admins->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $admin = $this->Admins->patchEntity($admin, $data);
            if ($this->Admins->save($admin)) {
                $this->Flash->success('Administrator został zaktualizowany.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('Nie można zaktualizować administratora. Sprawdź błędy w formularzu.');
        }

        $roles = $this->_getRoles();
        $this->set(compact('admin', 'roles'));
        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $admin = $this->Admins->get($id);

        if ($this->Admins->delete($admin)) {
            $this->Flash->success('Administrator został usunięty.');
        } else {
            $this->Flash->error('Nie można usunąć administratora.');
        }

        return $this->redirect(['action' => 'index']);
    }

    private function _getRoles(): array
    {
        return [
            'superadmin' => 'Super Admin',
            'admin' => 'Admin',
            'moderator' => 'Moderator',
        ];
    }
}
