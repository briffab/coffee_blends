<?php

declare(strict_types=1);

const MIN_PASSWORD_LENGTH = 8;

function list_users(PDO $pdo): array
{
    return $pdo->query(
        'SELECT u.id, u.name, u.email, u.is_admin, u.created_at, COUNT(b.id) AS blend_count
         FROM users u
         LEFT JOIN blends b ON b.user_id = u.id
         GROUP BY u.id
         ORDER BY u.created_at, u.id'
    )->fetchAll();
}

function get_user(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, name, email, is_admin, created_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function count_admins(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 1')->fetchColumn();
}

function normalize_email(string $email): string
{
    return mb_strtolower(trim($email));
}

function email_taken(PDO $pdo, string $email, ?int $exceptUserId = null): bool
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
    $stmt->execute([$email, $exceptUserId ?? 0]);
    return $stmt->fetch() !== false;
}

/** @return string[] validation errors for a name/email pair */
function validate_user_details(PDO $pdo, string $name, string $email, ?int $exceptUserId = null): array
{
    $errors = [];
    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Enter a name (up to 100 characters).';
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    } elseif (email_taken($pdo, $email, $exceptUserId)) {
        $errors[] = 'Someone already has an account with that email address.';
    }
    return $errors;
}

function validate_new_password(string $password): ?string
{
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        return 'Passwords must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
    }
    return null;
}

function create_user(PDO $pdo, string $name, string $email, string $password, bool $isAdmin): int
{
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $isAdmin ? 1 : 0]);
    return (int) $pdo->lastInsertId();
}

function update_user_details(PDO $pdo, int $id, string $name, string $email, bool $isAdmin): void
{
    $pdo->prepare('UPDATE users SET name = ?, email = ?, is_admin = ? WHERE id = ?')
        ->execute([$name, $email, $isAdmin ? 1 : 0, $id]);
}

function update_user_name(PDO $pdo, int $id, string $name): void
{
    $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $id]);
}

/** Sets a new password and invalidates any outstanding reset link. */
function set_user_password(PDO $pdo, int $id, string $password): void
{
    $pdo->prepare('UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_token_expires = NULL WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
}

function user_password_matches(PDO $pdo, int $id, string $password): bool
{
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $hash = $stmt->fetchColumn();
    return is_string($hash) && password_verify($password, $hash);
}

/** Deletes a user, all their blends (database rows cascade) and their image files. */
function delete_user(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('SELECT id FROM blends WHERE user_id = ?');
    $stmt->execute([$id]);
    $blendIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);

    foreach ($blendIds as $blendId) {
        delete_blend_images((int) $blendId);
    }
}

function random_password(int $length = 12): string
{
    $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $out;
}
