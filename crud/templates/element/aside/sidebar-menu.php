<?php
$controller = $this->request->getParam('controller');
$plugin = $this->request->getParam('plugin');
$action = $this->request->getParam('action');
?>
<ul class="sidebar-menu" data-widget="tree">
  <li class="header">MENU</li>

  <li class="<?= $controller === 'Pages' && $action === 'display' ? 'active' : '' ?>">
    <a href="<?= $this->Url->build('/') ?>">
      <i class="fa fa-dashboard"></i> <span>Dashboard</span>
    </a>
  </li>

  <li class="<?= $plugin === null && $controller === 'Admins' ? 'active' : '' ?>">
    <a href="<?= $this->Url->build(['plugin' => false, 'controller' => 'Admins', 'action' => 'index']) ?>">
      <i class="fa fa-user-secret"></i> <span>Administratorzy</span>
    </a>
  </li>

  <li class="header">API</li>

  <li class="<?= $controller === 'AdminUsers' ? 'active' : '' ?>">
    <a href="<?= $this->Url->build(['plugin' => 'AdminConnectAPI', 'controller' => 'AdminUsers', 'action' => 'index']) ?>">
      <i class="fa fa-users"></i> <span>Użytkownicy</span>
    </a>
  </li>

  <li class="<?= $controller === 'AdminGroups' ? 'active' : '' ?>">
    <a href="<?= $this->Url->build(['plugin' => 'AdminConnectAPI', 'controller' => 'AdminGroups', 'action' => 'index']) ?>">
      <i class="fa fa-object-group"></i> <span>Grupy</span>
    </a>
  </li>

  <li class="<?= $controller === 'AdminCampaigns' ? 'active' : '' ?>">
    <a href="<?= $this->Url->build(['plugin' => 'AdminConnectAPI', 'controller' => 'AdminCampaigns', 'action' => 'index']) ?>">
      <i class="fa fa-bullhorn"></i> <span>Kampanie</span>
    </a>
  </li>

  <li class="<?= $controller === 'AdminPuzzles' ? 'active' : '' ?>">
    <a href="<?= $this->Url->build(['plugin' => 'AdminConnectAPI', 'controller' => 'AdminPuzzles', 'action' => 'index']) ?>">
      <i class="fa fa-puzzle-piece"></i> <span>Puzzle</span>
    </a>
  </li>

</ul>
