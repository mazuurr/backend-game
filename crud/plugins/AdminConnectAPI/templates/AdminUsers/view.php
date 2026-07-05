<?php $this->assign('title', 'Szczegóły użytkownika'); ?>

<section class="content-header">
  <h1><?= h($user['username'] ?? '') ?></h1>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-6">
      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Dane użytkownika</h3></div>
        <div class="box-body">
          <dl class="dl-horizontal">
            <dt>UUID</dt><dd><?= h($user['uuid'] ?? '') ?></dd>
            <dt>Username</dt><dd><?= h($user['username'] ?? '') ?></dd>
            <dt>Email</dt><dd><?= h($user['email'] ?? '') ?></dd>
            <dt>Premium</dt><dd><?= !empty($user['premium']) ? '<span class="label label-warning">Tak</span>' : 'Nie' ?></dd>
            <dt>Aktywny</dt><dd><?= !empty($user['active']) ? '<span class="label label-success">Tak</span>' : '<span class="label label-danger">Nie</span>' ?></dd>
            <dt>Utworzony</dt><dd><?= $this->Date->format($user['created_at'] ?? null) ?></dd>
          </dl>
        </div>
        <div class="box-footer">
          <?= $this->Html->link('<i class="fa fa-pencil"></i> Edytuj', ['action' => 'edit', $user['uuid']], ['class' => 'btn btn-info', 'escape' => false]) ?>
          <?= $this->Html->link('Wróć', ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
      </div>
    </div>
  </div>
</section>
