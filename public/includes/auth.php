<?php

declare(strict_types=1);

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function current_user(PDO $pdo): ?array
{
    static $cache = [];
    $id = current_user_id();
    if ($id === null) {
        return null;
    }
    if (!array_key_exists($id, $cache)) {
        $stmt = $pdo->prepare('SELECT id, name, email, is_admin FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $cache[$id] = $stmt->fetch() ?: null;
    }
    return $cache[$id];
}

/** Redirects to the login page unless a signed-in user (that still exists) is making the request. */
function require_login(): void
{
    global $pdo;

    if (current_user_id() === null) {
        header('Location: login.php');
        exit;
    }
    // An admin may have deleted this account since the session started.
    if ($pdo instanceof PDO && current_user($pdo) === null) {
        logout();
        header('Location: login.php');
        exit;
    }
}

/** Like require_login(), but only lets admins through. Returns the admin's user row. */
function require_admin(PDO $pdo): array
{
    require_login();
    $user = current_user($pdo);
    if ($user === null || (int) $user['is_admin'] !== 1) {
        http_response_code(403);
        exit('This page is for admins only.');
    }
    return $user;
}

function attempt_login(PDO $pdo, string $email, string $password): bool
{
    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function start_password_reset(PDO $pdo, string $email): ?string
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    $update = $pdo->prepare('UPDATE users SET reset_token_hash = ?, reset_token_expires = ? WHERE id = ?');
    $update->execute([$tokenHash, $expires, $user['id']]);

    return $token;
}

function find_user_by_reset_token(PDO $pdo, string $token): ?array
{
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare('SELECT id, reset_token_expires FROM users WHERE reset_token_hash = ?');
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();

    if (!$user || $user['reset_token_expires'] === null) {
        return null;
    }
    if (new DateTime($user['reset_token_expires']) < new DateTime()) {
        return null;
    }
    return $user;
}

function complete_password_reset(PDO $pdo, int $userId, string $newPassword): void
{
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_token_expires = NULL WHERE id = ?'
    );
    $stmt->execute([$hash, $userId]);
}
