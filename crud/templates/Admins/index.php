<?php $this->assign('title', 'Administratorzy'); ?>

<section class="content-header">
  <h1>Administratorzy</h1>
</section>

<section class="content">
  <div class="row">
    <div class="col-xs-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Lista administratorów</h3>
          <div class="box-tools">
            <?= $this->Html->link(
              '<i class="fa fa-plus"></i> Dodaj administratora',
              ['action' => 'add'],
              ['class' => 'btn btn-primary btn-sm', 'escape' => false]
            ) ?>
          </div>
        </div>

        <div class="box-body table-responsive no-padding">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>Nazwa użytkownika</th>
                <th>Email</th>
                <th>Rola</th>
                <th>Utworzony</th>
                <th class="text-right">Akcje</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($admins as $admin): ?>
              <tr>
                <td><?= h($admin->id) ?></td>
                <td><?= h($admin->username) ?></td>
                <td><?= h($admin->email) ?></td>
                <td>
                  <?php
                    $labels = ['superadmin' => 'danger', 'admin' => 'primary', 'moderator' => 'default'];
                    $label = $labels[$admin->role] ?? 'default';
                  ?>
                  <span class="label label-<?= $label ?>"><?= h(ucfirst($admin->role)) ?></span>
                </td>
                <td><?= h($admin->created?->format('Y-m-d H:i')) ?></td>
                <td class="text-right">
                  <?= $this->Html->link(
                    '<i class="fa fa-pencil"></i>',
                    ['action' => 'edit', $admin->id],
                    ['class' => 'btn btn-xs btn-info', 'escape' => false, 'title' => 'Edytuj']
                  ) ?>
                  <?= $this->Form->postLink(
                    '<i class="fa fa-trash"></i>',
                    ['action' => 'delete', $admin->id],
                    [
                      'class' => 'btn btn-xs btn-danger',
                      'escape' => false,
                      'title' => 'Usuń',
                      'confirm' => 'Czy na pewno chcesz usunąć administratora "' . h($admin->username) . '"?',
                    ]
                  ) ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($admins->toArray())): ?>
              <tr>
                <td colspan="6" class="text-center">Brak administratorów.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="box-footer clearfix">
          <?= $this->Paginator->numbers(['class' => 'pagination pagination-sm no-margin pull-right']) ?>
        </div>
      </div>
    </div>
  </div>
</section>
