<?php $this->assign('title', 'Dodaj administratora'); ?>

<section class="content-header">
  <h1>Dodaj administratora</h1>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Nowy administrator</h3>
        </div>

        <?= $this->Form->create($admin) ?>
        <div class="box-body">
          <div class="form-group">
            <label>Nazwa użytkownika</label>
            <?= $this->Form->control('username', ['label' => false, 'class' => 'form-control', 'placeholder' => 'Nazwa użytkownika']) ?>
          </div>
          <div class="form-group">
            <label>Email</label>
            <?= $this->Form->control('email', ['label' => false, 'type' => 'email', 'class' => 'form-control', 'placeholder' => 'adres@email.pl']) ?>
          </div>
          <div class="form-group">
            <label>Hasło</label>
            <?= $this->Form->control('password', ['label' => false, 'class' => 'form-control', 'placeholder' => 'Min. 8 znaków']) ?>
          </div>
          <div class="form-group">
            <label>Rola</label>
            <?= $this->Form->control('role', ['label' => false, 'type' => 'select', 'options' => $roles, 'class' => 'form-control']) ?>
          </div>
        </div>

        <div class="box-footer">
          <?= $this->Form->button('<i class="fa fa-save"></i> Zapisz', ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
          <?= $this->Html->link('Anuluj', ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
        <?= $this->Form->end() ?>
      </div>
    </div>
  </div>
</section>
