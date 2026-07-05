<?php $this->assign('title', 'Użytkownicy'); ?>

<section class="content-header">
  <h1>Użytkownicy</h1>
</section>

<section class="content">
  <div class="row">
    <div class="col-xs-12">

      <div class="box box-default">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-filter"></i> Filtry</h3>
          <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
              <i class="fa fa-minus"></i>
            </button>
          </div>
        </div>
        <div class="box-body">
          <?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'index']]) ?>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label>Szukaj (login / email)</label>
                <?= $this->Form->control('search', [
                  'label' => false, 'class' => 'form-control', 'placeholder' => 'wpisz…',
                  'value' => $filters['search'] ?? '',
                ]) ?>
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label>Aktywny</label>
                <?= $this->Form->select('active', ['' => 'Wszyscy', '1' => 'Tak', '0' => 'Nie'], [
                  'class' => 'form-control', 'value' => $filters['active'] ?? '',
                ]) ?>
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label>Premium</label>
                <?= $this->Form->select('premium', ['' => 'Wszyscy', '1' => 'Tak', '0' => 'Nie'], [
                  'class' => 'form-control', 'value' => $filters['premium'] ?? '',
                ]) ?>
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label>Na stronie</label>
                <?= $this->Form->select('per_page', [10 => '10', 20 => '20', 50 => '50', 100 => '100'], [
                  'class' => 'form-control', 'value' => $filters['per_page'] ?? 20,
                ]) ?>
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label>&nbsp;</label>
                <div>
                  <?= $this->Form->button('<i class="fa fa-search"></i> Szukaj', ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
                  <?= $this->Html->link('<i class="fa fa-times"></i>', ['action' => 'index'], ['class' => 'btn btn-default', 'escape' => false, 'title' => 'Wyczyść']) ?>
                </div>
              </div>
            </div>
          </div>
          <?= $this->Form->end() ?>
        </div>
      </div>

      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">
            Lista użytkowników
            <span class="badge"><?= $meta['total'] ?? 0 ?></span>
          </h3>
          <div class="box-tools">
            <?= $this->Html->link('<i class="fa fa-plus"></i> Dodaj użytkownika', ['action' => 'add'], ['class' => 'btn btn-primary btn-sm', 'escape' => false]) ?>
          </div>
        </div>
        <div class="box-body table-responsive no-padding">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Premium</th>
                <th>Aktywny</th>
                <th class="text-right">Akcje</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
              <tr>
                <td><?= h($user['username']) ?></td>
                <td><?= h($user['email']) ?></td>
                <td><?= !empty($user['premium']) ? '<span class="label label-warning">Tak</span>' : '<span class="label label-default">Nie</span>' ?></td>
                <td><?= !empty($user['active']) ? '<span class="label label-success">Tak</span>' : '<span class="label label-danger">Nie</span>' ?></td>
                <td class="text-right">
                  <?= $this->Html->link('<i class="fa fa-eye"></i>', ['action' => 'view', $user['uuid']], ['class' => 'btn btn-xs btn-default', 'escape' => false, 'title' => 'Szczegóły']) ?>
                  <?= $this->Html->link('<i class="fa fa-pencil"></i>', ['action' => 'edit', $user['uuid']], ['class' => 'btn btn-xs btn-info', 'escape' => false, 'title' => 'Edytuj']) ?>
                  <?php if (!empty($user['active'])): ?>
                  <?= $this->Form->postLink('<i class="fa fa-ban"></i>', ['action' => 'deactivate', $user['uuid']], ['class' => 'btn btn-xs btn-warning', 'escape' => false, 'title' => 'Dezaktywuj', 'confirm' => 'Dezaktywować użytkownika ' . h($user['username']) . '?']) ?>
                  <?php else: ?>
                  <?= $this->Form->postLink('<i class="fa fa-check"></i>', ['action' => 'activate', $user['uuid']], ['class' => 'btn btn-xs btn-success', 'escape' => false, 'title' => 'Aktywuj', 'confirm' => 'Aktywować użytkownika ' . h($user['username']) . '?']) ?>
                  <?php endif; ?>
                  <?= $this->Form->postLink('<i class="fa fa-key"></i>', ['action' => 'sendPasswordReset', $user['uuid']], ['class' => 'btn btn-xs btn-default', 'escape' => false, 'title' => 'Wyślij reset hasła', 'confirm' => 'Wysłać email z resetem hasła do ' . h($user['username']) . '?']) ?>
                  <?= $this->Form->postLink('<i class="fa fa-trash"></i>', ['action' => 'delete', $user['uuid']], ['class' => 'btn btn-xs btn-danger', 'escape' => false, 'title' => 'Usuń', 'confirm' => 'Usunąć użytkownika ' . h($user['username']) . '?']) ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($users)): ?>
              <tr><td colspan="5" class="text-center">Brak użytkowników.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="box-footer">
          <?= $this->element('api_pagination', ['meta' => $meta, 'filters' => $filters]) ?>
        </div>
      </div>

    </div>
  </div>
</section>
