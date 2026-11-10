<?php

declare(strict_types=1);

const ROAST_LEVELS = ['light', 'medium', 'dark', 'very_dark', 'oily', 'burnt'];
const BLEND_DESCRIPTION_MAX_LENGTH = 2000;
const CLOUD_CONDITIONS = ['sunny', 'partial', 'cloudy'];

/** Every blend in the family (newest first), or only one person's when $onlyUserId is given. */
function get_blends(PDO $pdo, ?int $onlyUserId = null): array
{
    $stmt = $pdo->prepare(
        'SELECT b.*, u.name AS owner_name, u.email AS owner_email, COUNT(bb.id) AS bean_count
         FROM blends b
         JOIN users u ON u.id = b.user_id
         LEFT JOIN blend_beans bb ON bb.blend_id = b.id
         WHERE (? IS NULL OR b.user_id = ?)
         GROUP BY b.id, u.id
         ORDER BY b.roast_date DESC, b.id DESC'
    );
    $stmt->execute([$onlyUserId, $onlyUserId]);
    return $stmt->fetchAll();
}

/** Any blend by id — all blends are visible to every signed-in family member. */
function get_blend(PDO $pdo, int $blendId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT b.*, u.name AS owner_name, u.email AS owner_email
         FROM blends b
         JOIN users u ON u.id = b.user_id
         WHERE b.id = ?'
    );
    $stmt->execute([$blendId]);
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
                'INSERT INTO blends (user_id, name, roast_date, description, espresso_notes, milky_notes) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $blendData['name'],
                $blendData['roast_date'],
                $blendData['description'],
                $blendData['espresso_notes'],
                $blendData['milky_notes'],
            ]);
            $blendId = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE blends SET name = ?, roast_date = ?, description = ?, espresso_notes = ?, milky_notes = ? WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([
                $blendData['name'],
                $blendData['roast_date'],
                $blendData['description'],
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

/** Only the person who added a blend, or an admin, may change or delete it. */
function can_manage_blend(array $blend, ?array $user): bool
{
    return $user !== null
        && ((int) $blend['user_id'] === (int) $user['id'] || (int) $user['is_admin'] === 1);
}

function delete_blend(PDO $pdo, int $blendId, int $userId): void
{
    $stmt = $pdo->prepare('DELETE FROM blends WHERE id = ? AND user_id = ?');
    $stmt->execute([$blendId, $userId]);

    if ($stmt->rowCount() > 0) {
        delete_blend_images($blendId);
    }
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
    $description = trim((string) ($post['description'] ?? ''));
    $espressoNotes = trim((string) ($post['espresso_notes'] ?? ''));
    $milkyNotes = trim((string) ($post['milky_notes'] ?? ''));

    if ($name === '') {
        $errors[] = 'Blend name is required.';
    }
    if (mb_strlen($description) > BLEND_DESCRIPTION_MAX_LENGTH) {
        $errors[] = 'The description can be up to ' . BLEND_DESCRIPTION_MAX_LENGTH . ' characters.';
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
            'description' => $description === '' ? null : $description,
            'espresso_notes' => $espressoNotes === '' ? null : $espressoNotes,
            'milky_notes' => $milkyNotes === '' ? null : $milkyNotes,
        ],
        'beans' => $beans,
    ];
}

/** Ways the blends list can be ordered: key => label shown in the Sort by menu. */
const BLEND_SORTS = [
    'date_desc' => 'Roast date (newest first)',
    'date_asc' => 'Roast date (oldest first)',
    'rating_desc' => 'Highest rated',
    'rating_asc' => 'Lowest rated',
    'name_asc' => 'Name (A to Z)',
    'name_desc' => 'Name (Z to A)',
    'added_desc' => 'Recently added',
];

const DEFAULT_BLEND_SORT = 'date_desc';

function normalize_blend_sort(?string $sort): string
{
    return $sort !== null && isset(BLEND_SORTS[$sort]) ? $sort : DEFAULT_BLEND_SORT;
}

/**
 * Orders a blends list. Blends nobody has scored always sink to the bottom of
 * the two rating sorts; ties fall back to newest roast date, then newest id,
 * so the order is stable.
 *
 * @param array<int, array{average: float, count: int}> $ratings from get_blend_ratings()
 */
function sort_blends(array $blends, array $ratings, string $sort): array
{
    $byNewest = static fn (array $a, array $b): int
        => [$b['roast_date'], (int) $b['id']] <=> [$a['roast_date'], (int) $a['id']];

    usort($blends, static function (array $a, array $b) use ($ratings, $sort, $byNewest): int {
        $primary = 0;
        switch ($sort) {
            case 'date_desc':
                return $byNewest($a, $b);
            case 'date_asc':
                $primary = strcmp($a['roast_date'], $b['roast_date']);
                break;
            case 'name_asc':
            case 'name_desc':
                $primary = strcmp(mb_strtolower($a['name']), mb_strtolower($b['name']));
                if ($sort === 'name_desc') {
                    $primary = -$primary;
                }
                break;
            case 'added_desc':
                $primary = strcmp((string) $b['created_at'], (string) $a['created_at']);
                break;
            case 'rating_desc':
            case 'rating_asc':
                $ra = $ratings[(int) $a['id']] ?? null;
                $rb = $ratings[(int) $b['id']] ?? null;
                if ($ra === null || $rb === null) {
                    $primary = $ra === null ? ($rb === null ? 0 : 1) : -1;
                } else {
                    $primary = $ra['average'] <=> $rb['average'];
                    if ($sort === 'rating_desc') {
                        $primary = -$primary;
                    }
                    if ($primary === 0) {
                        $primary = $rb['count'] <=> $ra['count'];
                    }
                }
                break;
        }

        return $primary !== 0 ? $primary : $byNewest($a, $b);
    });

    return $blends;
}
