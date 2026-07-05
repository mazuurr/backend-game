<?php
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
$extraClass = !empty($params['class']) ? ' ' . $params['class'] : '';
?>
<div class="alert alert-info alert-dismissible<?= h($extraClass) ?>">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <i class="icon fa fa-info"></i> <?= $message ?>
</div>
