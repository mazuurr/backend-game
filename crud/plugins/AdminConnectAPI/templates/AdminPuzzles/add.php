<?php $this->assign('title', 'Dodaj puzzle'); ?>

<section class="content-header"><h1>Dodaj puzzle</h1></section>

<section class="content">
  <div class="row">
    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Upload grafiki</h3></div>
        <?= $this->Form->create(null, ['type' => 'file']) ?>
        <div class="box-body">
          <div class="form-group">
            <label>Grafika (JPG, PNG, GIF, WebP, SVG)</label>
            <?= $this->Form->control('image', ['label' => false, 'type' => 'file', 'class' => 'form-control']) ?>
          </div>
          <div class="form-group">
            <label>Poziom trudności <small class="text-muted">(opcjonalnie)</small></label>
            <?= $this->Form->select('difficulty',
              ['' => '— brak —', 1 => '1 – Łatwy', 2 => '2 – Średni', 3 => '3 – Trudny', 4 => '4 – Bardzo trudny', 5 => '5 – Ekspert'],
              ['class' => 'form-control']
            ) ?>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Łączna liczba elementów <small class="text-muted">(opcjonalnie)</small></label>
                <?= $this->Form->control('total_pieces', ['label' => false, 'type' => 'number', 'min' => 1, 'class' => 'form-control', 'placeholder' => 'np. 500']) ?>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Elementów na fragment <small class="text-muted">(opcjonalnie)</small></label>
                <?= $this->Form->control('pieces_per_fragment', ['label' => false, 'type' => 'number', 'min' => 1, 'class' => 'form-control', 'placeholder' => 'np. 25']) ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Puzzli w poziomie (X) <small class="text-muted">(opcjonalnie)</small></label>
                <?= $this->Form->control('pieces_x', ['label' => false, 'type' => 'number', 'min' => 1, 'class' => 'form-control', 'placeholder' => 'np. 40']) ?>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Puzzli w pionie (Y) <small class="text-muted">(opcjonalnie)</small></label>
                <?= $this->Form->control('pieces_y', ['label' => false, 'type' => 'number', 'min' => 1, 'class' => 'form-control', 'placeholder' => 'np. 50']) ?>
              </div>
            </div>
          </div>
        </div>
        <div class="box-footer">
          <?= $this->Form->button('<i class="fa fa-upload"></i> Wyślij', ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
          <?= $this->Html->link('Anuluj', ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
        <?= $this->Form->end() ?>
      </div>
    </div>
  </div>
</section>
