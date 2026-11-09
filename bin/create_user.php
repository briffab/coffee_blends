<?php

declare(strict_types=1);

// CLI-only: create a login for yourself (or another roaster) since the
// app has no public self-registration page.
// Usage: php bin/create_user.php "Full Name" "email@example.com" "password"

require __DIR__ . '/../public/includes/db.php';

$config = require __DIR__ . '/../config/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

if ($argc !== 4) {
    fwrite(STDERR, "Usage: php bin/create_user.php \"Full Name\" \"email@example.com\" \"password\"\n");
    exit(1);
}

[, $name, $email, $password] = $argv;

if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

$pdo = db_connect($config['db']);

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    fwrite(STDERR, "A user with that email already exists.\n");
    exit(1);
}

// The very first account becomes the admin; later ones are members (an admin
// can promote them from the Users page).
$isAdmin = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0 ? 1 : 0;

$hash = password_hash($password, PASSWORD_DEFAULT);
$insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, ?)');
$insert->execute([$name, $email, $hash, $isAdmin]);

echo "User created with id " . $pdo->lastInsertId() . ($isAdmin ? " (admin)" : "") . "\n";
