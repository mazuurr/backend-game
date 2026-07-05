<?php $identity = $this->request->getAttribute('identity'); ?>
<nav class="navbar navbar-static-top">
  <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
    <span class="sr-only">Toggle navigation</span>
    <span class="icon-bar"></span>
    <span class="icon-bar"></span>
    <span class="icon-bar"></span>
  </a>

  <div class="navbar-custom-menu">
    <ul class="nav navbar-nav">
      <li class="dropdown user user-menu">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
          <i class="fa fa-user-circle"></i>
          <span class="hidden-xs"><?= h($identity ? $identity->get('username') : '') ?></span>
        </a>
        <ul class="dropdown-menu">
          <li class="user-header">
            <i class="fa fa-user-circle fa-5x"></i>
            <p>
              <?= h($identity ? $identity->get('username') : '') ?>
              <small><?= h($identity ? ucfirst($identity->get('role')) : '') ?></small>
            </p>
          </li>
          <li class="user-footer">
            <div class="pull-right">
              <?= $this->Html->link('Wyloguj', '/logout', ['class' => 'btn btn-default btn-flat']) ?>
            </div>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</nav>
