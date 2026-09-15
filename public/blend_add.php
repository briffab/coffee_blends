<?php

declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require_login();

$currentUser = current_user($pdo);
$errors = [];
$blend = ['name' => '', 'roast_date' => date('Y-m-d'), 'espresso_notes' => '', 'milky_notes' => ''];
$beans = [[]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $parsed = parse_blend_submission($_POST);
    $errors = $parsed['errors'];
    $blend = $parsed['blend'];
    $beans = $parsed['beans'] ?: [[]];

    if (empty($errors)) {
        $blendId = save_blend($pdo, current_user_id(), $blend, $parsed['beans']);
        header('Location: blend_view.php?id=' . $blendId . '&created=1');
        exit;
    }
}

$pageTitle = 'Add blend';
require __DIR__ . '/../src/views/header.php';
?>
<h1 class="h3 mb-4">Add a blend</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" novalidate id="blend-form">
  <?= csrf_field() ?>
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
      <?php $index = $i; require __DIR__ . '/../src/views/bean_row_fields.php'; ?>
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

  <button type="submit" class="btn btn-primary">Save blend</button>
  <a href="home.php" class="btn btn-link">Cancel</a>
</form>

<template id="bean-template">
  <?php $index = '__INDEX__'; $bean = []; require __DIR__ . '/../src/views/bean_row_fields.php'; ?>
</template>

<script src="assets/js/blend-form.js"></script>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
