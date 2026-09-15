<?php

declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$user = $token !== '' ? find_user_by_reset_token($pdo, $token) : null;
$error = null;

if (!$user) {
    $pageTitle = 'Reset password';
    require __DIR__ . '/../src/views/header.php';
    ?>
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="alert alert-danger">This password reset link is invalid or has expired. <a href="forgot_password.php">Request a new one</a>.</div>
      </div>
    </div>
    <?php
    require __DIR__ . '/../src/views/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        complete_password_reset($pdo, (int) $user['id'], $password);
        header('Location: login.php?reset=1');
        exit;
    }
}

$pageTitle = 'Reset password';
require __DIR__ . '/../src/views/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <h1 class="h3 mb-4">Choose a new password</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="mb-3">
        <label class="form-label" for="password">New password</label>
        <input class="form-control" type="password" id="password" name="password" required minlength="8">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password_confirm">Confirm password</label>
        <input class="form-control" type="password" id="password_confirm" name="password_confirm" required minlength="8">
      </div>
      <button class="btn btn-primary w-100" type="submit">Reset password</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
