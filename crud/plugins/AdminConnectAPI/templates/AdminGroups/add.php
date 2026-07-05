<?php $this->assign('title', 'Dodaj grupę'); ?>

<section class="content-header"><h1>Dodaj grupę</h1></section>

<section class="content">
  <div class="row">
    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Nowa grupa</h3></div>
        <?= $this->Form->create(null) ?>
        <div class="box-body">
          <div class="form-group">
            <label>Nazwa</label>
            <?= $this->Form->control('name', ['label' => false, 'class' => 'form-control']) ?>
          </div>
          <div class="form-group">
            <label>Opis</label>
            <?= $this->Form->control('description', ['label' => false, 'type' => 'textarea', 'class' => 'form-control', 'rows' => 4]) ?>
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
