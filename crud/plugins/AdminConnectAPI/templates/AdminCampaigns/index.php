<?php $this->assign('title', 'Kampanie'); ?>

<section class="content-header"><h1>Kampanie</h1></section>

<section class="content">
  <div class="row">
    <div class="col-xs-12">

      <div class="box box-default">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-filter"></i> Filtry</h3>
          <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
          </div>
        </div>
        <div class="box-body">
          <?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'index']]) ?>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Szukaj (nazwa)</label>
                <?= $this->Form->control('search', [
                  'label' => false, 'class' => 'form-control', 'placeholder' => 'wpisz…',
                  'value' => $filters['search'] ?? '',
                ]) ?>
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label>Na stronie</label>
                <?= $this->Form->select('per_page', [10 => '10', 20 => '20', 50 => '50'], [
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
          <h3 class="box-title">Lista kampanii <span class="badge"><?= $meta['total'] ?? 0 ?></span></h3>
          <div class="box-tools">
            <?= $this->Html->link('<i class="fa fa-plus"></i> Dodaj kampanię', ['action' => 'add'], ['class' => 'btn btn-primary btn-sm', 'escape' => false]) ?>
          </div>
        </div>
        <div class="box-body table-responsive no-padding">
          <table class="table table-hover">
            <thead>
              <tr><th>Nazwa</th><th>Opis</th><th>Utworzona</th><th class="text-right">Akcje</th></tr>
            </thead>
            <tbody>
              <?php foreach ($campaigns as $campaign): ?>
              <tr>
                <td><?= h($campaign['name']) ?></td>
                <td><?= h($campaign['description'] ?? '') ?></td>
                <td><?= $this->Date->format($campaign['created_at'] ?? null) ?></td>
                <td class="text-right">
                  <?= $this->Html->link('<i class="fa fa-eye"></i>', ['action' => 'view', $campaign['uuid']], ['class' => 'btn btn-xs btn-default', 'escape' => false]) ?>
                  <?= $this->Html->link('<i class="fa fa-pencil"></i>', ['action' => 'edit', $campaign['uuid']], ['class' => 'btn btn-xs btn-info', 'escape' => false]) ?>
                  <?= $this->Form->postLink('<i class="fa fa-trash"></i>', ['action' => 'delete', $campaign['uuid']], ['class' => 'btn btn-xs btn-danger', 'escape' => false, 'confirm' => 'Usunąć kampanię ' . h($campaign['name']) . '?']) ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($campaigns)): ?>
              <tr><td colspan="4" class="text-center">Brak kampanii.</td></tr>
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
