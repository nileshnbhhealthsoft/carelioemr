<?php

namespace OpenEMR\Modules\LabRequisition;

/**
 * Draws the paper requisition. The same markup serves the entry screen (mode 'edit', real inputs)
 * and the printable page (mode 'print', values as plain text, boxes ticked and locked), so what is
 * entered is exactly what prints. Output goes through OpenEMR's attr()/text() escapers.
 */
class Sheet
{
    private bool $edit;

    /** @param array<string,mixed> $o order values: header fields, 'specimen' and 'tests' code lists, 'detail' map */
    public function __construct(private array $o, string $mode, private string $logoUrl)
    {
        $this->edit = $mode === 'edit';
    }

    public function render(): void
    {
        ?>
<div class="labreq-sheet">
  <div class="brand-header">
    <img src="<?php echo attr($this->logoUrl); ?>" alt="Carelio EMR">
    <h1 class="form-title"><?php echo xlt('Lab Requisition Form'); ?></h1>
  </div>

  <div class="priority">
    <div class="pbox"><?php $this->box('is_urgent', !empty($this->o['is_urgent'])); ?> <?php echo xlt('URGENT'); ?></div>
    <div class="pbox"><?php $this->box('is_stat', !empty($this->o['is_stat'])); ?> <?php echo xlt('STAT'); ?></div>
  </div>

  <div class="patient">
    <div class="pat-left">
      <div class="fld"><label><?php echo xlt('Name'); ?>:</label><?php $this->text('patient_name'); ?></div>
      <div class="fld">
        <label><?php echo xlt('D.O.B.'); ?>:</label>
        <div class="dob-wrap"><?php $this->text('dob'); ?><span class="sub"><?php echo xlt('Day/Month/Year'); ?></span></div>
        <label style="margin-left:8px"><?php echo xlt('Sex'); ?>:</label><?php $this->text('sex', 'max-width:60px'); ?>
      </div>
      <div class="fld"><label><?php echo xlt('Reg. No.'); ?>:</label><?php $this->text('reg_no'); ?></div>
      <div class="fld"><label><?php echo xlt('Address'); ?>:</label><?php $this->text('address'); ?></div>
      <div class="fld"><label><?php echo xlt('Doctor'); ?>:</label><?php $this->text('doctor'); ?></div>
      <div class="fld"><label><?php echo xlt('Patient Contact No.'); ?>:</label><?php $this->text('contact'); ?></div>
    </div>
    <div class="pat-right">
      <div class="fld"><label><?php echo xlt('Date'); ?>:</label><?php $this->text('order_date'); ?></div>
      <div class="fld"><label><?php echo xlt('SPECIMEN'); ?>:</label><?php $this->text('specimen_line1'); ?></div>
      <div class="fld"><label>&nbsp;</label><?php $this->text('specimen_line2'); ?></div>
      <div class="fld"><label><?php echo xlt('Signed'); ?>:</label><?php $this->text('signed'); ?></div>
    </div>
  </div>

  <div class="clinical">
    <label><?php echo xlt('Clinical Details'); ?>:</label>
    <?php if ($this->edit) { ?>
      <textarea name="clinical_details" rows="2" maxlength="2000"><?php echo text($this->o['clinical_details'] ?? ''); ?></textarea>
    <?php } else { ?>
      <div class="ta-print"><?php echo nl2br(text($this->o['clinical_details'] ?? '')); ?></div>
    <?php } ?>
  </div>

  <div class="spec-row">
    <b><?php echo xlt('Specimen'); ?></b>
    <?php foreach (Catalog::specimens() as $s) {
        if (in_array($s['code'], ['bone_marrow', 'cytology'], true)) {
            continue;
        } ?>
      <div class="opt">
        <?php $this->itemBox('specimen', $s['code']); ?><label><?php echo xlt($s['label']); ?></label>
        <?php if ($s['detail']) { ?><span class="fill"><?php $this->detail('specimen_' . $s['code'], 'width:120px'); ?></span><?php } ?>
      </div>
    <?php } ?>
  </div>
  <div class="hist-row">
    <div class="opt"><label><b><?php echo xlt('Histology'); ?></b> (<?php echo xlt('state site'); ?>)</label>
      <span class="fill"><?php $this->text('histology_site', 'width:200px'); ?></span></div>
  </div>
  <div class="hist-row">
    <?php foreach (Catalog::specimens() as $s) {
        if (!in_array($s['code'], ['bone_marrow', 'cytology'], true)) {
            continue;
        } ?>
      <div class="opt">
        <?php $this->itemBox('specimen', $s['code']); ?><label><?php echo xlt($s['label']); ?></label>
        <?php if ($s['detail']) { ?><span class="fill"><?php $this->detail('specimen_' . $s['code'], 'width:240px'); ?></span><?php } ?>
      </div>
    <?php } ?>
  </div>

  <?php foreach (Catalog::sections() as $key => $sec) { ?>
    <div class="<?php echo $key === 'panels' ? 'panels' : 'section'; ?>">
      <div class="grp-title"><?php echo xlt($sec['title']); ?></div>
      <?php if ($sec['layout'] === 'grid') { ?>
        <div class="panel-grid <?php echo attr($key); ?>">
          <?php foreach ($sec['cols'][0]['items'] as $it) { $this->testLine($it); } ?>
        </div>
      <?php } else { ?>
        <div class="cols">
          <?php foreach ($sec['cols'] as $col) { ?>
            <div class="col" style="flex:<?php echo attr((string)$col['flex']); ?>">
              <?php foreach ($col['items'] as $it) { $this->testLine($it); } ?>
            </div>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
  <?php } ?>

  <div class="labuse">
    <div class="r">
      <div class="c"><?php echo xlt('FOR LAB USE ONLY'); ?></div>
      <div class="c"><?php echo xlt('ID NUMBER'); ?>:</div>
      <div class="c"><?php echo xlt('ACCESSION#'); ?></div>
    </div>
    <div class="r">
      <div class="c"><?php echo xlt("DATE REC'D"); ?>:</div>
      <div class="c"><?php echo xlt('DATE ISSUED'); ?>:</div>
      <div class="c"><?php echo xlt('NAME'); ?></div>
    </div>
  </div>
</div>
        <?php
    }

    private function testLine(array $it): void
    {
        ?>
        <div class="tline">
          <?php $this->itemBox('test', $it['code']); ?><label><?php echo text($it['label']); ?></label>
          <?php if ($it['detail']) { ?><span class="inl"><?php $this->detail($it['code']); ?></span><?php } ?>
        </div>
        <?php
    }

    private function text(string $name, string $style = ''): void
    {
        $v = (string)($this->o[$name] ?? '');
        if ($this->edit) {
            echo '<input type="text" name="' . attr($name) . '" value="' . attr($v) . '" maxlength="255"'
                . ($style !== '' ? ' style="' . attr($style) . '"' : '') . '>';
        } else {
            echo '<span class="pv"' . ($style !== '' ? ' style="' . attr($style) . '"' : '') . '>' . text($v) . '</span>';
        }
    }

    /** Free-text line beside a ticked item. In print it shows only when something was typed. */
    private function detail(string $key, string $style = ''): void
    {
        $v = (string)($this->o['detail'][$key] ?? '');
        if ($this->edit) {
            echo '<input type="text" class="dtl" name="detail[' . attr($key) . ']" value="' . attr($v) . '" maxlength="255"'
                . ($style !== '' ? ' style="' . attr($style) . '"' : '') . '>';
        } else {
            echo '<span class="pv dtl-print"' . ($style !== '' ? ' style="' . attr($style) . '"' : '') . '>' . text($v) . '</span>';
        }
    }

    private function box(string $name, bool $checked): void
    {
        echo '<input type="checkbox" name="' . attr($name) . '" value="1"' . ($checked ? ' checked' : '') . ($this->edit ? '' : ' disabled') . '>';
    }

    private function itemBox(string $kind, string $code): void
    {
        $list = $kind === 'test' ? 'tests' : 'specimen';
        $checked = in_array($code, $this->o[$list] ?? [], true);
        echo '<input type="checkbox" name="' . attr($list) . '[]" value="' . attr($code) . '"' . ($checked ? ' checked' : '') . ($this->edit ? '' : ' disabled') . '>';
    }
}
