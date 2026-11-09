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
  <div class="table-responsive">
    <table class="table table-hover align-middle bg-white">
      <thead>
        <tr>
          <th></th>
          <th>Name</th>
          <th>Roast date</th>
          <th>Added by</th>
          <th>Beans</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($blends as $blend): ?>
          <tr>
            <td class="thumb-cell">
              <?php if (!empty($blend['image_path'])): ?>
                <img src="<?= htmlspecialchars($blend['image_path']) ?>" alt="" class="blend-thumb rounded">
              <?php endif; ?>
            </td>
            <td><a href="blend_view.php?id=<?= (int) $blend['id'] ?>"><?= htmlspecialchars($blend['name']) ?></a></td>
            <td><?= htmlspecialchars(format_date_for_display($blend['roast_date'])) ?></td>
            <td><?= htmlspecialchars(display_name(['name' => $blend['owner_name'], 'email' => $blend['owner_email']])) ?></td>
            <td><?= (int) $blend['bean_count'] ?></td>
            <td class="text-end">
              <?php if (can_manage_blend($blend, $currentUser)): ?>
                <a class="btn btn-sm btn-outline-secondary" href="blend_edit.php?id=<?= (int) $blend['id'] ?>">Edit</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/views/footer.php'; ?>
