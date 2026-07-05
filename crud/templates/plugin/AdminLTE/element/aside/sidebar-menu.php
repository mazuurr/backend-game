<ul class="sidebar-menu" data-widget="tree">
  <li class="header">PANEL ADMINISTRACYJNY</li>
  <li class="<?= $this->request->getParam('controller') === 'Admins' ? 'active' : '' ?>">
    <a href="<?= $this->Url->build(['controller' => 'Admins', 'action' => 'index']) ?>">
      <i class="fa fa-users"></i> <span>Administratorzy</span>
    </a>
  </li>
</ul>
