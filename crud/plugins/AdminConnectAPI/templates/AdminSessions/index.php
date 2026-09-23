<?php $this->assign('title', 'Aktywne sesje'); ?>

<section class="content-header">
  <h1>Sesje</h1>
  <div class="pull-right" style="margin-top: -30px;">
    <?= $this->Html->link('<i class="fa fa-bar-chart"></i> Statystyki zakończonych sesji', ['action' => 'stats'], ['class' => 'btn btn-default', 'escape' => false]) ?>
  </div>
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
            <div class="col-sm-3">
              <div class="form-group">
                <label>Status</label>
                <?= $this->Form->select('status', ['' => 'Wszystkie', 'open' => 'Otwarte', 'closed' => 'Zamknięte'], [
                  'class' => 'form-control', 'value' => $filters['status'] ?? '',
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
            Lista sesji
            <span class="badge"><?= $meta['total'] ?? 0 ?></span>
          </h3>
        </div>
        <div class="box-body table-responsive no-padding">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>UUID</th>
                <th>Puzzle</th>
                <th>Widoczność</th>
                <th>Tryb</th>
                <th>Status</th>
                <th>Rozpoczęta</th>
                <th>Wygasa</th>
                <th class="text-right">Akcje</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sessions as $session): ?>
              <tr>
                <td><code><?= h(substr($session['uuid'], 0, 8)) ?>…</code></td>
                <td><code><?= h(substr($session['puzzle_uuid'], 0, 8)) ?>…</code></td>
                <td><?= h($session['visibility']) ?></td>
                <td>
                  <?= $session['mode'] === 'shared'
                    ? '<span class="label label-info">Wspólna</span>'
                    : '<span class="label label-default">Indywidualna</span>' ?>
                </td>
                <td>
                  <?= $session['status'] === 'open'
                    ? '<span class="label label-success">Otwarta</span>'
                    : '<span class="label label-default">Zamknięta</span>' ?>
                </td>
                <td><?= h($session['created_at']) ?></td>
                <td><?= h($session['expires_at']) ?></td>
                <td class="text-right">
                  <?= $this->Html->link('<i class="fa fa-eye"></i>', ['action' => 'view', $session['uuid']], ['class' => 'btn btn-xs btn-default', 'escape' => false, 'title' => 'Szczegóły']) ?>
                  <?php if ($session['status'] === 'open'): ?>
                  <?= $this->Form->postLink('<i class="fa fa-lock"></i>', ['action' => 'close', $session['uuid']], ['class' => 'btn btn-xs btn-warning', 'escape' => false, 'title' => 'Zamknij sesję', 'confirm' => 'Zamknąć tę sesję?']) ?>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($sessions)): ?>
              <tr><td colspan="8" class="text-center">Brak sesji.</td></tr>
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
