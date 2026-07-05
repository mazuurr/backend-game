<?php
/**
 * @var array $meta  ['page' => int, 'pages' => int, 'total' => int, 'per_page' => int]
 * @var array $filters  current filter params to preserve in links
 */
$page   = $meta['page']   ?? 1;
$pages  = $meta['pages']  ?? 1;
$total  = $meta['total']  ?? 0;
$perPage = $meta['per_page'] ?? 20;

if ($pages <= 1 && $total <= $perPage) return;

$base = array_filter($filters, fn($v) => $v !== '' && $v !== null);
unset($base['page']);
?>
<div class="row" style="margin-top:10px;">
  <div class="col-sm-5">
    <div class="dataTables_info">
      Wyniki <?= (($page - 1) * $perPage) + 1 ?>–<?= min($page * $perPage, $total) ?> z <?= $total ?>
    </div>
  </div>
  <div class="col-sm-7">
    <nav>
      <ul class="pagination pagination-sm no-margin pull-right">

        <li class="<?= $page <= 1 ? 'disabled' : '' ?>">
          <a href="<?= $this->Url->build(array_merge($base, ['page' => $page - 1])) ?>">&laquo;</a>
        </li>

        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?>
        <li><a href="<?= $this->Url->build(array_merge($base, ['page' => 1])) ?>">1</a></li>
        <?php if ($start > 2): ?><li class="disabled"><span>…</span></li><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
        <li class="<?= $i === $page ? 'active' : '' ?>">
          <a href="<?= $this->Url->build(array_merge($base, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
        <?php if ($end < $pages - 1): ?><li class="disabled"><span>…</span></li><?php endif; ?>
        <li><a href="<?= $this->Url->build(array_merge($base, ['page' => $pages])) ?>"><?= $pages ?></a></li>
        <?php endif; ?>

        <li class="<?= $page >= $pages ? 'disabled' : '' ?>">
          <a href="<?= $this->Url->build(array_merge($base, ['page' => $page + 1])) ?>">&raquo;</a>
        </li>

      </ul>
    </nav>
  </div>
</div>
