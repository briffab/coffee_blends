<?php

declare(strict_types=1);

const BLEND_IMAGE_CANDIDATES = 5;

function image_generation_enabled(array $config): bool
{
    return trim((string) ($config['images']['api_key'] ?? '')) !== '';
}

function blend_uploads_root(): string
{
    return dirname(__DIR__) . '/uploads/blends';
}

function blend_image_dir(int $blendId): string
{
    return blend_uploads_root() . '/' . $blendId;
}

/** Relative-to-webroot URL paths of the current candidate images, in order. */
function list_image_candidates(int $blendId): array
{
    $files = glob(blend_image_dir($blendId) . '/cand_*.*') ?: [];
    sort($files, SORT_NATURAL);
    return array_map(fn (string $f): string => 'uploads/blends/' . $blendId . '/' . basename($f), $files);
}

function clear_image_candidates(int $blendId): void
{
    foreach (glob(blend_image_dir($blendId) . '/cand_*.*') ?: [] as $file) {
        @unlink($file);
    }
}

function build_image_prompt(string $blendName): string
{
    $name = trim(preg_replace('/[^\p{L}\p{N} \'&,.\-]/u', ' ', $blendName) ?? '');
    $name = mb_substr($name, 0, 100);

    return 'A warm, beautiful, appetising illustration for a small-batch home-roasted coffee blend called "'
        . $name . '". Coffee beans, roasting or a fresh cup, rich browns and creamy tones, '
        . 'square composition, no text, no words, no letters, no logos.';
}

/** Cleans a user-edited prompt; falls back to the default one when empty. */
function normalize_image_prompt(string $prompt, string $blendName): string
{
    $clean = trim(preg_replace('/[^\P{C}\n]/u', ' ', $prompt) ?? '');
    $clean = mb_substr($clean, 0, 1000);

    return $clean === '' ? build_image_prompt($blendName) : $clean;
}

/** The prompt last used to generate images for this blend, if any. */
function saved_image_prompt(int $blendId): ?string
{
    $file = blend_image_dir($blendId) . '/prompt.txt';
    $text = is_file($file) ? file_get_contents($file) : false;

    return is_string($text) && trim($text) !== '' ? $text : null;
}

/** Saves raw image bytes next to $destBase; returns the file extension used. */
function store_image_bytes(string $bytes, string $destBase): string
{
    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $img = @imagecreatefromstring($bytes);
        if ($img !== false) {
            $max = 800;
            if (imagesx($img) > $max) {
                $scaled = imagescale($img, $max);
                if ($scaled !== false) {
                    $img = $scaled;
                }
            }
            if (imagejpeg($img, $destBase . '.jpg', 85)) {
                return 'jpg';
            }
        }
    }
    if (file_put_contents($destBase . '.png', $bytes) === false) {
        throw new RuntimeException('Could not write image file (is public/uploads writable?).');
    }
    return 'png';
}

function http_get_bytes(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_FOLLOWLOCATION => true]);
    $body = curl_exec($ch);
    curl_close($ch);
    if (!is_string($body) || $body === '') {
        throw new RuntimeException('Could not download a generated image.');
    }
    return $body;
}

/** @return array{0:int,1:?string,2:string} HTTP status, response body (null if unreachable), curl error */
function request_image_generation(array $cfg, string $model, string $prompt): array
{
    $ch = curl_init($cfg['endpoint'] ?? 'https://api.openai.com/v1/images/generations');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 220,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $cfg['api_key'],
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $model,
            'prompt' => $prompt,
            'n' => BLEND_IMAGE_CANDIDATES,
            'size' => $cfg['size'] ?? '1024x1024',
            'quality' => $cfg['quality'] ?? 'low',
        ]),
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [$status, is_string($body) ? $body : null, $curlError];
}

/**
 * Asks the image API for BLEND_IMAGE_CANDIDATES images from $prompt (default:
 * built from the blend name) and saves them as cand_1..N, replacing any
 * existing candidates. The prompt is remembered for the next attempt.
 *
 * @return string[] URL paths of the saved candidates
 */
