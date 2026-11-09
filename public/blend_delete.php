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
$blend = get_blend($pdo, $blendId);
if (!$blend || !can_manage_blend($blend, current_user($pdo))) {
    http_response_code(403);
    exit('Only the person who added this blend, or an admin, can delete it.');
}
delete_blend($pdo, $blendId, (int) $blend['user_id']);
header('Location: home.php?deleted=1');
exit;
