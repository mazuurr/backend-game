<?php $this->assign('title', 'Puzzle'); ?>

<section class="content-header"><h1>Puzzle</h1></section>

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
                  <?= $this->Form->button('<i class="fa fa-search"></i> Filtruj', ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
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
          <h3 class="box-title">Lista puzzli <span class="badge"><?= $meta['total'] ?? 0 ?></span></h3>
          <div class="box-tools">
            <?= $this->Html->link('<i class="fa fa-plus"></i> Dodaj puzzle', ['action' => 'add'], ['class' => 'btn btn-primary btn-sm', 'escape' => false]) ?>
          </div>
        </div>
        <div class="box-body table-responsive no-padding">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Podgląd</th>
                <th>UUID</th>
                <th>Trudność</th>
                <th>Elementów</th>
                <th>Na fragment</th>
                <th>Siatka (X×Y)</th>
                <th>Utworzone</th>
                <th class="text-right">Akcje</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($puzzles as $puzzle): ?>
              <tr>
                <td>
                  <a href="<?= $this->Url->build(['action' => 'view', $puzzle['uuid']]) ?>">
                    <img src="<?= $this->Url->build(['action' => 'image', $puzzle['uuid']]) ?>"
                         style="height:40px; width:auto; border-radius:3px;">
                  </a>
                </td>
                <td><small class="text-muted"><?= h($puzzle['uuid']) ?></small></td>
                <td>
                  <?php
                    $diffLabels = [1 => '1 – Łatwy', 2 => '2 – Średni', 3 => '3 – Trudny', 4 => '4 – Bardzo trudny', 5 => '5 – Ekspert'];
                    $diffColors = [1 => 'success', 2 => 'info', 3 => 'warning', 4 => 'danger', 5 => 'danger'];
                    $d = $puzzle['difficulty'] ?? null;
                  ?>
                  <?php if ($d !== null): ?>
                    <span class="label label-<?= $diffColors[$d] ?? 'default' ?>"><?= $diffLabels[$d] ?? $d ?></span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td><?= $puzzle['total_pieces'] !== null ? h($puzzle['total_pieces']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $puzzle['pieces_per_fragment'] !== null ? h($puzzle['pieces_per_fragment']) : '<span class="text-muted">—</span>' ?></td>
                <td>
                  <?php $px = $puzzle['pieces_x'] ?? null; $py = $puzzle['pieces_y'] ?? null; ?>
                  <?= ($px !== null && $py !== null) ? h($px) . '×' . h($py) : '<span class="text-muted">—</span>' ?>
                </td>
                <td><?= $this->Date->format($puzzle['created_at'] ?? null) ?></td>
                <td class="text-right">
                  <?= $this->Html->link('<i class="fa fa-eye"></i>', ['action' => 'view', $puzzle['uuid']], ['class' => 'btn btn-xs btn-default', 'escape' => false, 'title' => 'Szczegóły']) ?>
                  <?= $this->Form->postLink('<i class="fa fa-trash"></i>', ['action' => 'delete', $puzzle['uuid']], ['class' => 'btn btn-xs btn-danger', 'escape' => false, 'confirm' => 'Usunąć puzzle?', 'title' => 'Usuń']) ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($puzzles)): ?>
              <tr><td colspan="8" class="text-center">Brak puzzli.</td></tr>
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
