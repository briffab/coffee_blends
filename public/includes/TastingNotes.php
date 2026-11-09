<?php

declare(strict_types=1);

const TASTING_NOTE_MAX_LENGTH = 2000;

/** Everyone's added notes for a blend, oldest first. */
function get_tasting_notes(PDO $pdo, int $blendId): array
{
    $stmt = $pdo->prepare(
        'SELECT n.*, u.name AS author_name, u.email AS author_email
         FROM tasting_notes n
         JOIN users u ON u.id = n.user_id
         WHERE n.blend_id = ?
         ORDER BY n.created_at, n.id'
    );
    $stmt->execute([$blendId]);
    return $stmt->fetchAll();
}

function get_tasting_note(PDO $pdo, int $noteId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM tasting_notes WHERE id = ?');
    $stmt->execute([$noteId]);
    return $stmt->fetch() ?: null;
}

/**
 * Trims the posted notes. Returns [espresso|null, milky|null, error|null];
 * at least one of the two must have something in it.
 */
function clean_tasting_note_input(string $espresso, string $milky): array
{
    $espresso = trim($espresso);
    $milky = trim($milky);

    if ($espresso === '' && $milky === '') {
        return [null, null, 'Write something in at least one of the tasting note boxes.'];
    }
    if (mb_strlen($espresso) > TASTING_NOTE_MAX_LENGTH || mb_strlen($milky) > TASTING_NOTE_MAX_LENGTH) {
        return [null, null, 'Tasting notes can be up to ' . TASTING_NOTE_MAX_LENGTH . ' characters each.'];
    }

    return [$espresso === '' ? null : $espresso, $milky === '' ? null : $milky, null];
}

function add_tasting_note(PDO $pdo, int $blendId, int $userId, ?string $espresso, ?string $milky): void
{
    $pdo->prepare('INSERT INTO tasting_notes (blend_id, user_id, espresso_notes, milky_notes) VALUES (?, ?, ?, ?)')
        ->execute([$blendId, $userId, $espresso, $milky]);
}

function update_tasting_note(PDO $pdo, int $noteId, ?string $espresso, ?string $milky): void
{
    $pdo->prepare('UPDATE tasting_notes SET espresso_notes = ?, milky_notes = ? WHERE id = ?')
        ->execute([$espresso, $milky, $noteId]);
}

function delete_tasting_note(PDO $pdo, int $noteId): void
{
    $pdo->prepare('DELETE FROM tasting_notes WHERE id = ?')->execute([$noteId]);
}
