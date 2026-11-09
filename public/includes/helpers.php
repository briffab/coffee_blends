<?php

declare(strict_types=1);

function site_base_url(array $config): string
{
    $configured = trim($config['app']['base_url'] ?? '');
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    return $scheme . '://' . $host . $dir;
}

function numeric_or_null($value): ?float
{
    $value = trim((string) $value);
    return $value === '' ? null : (is_numeric($value) ? (float) $value : null);
}

/**
 * Formats a Y-m-d date (as stored in the database and used by the
 * HTML date input) as dd-mm-yyyy for display.
 */
function format_date_for_display(?string $isoDate): string
{
    if ($isoDate === null || $isoDate === '') {
        return '';
    }
    $date = DateTime::createFromFormat('Y-m-d', $isoDate);
    return $date ? $date->format('d-m-Y') : $isoDate;
}

/** Queues a one-off message (type: success|danger|info) shown on the next page. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** @return array<int, array{type:string,message:string}> */
function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/** A user's name for greetings; falls back to the start of their email if the name is blank. */
function display_name(array $user): string
{
    $name = trim((string) ($user['name'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    $email = (string) ($user['email'] ?? '');
    $local = strstr($email, '@', true);

    return $local !== false && $local !== '' ? $local : 'there';
}
