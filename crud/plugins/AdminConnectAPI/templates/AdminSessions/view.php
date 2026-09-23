<?php $this->assign('title', 'Szczegóły sesji'); ?>

<section class="content-header">
  <h1>Sesja</h1>
</section>

<section class="content">
  <div class="row">
    <div class="col-xs-12">
      <div class="box box-default">
        <div class="box-header with-border">
          <h3 class="box-title">Szczegóły</h3>
          <div class="box-tools">
            <?php if (($session['status'] ?? '') === 'open'): ?>
            <?= $this->Form->postLink('<i class="fa fa-lock"></i> Zamknij sesję', ['action' => 'close', $session['uuid']], [
              'class' => 'btn btn-warning btn-sm', 'escape' => false, 'confirm' => 'Zamknąć tę sesję?',
              'data' => ['redirect' => 'view'],
            ]) ?>
            <?php endif; ?>
            <?= $this->Html->link('<i class="fa fa-arrow-left"></i> Wróć', ['action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
          </div>
        </div>
        <div class="box-body">
          <table class="table">
            <tr><th style="width:220px;">UUID</th><td><code><?= h($session['uuid'] ?? '') ?></code></td></tr>
            <tr><th>Puzzle UUID</th><td><code><?= h($session['puzzle_uuid'] ?? '') ?></code></td></tr>
            <tr><th>Widoczność</th><td><?= h($session['visibility'] ?? '') ?></td></tr>
            <tr>
              <th>Tryb</th>
              <td>
                <?= ($session['mode'] ?? '') === 'shared'
                  ? '<span class="label label-info">Wspólna</span>'
                  : '<span class="label label-default">Indywidualna</span>' ?>
              </td>
            </tr>
            <tr><th>Utworzona przez</th><td><code><?= h($session['created_by_user_uuid'] ?? '') ?></code></td></tr>
            <tr>
              <th>Status</th>
              <td>
                <?= ($session['status'] ?? '') === 'open'
                  ? '<span class="label label-success">Otwarta</span>'
                  : '<span class="label label-default">Zamknięta</span>' ?>
              </td>
            </tr>
            <tr><th>Utworzona</th><td><?= h($session['created_at'] ?? '') ?></td></tr>
            <tr><th>Wygasa</th><td><?= h($session['expires_at'] ?? '') ?></td></tr>
            <tr><th>Zamknięta</th><td><?= h($session['closed_at'] ?? '—') ?></td></tr>
            <tr><th>Fragmenty łącznie</th><td><?= h($session['total_pieces'] ?? '—') ?></td></tr>
            <tr><th>Fragmenty poprawne</th><td><?= h($session['correct_pieces'] ?? '—') ?></td></tr>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
