<?php

declare(strict_types=1);

const ROAST_LEVELS = ['light', 'medium', 'dark', 'very_dark', 'oily', 'burnt'];
const CLOUD_CONDITIONS = ['sunny', 'partial', 'cloudy'];

function get_blends_for_user(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT b.*, COUNT(bb.id) AS bean_count
         FROM blends b
         LEFT JOIN blend_beans bb ON bb.blend_id = b.id
         WHERE b.user_id = ?
         GROUP BY b.id
         ORDER BY b.roast_date DESC, b.id DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function get_blend(PDO $pdo, int $blendId, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM blends WHERE id = ? AND user_id = ?');
    $stmt->execute([$blendId, $userId]);
    $blend = $stmt->fetch();
    if (!$blend) {
        return null;
    }

    $beansStmt = $pdo->prepare('SELECT * FROM blend_beans WHERE blend_id = ? ORDER BY sort_order, id');
    $beansStmt->execute([$blendId]);
    $blend['beans'] = $beansStmt->fetchAll();

    return $blend;
}

/**
 * Creates a blend (when $blendId is null) or replaces an existing one's
 * data and bean rows (when $blendId is given), inside a transaction.
 */
function save_blend(PDO $pdo, int $userId, array $blendData, array $beans, ?int $blendId = null): int
{
    $pdo->beginTransaction();
    try {
        if ($blendId === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO blends (user_id, name, roast_date, espresso_notes, milky_notes) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $blendData['name'],
                $blendData['roast_date'],
                $blendData['espresso_notes'],
                $blendData['milky_notes'],
            ]);
            $blendId = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE blends SET name = ?, roast_date = ?, espresso_notes = ?, milky_notes = ? WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([
                $blendData['name'],
                $blendData['roast_date'],
                $blendData['espresso_notes'],
                $blendData['milky_notes'],
                $blendId,
                $userId,
            ]);

            $deleteBeans = $pdo->prepare('DELETE FROM blend_beans WHERE blend_id = ?');
            $deleteBeans->execute([$blendId]);
        }

        $beanStmt = $pdo->prepare(
            'INSERT INTO blend_beans
                (blend_id, sort_order, variety, weight_g, roast_temperature, roast_time_minutes,
                 roaster_start_temperature, ambient_temperature, cloud_conditions, roast_level)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach (array_values($beans) as $index => $bean) {
            $beanStmt->execute([
                $blendId,
                $index,
                $bean['variety'],
                $bean['weight_g'],
                $bean['roast_temperature'],
                $bean['roast_time_minutes'],
                $bean['roaster_start_temperature'],
                $bean['ambient_temperature'],
                $bean['cloud_conditions'],
                $bean['roast_level'],
            ]);
        }

        $pdo->commit();
        return $blendId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function delete_blend(PDO $pdo, int $blendId, int $userId): void
{
    $stmt = $pdo->prepare('DELETE FROM blends WHERE id = ? AND user_id = ?');
    $stmt->execute([$blendId, $userId]);
}

/**
 * Validates and normalizes a blend form submission ($_POST) into
 * a clean blend array and a list of bean rows, plus any validation errors.
 */
function parse_blend_submission(array $post): array
{
    $errors = [];

    $name = trim((string) ($post['name'] ?? ''));
    $roastDate = trim((string) ($post['roast_date'] ?? ''));
    $espressoNotes = trim((string) ($post['espresso_notes'] ?? ''));
    $milkyNotes = trim((string) ($post['milky_notes'] ?? ''));

    if ($name === '') {
        $errors[] = 'Blend name is required.';
    }

    $dateObj = DateTime::createFromFormat('Y-m-d', $roastDate);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $roastDate) {
        $errors[] = 'A valid roast date is required.';
    }

    $beansInput = is_array($post['beans'] ?? null) ? $post['beans'] : [];
    $beans = [];

    foreach ($beansInput as $row) {
        if (!is_array($row)) {
            continue;
        }

        $variety = trim((string) ($row['variety'] ?? ''));
        $weightRaw = trim((string) ($row['weight_g'] ?? ''));

        // Skip a completely empty row left over from a removed row in the browser.
        if ($variety === '' && $weightRaw === '') {
            continue;
        }

        $label = $variety !== '' ? $variety : 'a bean';
        $roastLevel = (string) ($row['roast_level'] ?? '');
        $cloud = (string) ($row['cloud_conditions'] ?? '');
        $cloud = $cloud === '' ? null : $cloud;

        if ($variety === '') {
            $errors[] = 'Each bean needs a variety/origin name.';
        }
        if ($weightRaw === '' || !is_numeric($weightRaw) || (float) $weightRaw <= 0) {
            $errors[] = "Enter a valid weight for $label.";
        }
        if (!in_array($roastLevel, ROAST_LEVELS, true)) {
            $errors[] = "Choose a valid roast level for $label.";
        }
        if ($cloud !== null && !in_array($cloud, CLOUD_CONDITIONS, true)) {
            $errors[] = "Choose a valid cloud condition for $label.";
        }

        $beans[] = [
            'variety' => $variety,
            'weight_g' => ($weightRaw !== '' && is_numeric($weightRaw)) ? (float) $weightRaw : null,
            'roast_temperature' => numeric_or_null($row['roast_temperature'] ?? ''),
            'roast_time_minutes' => numeric_or_null($row['roast_time_minutes'] ?? ''),
            'roaster_start_temperature' => numeric_or_null($row['roaster_start_temperature'] ?? ''),
            'ambient_temperature' => numeric_or_null($row['ambient_temperature'] ?? ''),
            'cloud_conditions' => $cloud,
            'roast_level' => in_array($roastLevel, ROAST_LEVELS, true) ? $roastLevel : null,
        ];
    }

    if (empty($beans)) {
        $errors[] = 'Add at least one bean variety.';
    }

    return [
        'errors' => $errors,
        'blend' => [
            'name' => $name,
            'roast_date' => $roastDate,
            'espresso_notes' => $espressoNotes === '' ? null : $espressoNotes,
            'milky_notes' => $milkyNotes === '' ? null : $milkyNotes,
        ],
        'beans' => $beans,
    ];
}
