<?php
/**
 * A 0-5 score selector (plus "No score"). Expects $scorePrefix (unique per
 * form on the page) and optionally $scoreSelected (int|null).
 *
 * @var string $scorePrefix
 * @var int|null $scoreSelected
 */
$scoreSelected = $scoreSelected ?? null;
?>
<div class="score-picker d-flex flex-wrap gap-1" role="radiogroup" aria-label="Score out of <?= MAX_SCORE ?>">
  <input type="radio" class="btn-check" name="score" id="<?= $scorePrefix ?>-none" value="" <?= $scoreSelected === null ? 'checked' : '' ?>>
  <label class="btn btn-outline-secondary" for="<?= $scorePrefix ?>-none" title="No score">None</label>
  <?php for ($i = 0; $i <= MAX_SCORE; $i++): ?>
    <input type="radio" class="btn-check" name="score" id="<?= $scorePrefix ?>-<?= $i ?>" value="<?= $i ?>" <?= $scoreSelected === $i ? 'checked' : '' ?>>
    <label class="btn btn-outline-primary score-btn" for="<?= $scorePrefix ?>-<?= $i ?>"><?= $i ?></label>
  <?php endfor; ?>
</div>
