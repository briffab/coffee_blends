<?php

declare(strict_types=1);

/**
 * Locate config.php. Two supported layouts, tried in this order:
 *  - two-tier (preferred): config/config.php sits one level above the
 *    public/ webroot (public/includes/ -> public/ -> repo root) — keeps it
 *    entirely outside anything the webserver serves, for hosts that let you
 *    point a (sub)domain's document root at an arbitrary path.
 *  - flat: config.php sits directly in the webroot, alongside index.php —
 *    for hosts whose subdomain tool only accepts a single folder inside
 *    public_html, so public/'s contents ARE the document root and there's
 *    nowhere "outside" to put it. public/.htaccess denies direct requests
 *    for it either way.
 */
$configPath = null;
foreach ([__DIR__ . '/../../config/config.php', __DIR__ . '/../config.php'] as $candidate) {
    if (is_file($candidate)) {
        $configPath = $candidate;
        break;
    }
}
if ($configPath === null) {
    http_response_code(500);
    exit(
        'Missing config.php. Copy config/config.example.php to config/config.php '
        . '(local, two-tier layout) or to public/config.php (flat single-folder host) '
        . 'and fill in your settings.'
    );
}
$config = require $configPath;

session_name($config['app']['session_name'] ?? 'coffee_blends_session');
session_start();

require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/mailer.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/Blend.php';

$pdo = db_connect($config['db']);
