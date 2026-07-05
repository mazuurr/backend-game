<?php $this->assign('title', 'Edytuj użytkownika'); ?>

<section class="content-header"><h1>Edytuj: <?= h($user['username'] ?? '') ?></h1></section>

<section class="content">
  <div class="row">
    <div class="col-md-6">
      <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Edycja użytkownika</h3></div>
        <?= $this->Form->create(null, ['url' => ['action' => 'edit', $uuid]]) ?>
        <div class="box-body">
          <div class="form-group">
            <label>Username</label>
            <?= $this->Form->control('username', ['label' => false, 'class' => 'form-control', 'value' => $user['username'] ?? '']) ?>
          </div>
          <div class="form-group">
            <label>Email</label>
            <?= $this->Form->control('email', ['label' => false, 'type' => 'email', 'class' => 'form-control', 'value' => $user['email'] ?? '']) ?>
          </div>
          <div class="form-group">
            <label>Nowe hasło <small class="text-muted">(zostaw puste, aby nie zmieniać)</small></label>
            <?= $this->Form->control('password', ['label' => false, 'class' => 'form-control', 'value' => '']) ?>
          </div>
          <div class="form-group">
            <?= $this->Form->control('premium', ['type' => 'checkbox', 'label' => 'Premium', 'checked' => !empty($user['premium'])]) ?>
          </div>
          <div class="form-group">
            <?= $this->Form->control('active', ['type' => 'checkbox', 'label' => 'Aktywny', 'checked' => !empty($user['active'])]) ?>
          </div>
        </div>
        <div class="box-footer">
          <?= $this->Form->button('<i class="fa fa-save"></i> Zapisz zmiany', ['class' => 'btn btn-warning', 'escapeTitle' => false]) ?>
          <?= $this->Html->link('Anuluj', ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
        <?= $this->Form->end() ?>

        <?= $this->Form->postLink(
          '<i class="fa fa-key"></i> Wyślij reset hasła',
          ['action' => 'sendPasswordReset', $uuid],
          [
            'class' => 'btn btn-default btn-sm',
            'escape' => false,
            'confirm' => 'Wysłać email z linkiem resetującym hasło?',
            'data-redirect' => 'edit',
          ]
        ) ?>
      </div>
    </div>
  </div>
</section>
