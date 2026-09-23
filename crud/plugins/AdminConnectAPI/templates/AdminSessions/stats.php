<?php
/**
 * @var array $stats
 * @var array $meta
 * @var array $filters
 */
$this->assign('title', 'Statystyki sesji');

if (!function_exists('formatSessionDuration')) {
    function formatSessionDuration(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return sprintf('%dh %02dmin', $h, $m);
        }
        if ($m > 0) {
            return sprintf('%dmin %02ds', $m, $s);
        }
        return sprintf('%ds', $s);
    }
}
?>

<section class="content-header">
  <h1>Statystyki sesji</h1>
  <div class="pull-right" style="margin-top: -30px;">
    <?= $this->Html->link('<i class="fa fa-arrow-left"></i> Wróć do sesji', ['action' => 'index'], ['class' => 'btn btn-default', 'escape' => false]) ?>
  </div>
</section>

<section class="content">
  <div class="row">
    <div class="col-xs-12">

      <div class="callout callout-info">
        Zrzut czasu i postępu zapisywany przed usunięciem zamkniętej sesji (patrz <code>app:sessions:purge</code>).
        Same sesje i ich ruchy są kasowane po okresie retencji — tu zostaje tylko podsumowanie.
      </div>

      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">
            Zakończone sesje
            <span class="badge"><?= $meta['total'] ?? 0 ?></span>
          </h3>
        </div>
        <div class="box-body table-responsive no-padding">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Sesja</th>
                <th>Puzzle</th>
                <th>Twórca</th>
                <th>Tryb</th>
                <th>Postęp</th>
                <th>Czas</th>
                <th>Rozpoczęta</th>
                <th>Zakończona</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($stats as $stat): ?>
              <?php
                $total = (int) ($stat['total_pieces'] ?? 0);
                $correct = (int) ($stat['correct_pieces'] ?? 0);
                $pct = $total > 0 ? (int) round(($correct / $total) * 100) : 0;
              ?>
              <tr>
                <td><code><?= h(substr($stat['session_uuid'], 0, 8)) ?>…</code></td>
                <td><code><?= h(substr($stat['puzzle_uuid'], 0, 8)) ?>…</code></td>
                <td><code><?= h(substr($stat['created_by_user_uuid'], 0, 8)) ?>…</code></td>
                <td>
                  <?= $stat['mode'] === 'shared'
                    ? '<span class="label label-info">Wspólna</span>'
                    : '<span class="label label-default">Indywidualna</span>' ?>
                </td>
                <td>
                  <?= $correct ?> / <?= $total ?>
                  <span class="label <?= $pct >= 100 ? 'label-success' : 'label-default' ?>"><?= $pct ?>%</span>
                </td>
                <td><?= formatSessionDuration((int) ($stat['time_spent_seconds'] ?? 0)) ?></td>
                <td><?= $this->Date->format($stat['started_at'] ?? null) ?></td>
                <td><?= $this->Date->format($stat['finished_at'] ?? null) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($stats)): ?>
              <tr><td colspan="8" class="text-center">Brak statystyk — jeszcze żadna sesja nie została oczyszczona.</td></tr>
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
