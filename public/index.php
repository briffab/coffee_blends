<?php

declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

header('Location: ' . (current_user_id() ? 'home.php' : 'login.php'));
exit;
