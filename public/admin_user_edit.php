<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$currentUser = require_admin($pdo);

$userId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$target = get_user($pdo, $userId);
if ($target === null) {
    http_response_code(404);
    exit('User not found.');
}
$isSelf = (int) $target['id'] === (int) $currentUser['id'];
$back = 'admin_user_edit.php?id=' . $userId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'details') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = normalize_email((string) ($_POST['email'] ?? ''));
        $isAdmin = !empty($_POST['is_admin']);

        $errors = validate_user_details($pdo, $name, $email, $userId);
        if ((int) $target['is_admin'] === 1 && !$isAdmin && count_admins($pdo) <= 1) {
            $errors[] = 'There must always be at least one admin.';
        }
        if ($errors) {
            foreach ($errors as $error) {
                flash('danger', $error);
            }
        } else {
            update_user_details($pdo, $userId, $name, $email, $isAdmin);
            flash('success', 'Details saved.');
        }
        header('Location: ' . $back);
        exit;
    }

    if ($action === 'password') {
        $password = (string) ($_POST['new_password'] ?? '');
        $error = validate_new_password($password);
        if ($error !== null) {
            flash('danger', $error);
        } else {
            set_user_password($pdo, $userId, $password);
            flash('success', 'Password changed. Let ' . $target['name'] . ' know their new password.');
        }
        header('Location: ' . $back);
        exit;
    }

    if ($action === 'delete') {
        if ($isSelf) {
            flash('danger', "You can't delete your own account.");
            header('Location: ' . $back);
            exit;
        }
        delete_user($pdo, $userId);
        flash('success', $target['name'] . "'s account and blends were deleted.");
        header('Location: admin_users.php');
        exit;
    }

    http_response_code(400);
    exit('Unknown action.');
}

$pageTitle = 'Manage ' . $target['name'];
require __DIR__ . '/includes/views/header.php';
?>
<p><a href="admin_users.php">&larr; All users</a></p>
<h1 class="h3 mb-4"><?= htmlspecialchars($target['name']) ?><?= $isSelf ? ' <span class="badge bg-light text-dark border">You</span>' : '' ?></h1>

<div class="row g-4" style="max-width: 900px;">
  <div class="col-md-6">
    <h2 class="h5">Details</h2>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $userId ?>">
      <input type="hidden" name="action" value="details">
      <div class="mb-3">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" id="name" name="name" value="<?= htmlspecialchars($target['name']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($target['email']) ?>" required>
      </div>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_admin" value="1" id="is_admin" <?= (int) $target['is_admin'] === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_admin">Admin (can add, edit and remove users)</label>
      </div>
      <button type="submit" class="btn btn-primary">Save details</button>
    </form>
  </div>

  <div class="col-md-6">
    <h2 class="h5">Set a new password</h2>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $userId ?>">
      <input type="hidden" name="action" value="password">
      <div class="mb-3">
        <label class="form-label" for="new_password">New password</label>
        <div class="input-group">
          <input class="form-control" type="text" id="new_password" name="new_password" autocomplete="off" required minlength="<?= MIN_PASSWORD_LENGTH ?>">
          <button type="button" class="btn btn-outline-secondary" id="gen-password">Generate</button>
        </div>
        <div class="form-text">Replaces their current password straight away.</div>
      </div>
      <button type="submit" class="btn btn-outline-primary">Change password</button>
    </form>
  </div>
</div>

<?php if (!$isSelf): ?>
  <div class="mt-5 pt-4 border-top" style="max-width: 900px;">
    <h2 class="h5 text-danger">Delete this account</h2>
    <p class="text-muted">Permanently deletes <?= htmlspecialchars($target['name']) ?> and every blend (and image) they've recorded.</p>
    <form method="post" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($target['name']), ENT_QUOTES) ?> and all their blends? This cannot be undone.');">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $userId ?>">
      <input type="hidden" name="action" value="delete">
      <button type="submit" class="btn btn-outline-danger btn-sm">Delete account</button>
    </form>
  </div>
<?php endif; ?>

<script>
document.getElementById('gen-password').addEventListener('click', function () {
  var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  var bytes = new Uint32Array(12);
  crypto.getRandomValues(bytes);
  var out = '';
  bytes.forEach(function (b) { out += chars[b % chars.length]; });
  document.getElementById('new_password').value = out;
});
</script>
<?php require __DIR__ . '/includes/views/footer.php'; ?>
