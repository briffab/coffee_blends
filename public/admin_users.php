<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$currentUser = require_admin($pdo);

$errors = [];
$form = ['name' => '', 'email' => '', 'is_admin' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $form = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => normalize_email((string) ($_POST['email'] ?? '')),
        'is_admin' => !empty($_POST['is_admin']),
    ];
    $password = (string) ($_POST['password'] ?? '');
    $sendEmail = !empty($_POST['send_email']);

    $errors = validate_user_details($pdo, $form['name'], $form['email']);
    $passwordError = validate_new_password($password);
    if ($passwordError !== null) {
        $errors[] = $passwordError;
    }

    if (!$errors) {
        create_user($pdo, $form['name'], $form['email'], $password, $form['is_admin']);
        if (!$sendEmail) {
            flash('success', 'Account created for ' . $form['name'] . '. Give them their email address and the password you set — they can change it from the account link at the top right.');
        } elseif (send_welcome_email($config, ['name' => $form['name'], 'email' => $form['email']], $password, $currentUser)) {
            flash('success', 'Account created for ' . $form['name'] . ' and their login details were emailed to ' . $form['email'] . '. (If it does not turn up, check their spam folder.)');
        } else {
            flash('warning', 'Account created for ' . $form['name'] . ', but the email could not be sent. Please give them their email address and the password you set yourself.');
        }
        header('Location: admin_users.php');
        exit;
    }
}

$users = list_users($pdo);

$pageTitle = 'Users';
require __DIR__ . '/includes/views/header.php';
?>
<h1 class="h3 mb-4">Users</h1>

<div class="table-responsive mb-5">
  <table class="table align-middle bg-white">
    <thead>
      <tr><th>Name</th><th>Email</th><th>Role</th><th>Blends</th><th>Added</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td>
            <?= htmlspecialchars(display_name($user)) ?>
            <?php if ((int) $user['id'] === (int) $currentUser['id']): ?><span class="badge bg-light text-dark border ms-1">You</span><?php endif; ?>
          </td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= (int) $user['is_admin'] === 1 ? '<span class="badge bg-dark">Admin</span>' : 'Member' ?></td>
          <td><?= (int) $user['blend_count'] ?></td>
          <td><?= htmlspecialchars(format_date_for_display(substr((string) $user['created_at'], 0, 10))) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="admin_user_edit.php?id=<?= (int) $user['id'] ?>">Manage</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<h2 class="h5">Add a family member</h2>
<p class="text-muted">They sign in with their email and the password you set here. They can see everyone's blends, but only edit their own.</p>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" novalidate class="row g-3" style="max-width: 720px;">
  <?= csrf_field() ?>
  <div class="col-md-6">
    <label class="form-label" for="name">Name</label>
    <input class="form-control" id="name" name="name" value="<?= htmlspecialchars($form['name']) ?>" required>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="email">Email</label>
    <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($form['email']) ?>" required>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="password">Starting password</label>
    <div class="input-group">
      <input class="form-control" type="text" id="password" name="password" autocomplete="off" required minlength="<?= MIN_PASSWORD_LENGTH ?>">
      <button type="button" class="btn btn-outline-secondary" id="gen-password">Generate</button>
    </div>
    <div class="form-text">At least <?= MIN_PASSWORD_LENGTH ?> characters.</div>
  </div>
  <div class="col-md-6 d-flex align-items-center">
    <div class="form-check mt-3">
      <input class="form-check-input" type="checkbox" name="is_admin" value="1" id="is_admin" <?= $form['is_admin'] ? 'checked' : '' ?>>
      <label class="form-check-label" for="is_admin">Make them an admin</label>
    </div>
  </div>
  <div class="col-12">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="send_email" value="1" id="send_email" checked>
      <label class="form-check-label" for="send_email">
        Email them their login details
        <span class="text-muted">(their email address, this password in plain text, and links to the app and The Briffas)</span>
      </label>
    </div>
  </div>
  <div class="col-12">
    <button type="submit" class="btn btn-primary">Create account</button>
  </div>
</form>

<script>
document.getElementById('gen-password').addEventListener('click', function () {
  var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  var bytes = new Uint32Array(12);
  crypto.getRandomValues(bytes);
  var out = '';
  bytes.forEach(function (b) { out += chars[b % chars.length]; });
  document.getElementById('password').value = out;
});
</script>
<?php require __DIR__ . '/includes/views/footer.php'; ?>
