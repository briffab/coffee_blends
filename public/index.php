<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

header('Location: ' . (current_user_id() ? 'home.php' : 'login.php'));
exit;
