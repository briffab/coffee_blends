<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

csrf_verify();
$blendId = (int) ($_POST['id'] ?? 0);
delete_blend($pdo, $blendId, current_user_id());
header('Location: home.php?deleted=1');
exit;
