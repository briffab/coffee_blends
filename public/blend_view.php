<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$currentUser = current_user($pdo);
$blendId = (int) ($_GET['id'] ?? 0);
$blend = get_blend($pdo, $blendId);
if (!$blend) {
    http_response_code(404);
    exit('Blend not found.');
}

function fmt_value($value, string $suffix = ''): string
{
    return $value === null || $value === '' ? '—' : htmlspecialchars((string) $value) . $suffix;
}

$canManage = can_manage_blend($blend, $currentUser);
$tastingNotes = get_tasting_notes($pdo, $blendId);
$rating = get_blend_ratings($pdo, $blendId)[$blendId] ?? null;
$ownerName = display_name(['name' => $blend['owner_name'], 'email' => $blend['owner_email']]);

$pageTitle = $blend['name'];
require __DIR__ . '/includes/views/header.php';
?>
<div class="blend-header mb-3">
  <?php if (!empty($blend['image_path'])): ?>
    <a href="<?= htmlspecialchars($blend['image_path']) ?>" class="blend-photo-link" title="View full size">
      <img src="<?= htmlspecialchars(blend_thumb_url($blend['image_path'])) ?>" alt="" width="120" height="120" decoding="async" class="blend-photo rounded">
    </a>
  <?php endif; ?>
  <div class="blend-header-text">
    <h1 class="h3 mb-1"><?= htmlspecialchars($blend['name']) ?></h1>
    <p class="text-muted mb-2">
      Roasted <?= htmlspecialchars(format_date_for_display($blend['roast_date'])) ?>
      &middot; added by <?= htmlspecialchars($ownerName) ?>
    </p>
    <?php if ($rating !== null): ?>
      <p class="mb-2" title="Average of everyone's scores (each person counts once)">
        <span class="score-star">&#9733;</span> <strong><?= format_score($rating['average']) ?></strong><span class="text-muted">/<?= MAX_SCORE ?></span>
        <span class="text-muted small">&middot; <?= $rating['count'] ?> <?= $rating['count'] === 1 ? 'score' : 'scores' ?></span>
      </p>
    <?php endif; ?>
    <?php if ($canManage): ?>
      <div class="d-flex flex-wrap gap-2">
        <a href="blend_edit.php?id=<?= (int) $blend['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
        <?php if (image_generation_enabled($config)): ?>
          <a href="blend_images.php?id=<?= (int) $blend['id'] ?>" class="btn btn-sm btn-outline-primary">
            <?= !empty($blend['image_path']) ? 'Change image' : 'Create an image' ?>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($blend['description'])): ?>
  <p class="blend-description"><?= nl2br(htmlspecialchars($blend['description'])) ?></p>
<?php endif; ?>

<?php if (!empty($_GET['created'])): ?><div class="alert alert-success">Blend saved.</div><?php endif; ?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success">Changes saved.</div><?php endif; ?>
<?php if (!empty($_GET['image'])): ?><div class="alert alert-success">Image saved.</div><?php endif; ?>

<h2 class="h5">Beans</h2>
<div class="table-responsive mb-4 d-none d-md-block">
  <table class="table bg-white align-middle">
    <thead>
      <tr>
        <th>Variety</th><th>Weight</th><th>Roast level</th><th>Roast temp</th><th>Roast time</th>
        <th>Roaster start temp</th><th>Ambient temp</th><th>Sky</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($blend['beans'] as $bean): ?>
        <tr>
          <td><?= htmlspecialchars($bean['variety']) ?></td>
          <td><?= fmt_value($bean['weight_g'], 'g') ?></td>
          <td><span class="badge bg-secondary"><?= ucwords(str_replace('_', ' ', $bean['roast_level'])) ?></span></td>
          <td><?= fmt_value($bean['roast_temperature'], '°') ?></td>
          <td><?= fmt_value($bean['roast_time_minutes'], ' min') ?></td>
          <td><?= fmt_value($bean['roaster_start_temperature'], '°') ?></td>
          <td><?= fmt_value($bean['ambient_temperature'], '°') ?></td>
          <td><?= $bean['cloud_conditions'] ? ucfirst($bean['cloud_conditions']) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="d-md-none mb-4">
  <?php foreach ($blend['beans'] as $bean): ?>
    <?php
      $facts = [
          'Weight' => fmt_value($bean['weight_g'], 'g'),
          'Roast temp' => fmt_value($bean['roast_temperature'], '°'),
          'Roast time' => fmt_value($bean['roast_time_minutes'], ' min'),
          'Roaster start temp' => fmt_value($bean['roaster_start_temperature'], '°'),
          'Ambient temp' => fmt_value($bean['ambient_temperature'], '°'),
          'Sky' => $bean['cloud_conditions'] ? ucfirst($bean['cloud_conditions']) : '—',
      ];
    ?>
    <div class="bean-card">
      <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <strong class="bean-card-name"><?= htmlspecialchars($bean['variety']) ?></strong>
        <span class="badge bg-secondary flex-shrink-0"><?= ucwords(str_replace('_', ' ', $bean['roast_level'])) ?></span>
      </div>
      <div class="bean-facts">
        <?php foreach ($facts as $label => $value): ?>
          <?php if ($value !== '—'): ?>
            <div class="bean-fact"><span><?= $label ?></span><?= $value ?></div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<h2 class="h5" id="notes">Tasting notes</h2>
