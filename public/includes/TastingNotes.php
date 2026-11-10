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

const MAX_SCORE = 5;

/**
 * Validates a posted score + notes. Returns [score|null, espresso|null,
 * milky|null, error|null]. The score is optional (a note without one is fine,
 * and so is a score without notes), but there must be at least one of the
 * three. A score of 0 is a real score; "no score" is null.
 */
function clean_tasting_note_input(string $scoreRaw, string $espresso, string $milky): array
{
    $scoreRaw = trim($scoreRaw);
    $espresso = trim($espresso);
    $milky = trim($milky);

    $score = null;
    if ($scoreRaw !== '') {
        if (preg_match('/^[0-5]$/', $scoreRaw) !== 1) {
            return [null, null, null, 'The score must be a whole number from 0 to ' . MAX_SCORE . '.'];
        }
        $score = (int) $scoreRaw;
    }

    if ($score === null && $espresso === '' && $milky === '') {
        return [null, null, null, 'Add a score or write something in at least one of the tasting note boxes.'];
    }
    if (mb_strlen($espresso) > TASTING_NOTE_MAX_LENGTH || mb_strlen($milky) > TASTING_NOTE_MAX_LENGTH) {
        return [null, null, null, 'Tasting notes can be up to ' . TASTING_NOTE_MAX_LENGTH . ' characters each.'];
    }

    return [$score, $espresso === '' ? null : $espresso, $milky === '' ? null : $milky, null];
}

function add_tasting_note(PDO $pdo, int $blendId, int $userId, ?int $score, ?string $espresso, ?string $milky): void
{
    $pdo->prepare('INSERT INTO tasting_notes (blend_id, user_id, score, espresso_notes, milky_notes) VALUES (?, ?, ?, ?, ?)')
        ->execute([$blendId, $userId, $score, $espresso, $milky]);
}

function update_tasting_note(PDO $pdo, int $noteId, ?int $score, ?string $espresso, ?string $milky): void
{
    $pdo->prepare('UPDATE tasting_notes SET score = ?, espresso_notes = ?, milky_notes = ? WHERE id = ?')
        ->execute([$score, $espresso, $milky, $noteId]);
}

/**
 * Average score per blend, as [blendId => ['average' => float, 'count' => int]].
 * Each person counts once per blend (the mean of their own scores), so someone
 * who tries a blend on several days doesn't outweigh everyone else. Blends
 * nobody has scored are absent from the result.
 */
function get_blend_ratings(PDO $pdo, ?int $blendId = null): array
{
    $stmt = $pdo->prepare(
        'SELECT blend_id, AVG(user_average) AS average, COUNT(*) AS people
         FROM (
             SELECT blend_id, user_id, AVG(score) AS user_average
             FROM tasting_notes
             WHERE score IS NOT NULL AND (? IS NULL OR blend_id = ?)
             GROUP BY blend_id, user_id
         ) per_person
         GROUP BY blend_id'
    );
    $stmt->execute([$blendId, $blendId]);

    $ratings = [];
    foreach ($stmt->fetchAll() as $row) {
        $ratings[(int) $row['blend_id']] = ['average' => (float) $row['average'], 'count' => (int) $row['people']];
    }
    return $ratings;
}

/** "4.3" — one decimal place, no trailing ".0" noise needed for whole numbers like 4. */
function format_score(float $score): string
{
    return rtrim(rtrim(number_format($score, 1, '.', ''), '0'), '.');
}

function delete_tasting_note(PDO $pdo, int $noteId): void
{
    $pdo->prepare('DELETE FROM tasting_notes WHERE id = ?')->execute([$noteId]);
}
