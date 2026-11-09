<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$currentUser = current_user($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'name') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            flash('danger', 'Enter a name (up to 100 characters).');
        } else {
            update_user_name($pdo, (int) $currentUser['id'], $name);
            flash('success', 'Name updated.');
        }
    } elseif ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (!user_password_matches($pdo, (int) $currentUser['id'], $current)) {
            flash('danger', 'Your current password is not correct.');
        } elseif (($error = validate_new_password($new)) !== null) {
            flash('danger', $error);
        } elseif ($new !== $confirm) {
            flash('danger', 'The new passwords do not match.');
        } else {
            set_user_password($pdo, (int) $currentUser['id'], $new);
            flash('success', 'Password changed.');
        }
    } else {
        http_response_code(400);
        exit('Unknown action.');
    }

    header('Location: account.php');
    exit;
}

$pageTitle = 'Your account';
require __DIR__ . '/includes/views/header.php';
?>
<h1 class="h3 mb-4">Your account</h1>

<div class="row g-4" style="max-width: 900px;">
  <div class="col-md-6">
    <h2 class="h5">Details</h2>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="name">
      <div class="mb-3">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" id="name" name="name" value="<?= htmlspecialchars($currentUser['name']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" value="<?= htmlspecialchars($currentUser['email']) ?>" disabled>
        <div class="form-text">This is what you sign in with. To change it, ask an admin.</div>
      </div>
      <button type="submit" class="btn btn-primary">Save name</button>
    </form>
  </div>

  <div class="col-md-6">
    <h2 class="h5">Change password</h2>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="password">
      <div class="mb-3">
        <label class="form-label" for="current_password">Current password</label>
        <input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
      </div>
      <div class="mb-3">
        <label class="form-label" for="new_password">New password</label>
        <input class="form-control" type="password" id="new_password" name="new_password" autocomplete="new-password" required minlength="<?= MIN_PASSWORD_LENGTH ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="confirm_password">Confirm new password</label>
        <input class="form-control" type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required minlength="<?= MIN_PASSWORD_LENGTH ?>">
      </div>
      <button type="submit" class="btn btn-outline-primary">Change password</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/views/footer.php'; ?>
