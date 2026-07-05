<?php $this->assign('title', 'Szczegóły grupy'); ?>

<section class="content-header"><h1><?= h($group['name'] ?? '') ?></h1></section>

<section class="content">
  <div class="row">
    <div class="col-md-5">
      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Dane grupy</h3></div>
        <div class="box-body">
          <dl class="dl-horizontal">
            <dt>UUID</dt><dd><?= h($group['uuid'] ?? '') ?></dd>
            <dt>Nazwa</dt><dd><?= h($group['name'] ?? '') ?></dd>
            <dt>Opis</dt><dd><?= h($group['description'] ?? '') ?></dd>
            <dt>Utworzona</dt><dd><?= $this->Date->format($group['created_at'] ?? null) ?></dd>
          </dl>
        </div>
        <div class="box-footer">
          <?= $this->Html->link('<i class="fa fa-pencil"></i> Edytuj', ['action' => 'edit', $group['uuid']], ['class' => 'btn btn-info', 'escape' => false]) ?>
          <?= $this->Html->link('Wróć', ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
      </div>
    </div>

    <div class="col-md-7">
      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Użytkownicy w grupie</h3></div>
        <div class="box-body table-responsive no-padding">
          <table class="table table-hover">
            <thead><tr><th>Username</th><th>Email</th><th class="text-right">Akcje</th></tr></thead>
            <tbody>
              <?php foreach ($group['users'] ?? [] as $user): ?>
              <tr>
                <td><?= h($user['username']) ?></td>
                <td><?= h($user['email']) ?></td>
                <td class="text-right">
                  <?= $this->Form->postLink(
                    '<i class="fa fa-user-times"></i>',
                    ['action' => 'removeUser', $group['uuid'], $user['uuid']],
                    ['class' => 'btn btn-xs btn-danger', 'escape' => false, 'confirm' => 'Wypisać użytkownika ' . h($user['username']) . ' z grupy?']
                  ) ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($group['users'])): ?>
              <tr><td colspan="3" class="text-center">Brak użytkowników w grupie.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
