<?php

// Copy this file to config.php and fill in your real settings.
// config.php is gitignored so your secrets never get committed.

return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'coffee_blends',
        'user' => 'coffee_user',
        'pass' => 'changeme',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        // Leave blank to auto-detect from the request (scheme + host + path).
        // Set explicitly if auto-detection doesn't work behind your proxy/load balancer.
        'base_url' => '',
        'session_name' => 'coffee_blends_session',
    ],
    'mail' => [
        'from_email' => 'no-reply@example.com',
        'from_name' => 'Coffee Blends',
    ],
];
