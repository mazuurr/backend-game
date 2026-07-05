<?php $this->assign('title', 'Edytuj administratora'); ?>

<section class="content-header">
  <h1>Edytuj: <?= h($admin->username) ?></h1>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-6">
      <div class="box box-warning">
        <div class="box-header with-border">
          <h3 class="box-title">Edycja administratora</h3>
        </div>

        <?= $this->Form->create($admin, ['url' => ['action' => 'edit', $admin->id]]) ?>
        <div class="box-body">
          <div class="form-group">
            <label>Nazwa użytkownika</label>
            <?= $this->Form->control('username', ['label' => false, 'class' => 'form-control']) ?>
          </div>
          <div class="form-group">
            <label>Email</label>
            <?= $this->Form->control('email', ['label' => false, 'type' => 'email', 'class' => 'form-control']) ?>
          </div>
          <div class="form-group">
            <label>Nowe hasło <small class="text-muted">(zostaw puste, aby nie zmieniać)</small></label>
            <?= $this->Form->control('password', ['label' => false, 'class' => 'form-control', 'placeholder' => 'Min. 8 znaków', 'value' => '']) ?>
          </div>
          <div class="form-group">
            <label>Rola</label>
            <?= $this->Form->control('role', ['label' => false, 'type' => 'select', 'options' => $roles, 'class' => 'form-control']) ?>
          </div>
        </div>

        <div class="box-footer">
          <?= $this->Form->button('<i class="fa fa-save"></i> Zapisz zmiany', ['class' => 'btn btn-warning', 'escapeTitle' => false]) ?>
          <?= $this->Html->link('Anuluj', ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
        <?= $this->Form->end() ?>
      </div>
    </div>
  </div>
</section>
