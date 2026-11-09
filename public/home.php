<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$currentUser = current_user($pdo);
$onlyMine = !empty($_GET['mine']);
$blends = get_blends($pdo, $onlyMine ? (int) $currentUser['id'] : null);

$pageTitle = $onlyMine ? 'My blends' : 'Family blends';
require __DIR__ . '/includes/views/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?= $onlyMine ? 'My blends' : 'Family blends' ?></h1>
  <a href="blend_add.php" class="btn btn-primary">+ Add blend</a>
</div>

<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link <?= $onlyMine ? '' : 'active' ?>" href="home.php">Everyone's</a></li>
  <li class="nav-item"><a class="nav-link <?= $onlyMine ? 'active' : '' ?>" href="home.php?mine=1">Mine</a></li>
</ul>

<?php if (!empty($_GET['deleted'])): ?>
  <div class="alert alert-success">Blend deleted.</div>
<?php endif; ?>

<?php if (empty($blends)): ?>
  <p class="text-muted"><?= $onlyMine ? "You haven't added any blends yet." : 'No blends recorded yet.' ?> Roast something and add the first blend!</p>
<?php else: ?>
  <div class="blend-list">
    <?php foreach ($blends as $blend): ?>
      <div class="blend-item">
        <a class="blend-item-main" href="blend_view.php?id=<?= (int) $blend['id'] ?>">
          <?php if (!empty($blend['image_path'])): ?>
            <img src="<?= htmlspecialchars(blend_thumb_url($blend['image_path'])) ?>" alt="" width="64" height="64" loading="lazy" decoding="async" class="blend-thumb rounded">
          <?php else: ?>
            <span class="blend-thumb blend-thumb-empty rounded" aria-hidden="true">&#9749;</span>
          <?php endif; ?>
          <span class="blend-item-text">
            <span class="blend-name"><?= htmlspecialchars($blend['name']) ?></span>
            <span class="blend-meta">
              <?= htmlspecialchars(format_date_for_display($blend['roast_date'])) ?>
              &middot; <?= htmlspecialchars(display_name(['name' => $blend['owner_name'], 'email' => $blend['owner_email']])) ?>
              &middot; <?= (int) $blend['bean_count'] ?> <?= (int) $blend['bean_count'] === 1 ? 'bean' : 'beans' ?>
            </span>
          </span>
        </a>
        <?php if (can_manage_blend($blend, $currentUser)): ?>
          <a class="btn btn-sm btn-outline-secondary" href="blend_edit.php?id=<?= (int) $blend['id'] ?>">Edit</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/views/footer.php'; ?>
