<?php

declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

if (current_user_id()) {
    header('Location: home.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (attempt_login($pdo, $email, $password)) {
        header('Location: home.php');
        exit;
    }
    $error = 'Incorrect email or password.';
}

$pageTitle = 'Log in';
require __DIR__ . '/../src/views/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <h1 class="h3 mb-4">Log in</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if (!empty($_GET['reset'])): ?><div class="alert alert-success">Your password has been reset. You can log in now.</div><?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>
      <button class="btn btn-primary w-100" type="submit">Log in</button>
    </form>
    <p class="mt-3"><a href="forgot_password.php">Forgot your password?</a></p>
  </div>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