<p class="text-muted small mb-2">From <?= htmlspecialchars($ownerName) ?>, who added this blend:</p>
<div class="row">
  <div class="col-md-6 mb-3">
    <h3 class="h6">Espresso</h3>
    <p class="bg-white p-3 rounded border"><?= $blend['espresso_notes'] !== null && $blend['espresso_notes'] !== '' ? nl2br(htmlspecialchars($blend['espresso_notes'])) : '<span class="text-muted">No notes yet.</span>' ?></p>
  </div>
  <div class="col-md-6 mb-3">
    <h3 class="h6">Milky drinks</h3>
    <p class="bg-white p-3 rounded border"><?= $blend['milky_notes'] !== null && $blend['milky_notes'] !== '' ? nl2br(htmlspecialchars($blend['milky_notes'])) : '<span class="text-muted">No notes yet.</span>' ?></p>
  </div>
</div>

<?php foreach ($tastingNotes as $note): ?>
  <?php
    $isAuthor = (int) $note['user_id'] === (int) $currentUser['id'];
    $authorName = display_name(['name' => $note['author_name'], 'email' => $note['author_email']]);
  ?>
  <div class="bg-white p-3 rounded border mb-3">
    <div class="d-flex justify-content-between">
      <strong><?= htmlspecialchars($authorName) ?><?= $isAuthor ? ' <span class="badge bg-light text-dark border">You</span>' : '' ?></strong>
      <span class="text-muted small"><?= htmlspecialchars(format_date_for_display(substr((string) $note['created_at'], 0, 10))) ?></span>
    </div>
    <?php if ($note['score'] !== null): ?>
      <div class="mt-1"><span class="score-star">&#9733;</span> <strong><?= (int) $note['score'] ?></strong><span class="text-muted">/<?= MAX_SCORE ?></span></div>
    <?php endif; ?>
    <div class="row mt-2">
      <?php if ($note['espresso_notes'] !== null): ?>
        <div class="col-md-6"><div class="small text-muted">Espresso</div><?= nl2br(htmlspecialchars($note['espresso_notes'])) ?></div>
      <?php endif; ?>
      <?php if ($note['milky_notes'] !== null): ?>
        <div class="col-md-6"><div class="small text-muted">Milky drinks</div><?= nl2br(htmlspecialchars($note['milky_notes'])) ?></div>
      <?php endif; ?>
    </div>
    <?php if ($isAuthor || (int) $currentUser['is_admin'] === 1): ?>
      <div class="d-flex gap-3 align-items-start mt-3">
        <?php if ($isAuthor): ?>
          <details class="flex-grow-1">
            <summary class="small text-primary" style="cursor: pointer;">Edit</summary>
            <form method="post" action="tasting_notes.php" class="mt-2">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="note_id" value="<?= (int) $note['id'] ?>">
              <div class="mb-2">
                <label class="form-label small">Score</label>
                <?php $scorePrefix = 'edit' . (int) $note['id']; $scoreSelected = $note['score'] === null ? null : (int) $note['score']; require __DIR__ . '/includes/views/score_picker.php'; ?>
              </div>
              <div class="row g-2">
                <div class="col-md-6"><label class="form-label small">Espresso</label><textarea class="form-control" name="espresso_notes" rows="3" maxlength="<?= TASTING_NOTE_MAX_LENGTH ?>"><?= htmlspecialchars((string) $note['espresso_notes']) ?></textarea></div>
                <div class="col-md-6"><label class="form-label small">Milky drinks</label><textarea class="form-control" name="milky_notes" rows="3" maxlength="<?= TASTING_NOTE_MAX_LENGTH ?>"><?= htmlspecialchars((string) $note['milky_notes']) ?></textarea></div>
              </div>
              <button type="submit" class="btn btn-sm btn-primary mt-2">Save</button>
            </form>
          </details>
        <?php endif; ?>
        <form method="post" action="tasting_notes.php" onsubmit="return confirm('Delete this tasting note?');" class="ms-auto">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="note_id" value="<?= (int) $note['id'] ?>">
          <button type="submit" class="btn btn-link btn-sm text-danger p-0">Delete</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<div class="card mb-4">
  <div class="card-body">
    <h3 class="h6">Add your score and tasting notes</h3>
    <p class="text-muted small">Tried this blend? Give it a score out of <?= MAX_SCORE ?> and/or say what you thought &mdash; both are optional, but add at least one. It shows up here with your name and doesn't change anything else.</p>
    <form method="post" action="tasting_notes.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="blend_id" value="<?= $blendId ?>">
      <div class="mb-3">
        <label class="form-label">Score <span class="text-muted">(0 to <?= MAX_SCORE ?>)</span></label>
        <?php $scorePrefix = 'add'; $scoreSelected = null; require __DIR__ . '/includes/views/score_picker.php'; ?>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="espresso_notes">Espresso</label>
          <textarea class="form-control" id="espresso_notes" name="espresso_notes" rows="3" maxlength="<?= TASTING_NOTE_MAX_LENGTH ?>"></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="milky_notes">Milky drinks</label>
          <textarea class="form-control" id="milky_notes" name="milky_notes" rows="3" maxlength="<?= TASTING_NOTE_MAX_LENGTH ?>"></textarea>
        </div>
      </div>
      <button type="submit" class="btn btn-primary mt-3">Add my score &amp; notes</button>
    </form>
  </div>
</div>

<a href="home.php" class="btn btn-link ps-0">&larr; Back to all blends</a>

<?php require __DIR__ . '/includes/views/footer.php'; ?>