function generate_image_candidates(array $config, int $blendId, string $blendName, ?string $prompt = null): array
{
    if (!image_generation_enabled($config)) {
        throw new RuntimeException('Image generation is not configured.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('The PHP curl extension is required for image generation.');
    }

    $cfg = $config['images'];
    $prompt = normalize_image_prompt($prompt ?? '', $blendName);
    @set_time_limit(240);

    // `model` may be one ID or a list tried in order: image model names get
    // retired/renamed, so fall through to the next only when the API says that
    // model doesn't exist (or this key can't use it).
    $models = (array) ($cfg['model'] ?? ['gpt-image-2.5-flare', 'gpt-image-2']);
    $data = null;
    $lastError = 'No image model configured.';
    foreach ($models as $model) {
        [$status, $body, $curlError] = request_image_generation($cfg, (string) $model, $prompt);
        if ($body === null) {
            throw new RuntimeException('Could not reach the image service: ' . $curlError);
        }
        $decoded = json_decode($body, true);
        if ($status === 200 && isset($decoded['data']) && is_array($decoded['data'])) {
            $data = $decoded;
            break;
        }
        $detail = is_array($decoded) ? (string) ($decoded['error']['message'] ?? '') : '';
        $lastError = 'The image service returned an error (HTTP ' . $status . ')' . ($detail !== '' ? ': ' . $detail : '.');
        $modelUnavailable = in_array($status, [400, 403, 404], true)
            && preg_match('/model.*(does not exist|not found|not available|access|deprecated|retired)|(does not exist|not found|no access).*model/i', $detail) === 1;
        if (!$modelUnavailable) {
            break;
        }
    }
    if ($data === null) {
        throw new RuntimeException($lastError);
    }

    $dir = blend_image_dir($blendId);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create the images folder (is public/uploads writable?).');
    }
    clear_image_candidates($blendId);
    file_put_contents($dir . '/prompt.txt', $prompt);

    $saved = 0;
    foreach (array_values($data['data']) as $i => $item) {
        if (isset($item['b64_json'])) {
            $bytes = base64_decode((string) $item['b64_json'], true);
        } elseif (isset($item['url'])) {
            $bytes = http_get_bytes((string) $item['url']);
        } else {
            continue;
        }
        if ($bytes === false || $bytes === '') {
            continue;
        }
        store_image_bytes($bytes, $dir . '/cand_' . ($i + 1));
        $saved++;
    }
    if ($saved === 0) {
        throw new RuntimeException('The image service did not return any images.');
    }

    return list_image_candidates($blendId);
}

/** Makes candidate number $choice (1-based) the blend's image and removes the rest. */
function choose_image_candidate(PDO $pdo, int $blendId, int $userId, int $choice): bool
{
    $candidates = list_image_candidates($blendId);
    if (!isset($candidates[$choice - 1])) {
        return false;
    }

    $dir = blend_image_dir($blendId);
    $source = $dir . '/' . basename($candidates[$choice - 1]);
    $ext = pathinfo($source, PATHINFO_EXTENSION);
    $finalName = 'image_' . bin2hex(random_bytes(4)) . '.' . $ext;

    if (!copy($source, $dir . '/' . $finalName)) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT image_path FROM blends WHERE id = ? AND user_id = ?');
    $stmt->execute([$blendId, $userId]);
    $old = $stmt->fetchColumn();

    $pdo->prepare('UPDATE blends SET image_path = ? WHERE id = ? AND user_id = ?')
        ->execute(['uploads/blends/' . $blendId . '/' . $finalName, $blendId, $userId]);

    if (is_string($old) && $old !== '' && basename($old) !== $finalName) {
        @unlink($dir . '/' . basename($old));
    }
    clear_image_candidates($blendId);

    return true;
}

function delete_blend_images(int $blendId): void
{
    $dir = blend_image_dir($blendId);
    foreach (glob($dir . '/*') ?: [] as $file) {
        @unlink($file);
    }
    @rmdir($dir);
}
