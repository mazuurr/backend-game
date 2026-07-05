<?php $this->assign('title', 'Szczegóły puzzla'); ?>

<section class="content-header"><h1>Puzzle</h1></section>

<section class="content">
  <div class="row">
    <div class="col-md-5">

      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Metadane</h3></div>
        <div class="box-body">
          <dl class="dl-horizontal">
            <dt>UUID</dt><dd><small class="text-muted"><?= h($puzzle['uuid'] ?? '') ?></small></dd>
            <dt>Nazwa pliku</dt><dd><?= h($puzzle['original_name'] ?? '') ?></dd>
            <dt>Typ MIME</dt><dd><?= h($puzzle['mime_type'] ?? '') ?></dd>
            <dt>Rozmiar</dt><dd><?= isset($puzzle['size']) ? number_format($puzzle['size'] / 1024, 1) . ' KB' : '' ?></dd>
            <?php
              $diffLabels = [1 => '1 – Łatwy', 2 => '2 – Średni', 3 => '3 – Trudny', 4 => '4 – Bardzo trudny', 5 => '5 – Ekspert'];
              $diffColors = [1 => 'success', 2 => 'info', 3 => 'warning', 4 => 'danger', 5 => 'danger'];
              $d = $puzzle['difficulty'] ?? null;
            ?>
            <dt>Trudność</dt><dd>
              <?php if ($d !== null): ?>
                <span class="label label-<?= $diffColors[$d] ?? 'default' ?>"><?= $diffLabels[$d] ?? $d ?></span>
              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </dd>
            <dt>Łączna l. elementów</dt><dd><?= $puzzle['total_pieces'] !== null ? h($puzzle['total_pieces']) : '<span class="text-muted">—</span>' ?></dd>
            <dt>Elementów na fragment</dt><dd><?= $puzzle['pieces_per_fragment'] !== null ? h($puzzle['pieces_per_fragment']) : '<span class="text-muted">—</span>' ?></dd>
            <dt>Puzzli w poziomie (X)</dt><dd><?= $puzzle['pieces_x'] !== null ? h($puzzle['pieces_x']) : '<span class="text-muted">—</span>' ?></dd>
            <dt>Puzzli w pionie (Y)</dt><dd><?= $puzzle['pieces_y'] !== null ? h($puzzle['pieces_y']) : '<span class="text-muted">—</span>' ?></dd>
            <dt>Utworzone</dt><dd><?= $this->Date->format($puzzle['created_at'] ?? null) ?></dd>
          </dl>
        </div>
        <div class="box-footer">
          <?= $this->Form->postLink('<i class="fa fa-trash"></i> Usuń', ['action' => 'delete', $puzzle['uuid']], ['class' => 'btn btn-danger', 'escape' => false, 'confirm' => 'Usunąć puzzle?']) ?>
          <?= $this->Html->link('Wróć', ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
      </div>

      <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title">Edytuj parametry</h3></div>
        <?= $this->Form->create(null, ['url' => ['action' => 'update', $puzzle['uuid']]]) ?>
        <div class="box-body">
          <div class="form-group">
            <label>Poziom trudności</label>
            <?= $this->Form->select('difficulty',
              ['' => '— brak —', 1 => '1 – Łatwy', 2 => '2 – Średni', 3 => '3 – Trudny', 4 => '4 – Bardzo trudny', 5 => '5 – Ekspert'],
              ['class' => 'form-control', 'value' => $puzzle['difficulty'] ?? '']
            ) ?>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Łączna liczba elementów</label>
                <?= $this->Form->control('total_pieces', [
                  'label' => false, 'type' => 'number', 'min' => 1,
                  'class' => 'form-control', 'placeholder' => 'np. 500',
                  'value' => $puzzle['total_pieces'] ?? '',
                ]) ?>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Elementów na fragment</label>
                <?= $this->Form->control('pieces_per_fragment', [
                  'label' => false, 'type' => 'number', 'min' => 1,
                  'class' => 'form-control', 'placeholder' => 'np. 25',
                  'value' => $puzzle['pieces_per_fragment'] ?? '',
                ]) ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Puzzli w poziomie (X)</label>
                <?= $this->Form->control('pieces_x', [
                  'label' => false, 'type' => 'number', 'min' => 1,
                  'class' => 'form-control', 'placeholder' => 'np. 40',
                  'value' => $puzzle['pieces_x'] ?? '',
                ]) ?>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Puzzli w pionie (Y)</label>
                <?= $this->Form->control('pieces_y', [
                  'label' => false, 'type' => 'number', 'min' => 1,
                  'class' => 'form-control', 'placeholder' => 'np. 50',
                  'value' => $puzzle['pieces_y'] ?? '',
                ]) ?>
              </div>
            </div>
          </div>
        </div>
        <div class="box-footer">
          <?= $this->Form->button('<i class="fa fa-save"></i> Zapisz zmiany', ['class' => 'btn btn-info', 'escapeTitle' => false]) ?>
        </div>
        <?= $this->Form->end() ?>
      </div>

      <?php $assignedCampaignUuid = $puzzle['campaign_uuid'] ?? null; ?>
      <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Kampania</h3></div>
        <div class="box-body">
          <?php if ($assignedCampaignUuid): ?>
            <?php
              $assignedName = '—';
              foreach ($campaigns as $c) {
                  if ($c['uuid'] === $assignedCampaignUuid) { $assignedName = $c['name']; break; }
              }
            ?>
            <p>Przypisano do: <strong><?= h($assignedName) ?></strong></p>
            <?= $this->Form->postLink(
              '<i class="fa fa-times"></i> Odepnij od kampanii',
              ['action' => 'removeCampaign', $puzzle['uuid'], $assignedCampaignUuid],
              ['class' => 'btn btn-warning btn-sm', 'escape' => false, 'confirm' => 'Odpiąć puzzle od kampanii "' . h($assignedName) . '"?']
            ) ?>
          <?php else: ?>
            <p class="text-muted">Puzzle nie jest przypisane do żadnej kampanii.</p>
            <?php if (!empty($campaigns)): ?>
            <?= $this->Form->create(null, ['url' => ['action' => 'assignCampaign', $puzzle['uuid']]]) ?>
            <div class="input-group">
              <?= $this->Form->select('campaign_uuid',
                array_combine(array_column($campaigns, 'uuid'), array_column($campaigns, 'name')),
                ['class' => 'form-control', 'empty' => '— wybierz kampanię —']
              ) ?>
              <span class="input-group-btn">
                <?= $this->Form->button('<i class="fa fa-link"></i> Przypisz', ['class' => 'btn btn-warning', 'escapeTitle' => false]) ?>
              </span>
            </div>
            <?= $this->Form->end() ?>
            <?php else: ?>
            <p class="text-muted"><em>Brak kampanii do przypisania.</em></p>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>
    <div class="col-md-7">
      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Podgląd grafiki</h3></div>
        <div class="box-body text-center">
          <img
            src="<?= $this->Url->build(['action' => 'image', $puzzle['uuid']]) ?>"
            alt="<?= h($puzzle['original_name'] ?? 'puzzle') ?>"
            style="max-width:100%; max-height:500px;"
          >
        </div>
      </div>
    </div>
  </div>
</section>
