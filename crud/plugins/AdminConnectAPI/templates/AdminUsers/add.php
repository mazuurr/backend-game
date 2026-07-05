<?php $this->assign('title', 'Dodaj użytkownika'); ?>

<section class="content-header"><h1>Dodaj użytkownika</h1></section>

<section class="content">
  <div class="row">
    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Nowy użytkownik</h3></div>
        <?= $this->Form->create(null) ?>
        <div class="box-body">
          <div class="form-group">
            <label>Username</label>
            <?= $this->Form->control('username', ['label' => false, 'class' => 'form-control']) ?>
          </div>
          <div class="form-group">
            <label>Email</label>
            <?= $this->Form->control('email', ['label' => false, 'type' => 'email', 'class' => 'form-control']) ?>
          </div>
          <div class="form-group">
            <label>Hasło</label>
            <?= $this->Form->control('password', ['label' => false, 'class' => 'form-control']) ?>
          </div>
          <div class="form-group">
            <?= $this->Form->control('premium', ['type' => 'checkbox', 'label' => 'Premium']) ?>
          </div>
          <div class="form-group">
            <?= $this->Form->control('active', ['type' => 'checkbox', 'label' => 'Aktywny', 'checked' => true]) ?>
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
