<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
csrf_verify();

$currentUser = current_user($pdo);
$action = (string) ($_POST['action'] ?? '');
$scoreRaw = (string) ($_POST['score'] ?? '');
$espresso = (string) ($_POST['espresso_notes'] ?? '');
$milky = (string) ($_POST['milky_notes'] ?? '');

// Where to go afterwards: back to the blend the note belongs to.
$blendId = (int) ($_POST['blend_id'] ?? 0);
$note = null;
if ($action !== 'add') {
    $note = get_tasting_note($pdo, (int) ($_POST['note_id'] ?? 0));
    if ($note === null) {
        http_response_code(404);
        exit('Tasting note not found.');
    }
    $blendId = (int) $note['blend_id'];
}

$blend = get_blend($pdo, $blendId);
if ($blend === null) {
    http_response_code(404);
    exit('Blend not found.');
}
$back = 'blend_view.php?id=' . $blendId . '#notes';
$isAuthor = $note !== null && (int) $note['user_id'] === (int) $currentUser['id'];

if ($action === 'add') {
    [$scoreClean, $espressoClean, $milkyClean, $error] = clean_tasting_note_input($scoreRaw, $espresso, $milky);
    if ($error !== null) {
        flash('danger', $error);
    } else {
        add_tasting_note($pdo, $blendId, (int) $currentUser['id'], $scoreClean, $espressoClean, $milkyClean);
        flash('success', 'Thanks — your score and tasting notes were added.');
    }
} elseif ($action === 'update') {
    if (!$isAuthor) {
        http_response_code(403);
        exit('You can only edit your own tasting notes.');
    }
    [$scoreClean, $espressoClean, $milkyClean, $error] = clean_tasting_note_input($scoreRaw, $espresso, $milky);
    if ($error !== null) {
        flash('danger', $error);
    } else {
        update_tasting_note($pdo, (int) $note['id'], $scoreClean, $espressoClean, $milkyClean);
        flash('success', 'Your score and tasting notes were updated.');
    }
} elseif ($action === 'delete') {
    if (!$isAuthor && (int) $currentUser['is_admin'] !== 1) {
        http_response_code(403);
        exit('You can only delete your own tasting notes.');
    }
    delete_tasting_note($pdo, (int) $note['id']);
    flash('success', 'Tasting note deleted.');
} else {
    http_response_code(400);
    exit('Unknown action.');
}

header('Location: ' . $back);
exit;
