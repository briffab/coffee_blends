<?php

declare(strict_types=1);

$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    exit('Missing config/config.php. Copy config/config.example.php to config/config.php and fill in your settings.');
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
