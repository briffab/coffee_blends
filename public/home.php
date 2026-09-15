<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$currentUser = current_user($pdo);
$blends = get_blends_for_user($pdo, current_user_id());

$pageTitle = 'Your blends';
require __DIR__ . '/includes/views/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">Your blends</h1>
  <a href="blend_add.php" class="btn btn-primary">+ Add blend</a>
</div>

<?php if (!empty($_GET['deleted'])): ?>
  <div class="alert alert-success">Blend deleted.</div>
<?php endif; ?>

<?php if (empty($blends)): ?>
  <p class="text-muted">No blends recorded yet. Roast something and add your first blend!</p>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle bg-white">
      <thead>
        <tr>
          <th>Name</th>
          <th>Roast date</th>
          <th>Beans</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($blends as $blend): ?>
          <tr>
            <td><a href="blend_view.php?id=<?= (int) $blend['id'] ?>"><?= htmlspecialchars($blend['name']) ?></a></td>
            <td><?= htmlspecialchars(format_date_for_display($blend['roast_date'])) ?></td>
            <td><?= (int) $blend['bean_count'] ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-secondary" href="blend_edit.php?id=<?= (int) $blend['id'] ?>">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/views/footer.php'; ?>
