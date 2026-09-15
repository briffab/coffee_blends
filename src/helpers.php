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
