<?php $this->assign('title', 'Puzzel Admin'); ?>

<?= $this->Flash->render() ?>

<?= $this->Form->create(null, ['url' => '/login']) ?>
  <div class="form-group has-feedback">
    <?= $this->Form->control('username', [
      'label' => false,
      'placeholder' => 'Nazwa użytkownika',
      'class' => 'form-control',
      'autofocus' => true,
    ]) ?>
    <span class="fa fa-user form-control-feedback"></span>
  </div>
  <div class="form-group has-feedback">
    <?= $this->Form->control('password', [
      'label' => false,
      'placeholder' => 'Hasło',
      'class' => 'form-control',
    ]) ?>
    <span class="fa fa-lock form-control-feedback"></span>
  </div>
  <div class="row">
    <div class="col-xs-12">
      <?= $this->Form->button('Zaloguj się', [
        'class' => 'btn btn-primary btn-block btn-flat',
      ]) ?>
    </div>
  </div>
<?= $this->Form->end() ?>
