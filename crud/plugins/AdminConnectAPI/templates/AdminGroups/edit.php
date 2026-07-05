<?php $this->assign('title', 'Edytuj grupę'); ?>

<section class="content-header"><h1>Edytuj: <?= h($group['name'] ?? '') ?></h1></section>

<section class="content">
  <div class="row">
    <div class="col-md-6">
      <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Edycja grupy</h3></div>
        <?= $this->Form->create(null, ['url' => ['action' => 'edit', $uuid]]) ?>
        <div class="box-body">
          <div class="form-group">
            <label>Nazwa</label>
            <?= $this->Form->control('name', ['label' => false, 'class' => 'form-control', 'value' => $group['name'] ?? '']) ?>
          </div>
          <div class="form-group">
            <label>Opis</label>
            <?= $this->Form->control('description', ['label' => false, 'type' => 'textarea', 'class' => 'form-control', 'rows' => 4, 'value' => $group['description'] ?? '']) ?>
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
