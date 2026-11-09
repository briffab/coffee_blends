<?php
/** @var array|null $currentUser */
$currentUser = $currentUser ?? (isset($pdo) && current_user_id() ? current_user($pdo) : null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>Coffee Blends</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="<?= $currentUser ? 'home.php' : 'login.php' ?>">☕ Coffee Blends</a>
    <?php if ($currentUser): ?>
      <div class="d-flex align-items-center gap-3">
        <?php if ((int) $currentUser['is_admin'] === 1): ?>
          <a class="text-light text-decoration-none" href="admin_users.php">Users</a>
        <?php endif; ?>
        <a class="text-light text-decoration-none" href="account.php">Hi, <?= htmlspecialchars(display_name($currentUser)) ?></a>
        <a class="btn btn-outline-light btn-sm" href="logout.php">Log out</a>
      </div>
    <?php endif; ?>
  </div>
</nav>
<main class="container pb-5">
<?php foreach (take_flashes() as $flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
<?php endforeach; ?>
