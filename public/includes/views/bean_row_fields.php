<?php
/**
 * Renders the fields for one bean row. Expects $index (int or the
 * literal string "__INDEX__" when used as the JS clone template)
 * and optionally $bean (associative array of existing values).
 *
 * @var int|string $index
 * @var array $bean
 */
$bean = $bean ?? [];
$get = fn (string $key, string $default = ''): string => htmlspecialchars((string) ($bean[$key] ?? $default), ENT_QUOTES);
?>
<div class="bean-row" data-bean-row>
  <div class="d-flex justify-content-between align-items-start">
    <h2 class="h6">Bean</h2>
    <button type="button" class="btn btn-sm btn-outline-danger remove-bean">Remove</button>
  </div>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Variety / origin</label>
      <input type="text" class="form-control" name="beans[<?= $index ?>][variety]" value="<?= $get('variety') ?>" placeholder="e.g. Ethiopia Yirgacheffe" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Weight (g)</label>
      <input type="number" step="0.01" min="0" class="form-control" name="beans[<?= $index ?>][weight_g]" value="<?= $get('weight_g') ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Roast level</label>
      <select class="form-select" name="beans[<?= $index ?>][roast_level]" required>
        <option value="">Choose…</option>
        <?php foreach (ROAST_LEVELS as $level): ?>
          <option value="<?= $level ?>" <?= ($bean['roast_level'] ?? '') === $level ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $level)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Roast temperature (°)</label>
      <input type="number" step="0.1" class="form-control" name="beans[<?= $index ?>][roast_temperature]" value="<?= $get('roast_temperature') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Roast time (minutes)</label>
      <input type="number" step="0.1" min="0" class="form-control" name="beans[<?= $index ?>][roast_time_minutes]" value="<?= $get('roast_time_minutes') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Roaster temp at start (°)</label>
      <input type="number" step="0.1" class="form-control" name="beans[<?= $index ?>][roaster_start_temperature]" value="<?= $get('roaster_start_temperature') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Ambient temperature (°)</label>
      <input type="number" step="0.1" class="form-control" name="beans[<?= $index ?>][ambient_temperature]" value="<?= $get('ambient_temperature') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Cloud conditions</label>
      <select class="form-select" name="beans[<?= $index ?>][cloud_conditions]">
        <option value="">N/A</option>
        <?php foreach (CLOUD_CONDITIONS as $cond): ?>
          <option value="<?= $cond ?>" <?= ($bean['cloud_conditions'] ?? '') === $cond ? 'selected' : '' ?>><?= ucfirst($cond) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>
