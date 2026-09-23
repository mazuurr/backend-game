<?php $this->assign('title', 'Dashboard'); ?>

<section class="content-header">
  <h1>Dashboard</h1>
</section>

<section class="content">
  <div class="row">
    <div class="col-lg-3 col-xs-6">
      <div class="small-box bg-aqua">
        <div class="inner">
          <h3>&nbsp;</h3>
          <p>Użytkownicy</p>
        </div>
        <div class="icon"><i class="fa fa-users"></i></div>
        <a href="<?= $this->Url->build(['plugin' => 'AdminConnectAPI', 'controller' => 'AdminUsers', 'action' => 'index']) ?>" class="small-box-footer">
          Przejdź <i class="fa fa-arrow-circle-right"></i>
        </a>
      </div>
    </div>

    <div class="col-lg-3 col-xs-6">
      <div class="small-box bg-yellow">
        <div class="inner">
          <h3>&nbsp;</h3>
          <p>Puzzle</p>
        </div>
        <div class="icon"><i class="fa fa-puzzle-piece"></i></div>
        <a href="<?= $this->Url->build(['plugin' => 'AdminConnectAPI', 'controller' => 'AdminPuzzles', 'action' => 'index']) ?>" class="small-box-footer">
          Przejdź <i class="fa fa-arrow-circle-right"></i>
        </a>
      </div>
    </div>

  </div>
</section>
