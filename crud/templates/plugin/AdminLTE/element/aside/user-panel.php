<?php $identity = $this->request->getAttribute('identity'); ?>
<div class="user-panel">
  <div class="pull-left image">
    <i class="fa fa-user-circle fa-3x" style="color:#aaa; line-height:1.2"></i>
  </div>
  <div class="pull-left info">
    <p><?= h($identity ? $identity->get('username') : '') ?></p>
    <a href="#">
      <i class="fa fa-circle text-success"></i>
      <?= h($identity ? ucfirst($identity->get('role')) : '') ?>
    </a>
  </div>
</div>
