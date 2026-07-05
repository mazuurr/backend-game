<?php $this->assign('title', 'Szczegóły kampanii'); ?>

<section class="content-header"><h1><?= h($campaign['name'] ?? '') ?></h1></section>

<section class="content">
  <div class="row">

    <div class="col-md-4">
      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Dane kampanii</h3></div>
        <div class="box-body">
          <dl class="dl-horizontal">
            <dt>UUID</dt><dd><small class="text-muted"><?= h($campaign['uuid'] ?? '') ?></small></dd>
            <dt>Nazwa</dt><dd><?= h($campaign['name'] ?? '') ?></dd>
            <dt>Opis</dt><dd><?= h($campaign['description'] ?? '—') ?></dd>
            <dt>Utworzona</dt><dd><?= $this->Date->format($campaign['created_at'] ?? null) ?></dd>
          </dl>
        </div>
        <div class="box-footer">
          <?= $this->Html->link('<i class="fa fa-pencil"></i> Edytuj', ['action' => 'edit', $campaign['uuid']], ['class' => 'btn btn-info', 'escape' => false]) ?>
          <?= $this->Html->link('Wróć', ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
      </div>
    </div>

    <div class="col-md-8">

      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">
            Puzzle w kampanii
            <span class="badge bg-light-blue"><?= count($campaign['puzzles'] ?? []) ?></span>
          </h3>
        </div>
        <div class="box-body">
          <?php if (!empty($campaign['puzzles'])): ?>
          <div class="row">
            <?php foreach ($campaign['puzzles'] as $puzzle): ?>
            <div class="col-xs-6 col-sm-4 col-md-3" style="margin-bottom:15px;">
              <div class="thumbnail" style="margin:0; position:relative;">
                <a href="<?= $this->Url->build(['plugin' => 'AdminConnectAPI', 'controller' => 'AdminPuzzles', 'action' => 'view', $puzzle['uuid']]) ?>">
                  <img
                    src="<?= $this->Url->build(['plugin' => 'AdminConnectAPI', 'controller' => 'AdminPuzzles', 'action' => 'image', $puzzle['uuid']]) ?>"
                    style="width:100%; height:80px; object-fit:cover;"
                    alt="puzzle"
                  >
                </a>
                <div style="text-align:center; padding:4px;">
                  <?= $this->Form->postLink(
                    '<i class="fa fa-unlink"></i> Odepnij',
                    ['action' => 'removePuzzle', $campaign['uuid'], $puzzle['uuid']],
                    ['class' => 'btn btn-xs btn-danger', 'escape' => false, 'confirm' => 'Odpiąć puzzle od kampanii?']
                  ) ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-muted text-center">Brak puzzli w kampanii.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="box box-warning">
        <div class="box-header with-border">
          <h3 class="box-title">
            Dostępne puzzle
            <span class="badge"><?= count($availablePuzzles) ?></span>
          </h3>
        </div>
        <div class="box-body">
          <?php if (!empty($availablePuzzles)): ?>
          <div class="row">
            <?php foreach ($availablePuzzles as $puzzle): ?>
            <div class="col-xs-6 col-sm-4 col-md-3" style="margin-bottom:15px;">
              <div class="thumbnail" style="margin:0;">
                <img
                  src="<?= $this->Url->build(['plugin' => 'AdminConnectAPI', 'controller' => 'AdminPuzzles', 'action' => 'image', $puzzle['uuid']]) ?>"
                  style="width:100%; height:80px; object-fit:cover;"
                  alt="puzzle"
                >
                <div style="text-align:center; padding:4px;">
                  <?= $this->Form->postLink(
                    '<i class="fa fa-link"></i> Przypisz',
                    ['action' => 'assignPuzzle', $campaign['uuid'], $puzzle['uuid']],
                    ['class' => 'btn btn-xs btn-warning', 'escape' => false, 'confirm' => 'Przypisać puzzle do tej kampanii?']
                  ) ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-muted text-center">Wszystkie puzzle są już przypisane do kampanii.</p>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</section>
