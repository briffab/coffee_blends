<?php

declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim((string) ($_POST['email'] ?? ''));
    if ($email !== '') {
        $token = start_password_reset($pdo, $email);
        if ($token !== null) {
            $resetUrl = site_base_url($config) . '/reset_password.php?token=' . urlencode($token);
            send_password_reset_email($config['mail'], $email, $resetUrl);
        }
    }
    // Always show the same message whether or not the email is registered,
    // so this form can't be used to discover which emails have accounts.
    $sent = true;
}

$pageTitle = 'Forgot password';
require __DIR__ . '/../src/views/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <h1 class="h3 mb-4">Forgot password</h1>
    <?php if ($sent): ?>
      <div class="alert alert-success">If that email is registered, a reset link has been sent.</div>
    <?php else: ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" required autofocus>
        </div>
        <button class="btn btn-primary w-100" type="submit">Send reset link</button>
      </form>
    <?php endif; ?>
    <p class="mt-3"><a href="login.php">Back to log in</a></p>
  </div>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
