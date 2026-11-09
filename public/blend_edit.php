<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$currentUser = current_user($pdo);
$blendId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$existing = get_blend($pdo, $blendId);
if (!$existing) {
    http_response_code(404);
    exit('Blend not found.');
}
if (!can_manage_blend($existing, $currentUser)) {
    http_response_code(403);
    exit('Only the person who added this blend, or an admin, can edit it.');
}

$errors = [];
$blend = [
    'name' => $existing['name'],
    'roast_date' => $existing['roast_date'],
    'espresso_notes' => $existing['espresso_notes'],
    'milky_notes' => $existing['milky_notes'],
];
$beans = $existing['beans'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $parsed = parse_blend_submission($_POST);
    $errors = $parsed['errors'];
    $blend = $parsed['blend'];
    $beans = $parsed['beans'] ?: [[]];

    if (empty($errors)) {
        save_blend($pdo, (int) $existing['user_id'], $blend, $parsed['beans'], $blendId);
        if (!empty($_POST['regenerate_image']) && image_generation_enabled($config)) {
            header('Location: blend_images.php?id=' . $blendId);
        } else {
            header('Location: blend_view.php?id=' . $blendId . '&updated=1');
        }
        exit;
    }
}

$pageTitle = 'Edit blend';
require __DIR__ . '/includes/views/header.php';
?>
<h1 class="h3 mb-4">Edit blend</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" novalidate id="blend-form">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $blendId ?>">
  <div class="row g-3 mb-3">
    <div class="col-md-8">
      <label class="form-label">Blend name</label>
      <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($blend['name']) ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Roast date</label>
      <input type="date" class="form-control" name="roast_date" value="<?= htmlspecialchars($blend['roast_date']) ?>" required>
    </div>
  </div>

  <h2 class="h5 mt-4">Beans</h2>
  <div id="bean-rows">
    <?php foreach ($beans as $i => $bean): ?>
      <?php $index = $i; require __DIR__ . '/includes/views/bean_row_fields.php'; ?>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-outline-primary mb-4" id="add-bean">+ Add another bean</button>

  <h2 class="h5">Tasting notes</h2>
  <div class="mb-3">
    <label class="form-label">Espresso notes</label>
    <textarea class="form-control" name="espresso_notes" rows="3"><?= htmlspecialchars($blend['espresso_notes'] ?? '') ?></textarea>
  </div>
  <div class="mb-4">
    <label class="form-label">Milky drink notes</label>
    <textarea class="form-control" name="milky_notes" rows="3"><?= htmlspecialchars($blend['milky_notes'] ?? '') ?></textarea>
  </div>

  <?php if (image_generation_enabled($config)): ?>
    <div class="form-check mb-4">
      <input class="form-check-input" type="checkbox" name="regenerate_image" value="1" id="regenerate_image">
      <label class="form-check-label" for="regenerate_image">
        Choose a new image after saving
        <span class="text-muted">(you'll review the prompt first; nothing is generated until you click Generate<?= !empty($existing['image_path']) ? ', and your current image is kept unless you pick a new one' : '' ?>)</span>
      </label>
    </div>
  <?php endif; ?>

  <button type="submit" class="btn btn-primary">Save changes</button>
  <a href="blend_view.php?id=<?= $blendId ?>" class="btn btn-link">Cancel</a>
</form>

<template id="bean-template">
  <?php $index = '__INDEX__'; $bean = []; require __DIR__ . '/includes/views/bean_row_fields.php'; ?>
</template>

<div class="mt-5 pt-4 border-top">
  <form method="post" action="blend_delete.php" onsubmit="return confirm('Delete this blend? This cannot be undone.');">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $blendId ?>">
    <button type="submit" class="btn btn-outline-danger btn-sm">Delete this blend</button>
  </form>
</div>

<script src="<?= asset_url('assets/js/blend-form.js') ?>"></script>
<?php require __DIR__ . '/includes/views/footer.php'; ?>
