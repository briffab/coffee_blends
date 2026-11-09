<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$currentUser = current_user($pdo);
$blendId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$blend = get_blend($pdo, $blendId, current_user_id());
if (!$blend) {
    http_response_code(404);
    exit('Blend not found.');
}
if (!image_generation_enabled($config)) {
    header('Location: blend_view.php?id=' . $blendId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'generate') {
        // Release the session lock: generation can take a minute and would
        // otherwise block every other request from this browser.
        session_write_close();
        header('Content-Type: application/json');
        try {
            generate_image_candidates($config, $blendId, $blend['name']);
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            error_log('coffee_blends image generation failed: ' . $e->getMessage());
            http_response_code(502);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'choose') {
        if (choose_image_candidate($pdo, $blendId, current_user_id(), (int) ($_POST['choice'] ?? 0))) {
            header('Location: blend_view.php?id=' . $blendId . '&image=1');
            exit;
        }
        header('Location: blend_images.php?id=' . $blendId);
        exit;
    }

    http_response_code(400);
    exit('Unknown action.');
}

$candidates = list_image_candidates($blendId);
$autoStart = empty($candidates) && (empty($blend['image_path']) || isset($_GET['generate']));

$pageTitle = 'Pick an image';
require __DIR__ . '/includes/views/header.php';
?>
<h1 class="h3 mb-1">Pick an image</h1>
<p class="text-muted">For <strong><?= htmlspecialchars($blend['name']) ?></strong></p>

<form id="generate-form" method="post" class="d-none">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $blendId ?>">
  <input type="hidden" name="action" value="generate">
</form>

<div id="status" class="alert alert-info <?= $autoStart ? '' : 'd-none' ?>" role="status">
  <div class="d-flex align-items-center">
    <div class="spinner-border spinner-border-sm me-2" aria-hidden="true"></div>
    Creating five images from the blend name — this can take up to a minute. Please keep this page open.
  </div>
</div>
<div id="error" class="alert alert-danger d-none"></div>

<?php if ($candidates): ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $blendId ?>">
    <input type="hidden" name="action" value="choose">
    <div class="row g-3 mb-4">
      <?php foreach ($candidates as $i => $path): ?>
        <?php $version = (int) @filemtime(blend_image_dir($blendId) . '/' . basename($path)); ?>
        <div class="col-6 col-md-4">
          <button type="submit" name="choice" value="<?= $i + 1 ?>" class="candidate btn p-0 w-100" title="Use this image">
            <img src="<?= htmlspecialchars($path) ?>?v=<?= $version ?>" alt="Option <?= $i + 1 ?>" class="img-fluid rounded">
            <span class="d-block py-2 fw-semibold">Use this one</span>
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  </form>
<?php elseif (!empty($blend['image_path'])): ?>
  <img src="<?= htmlspecialchars($blend['image_path']) ?>" alt="Current image" class="img-fluid rounded mb-3" style="max-width: 320px;">
<?php endif; ?>

<div class="d-flex gap-2 align-items-center" id="actions">
  <button type="button" class="btn btn-outline-primary" id="regenerate">
    <?= $candidates || !empty($blend['image_path']) ? 'Generate five new images' : 'Generate images' ?>
  </button>
  <a href="blend_view.php?id=<?= $blendId ?>" class="btn btn-link">
    <?= $candidates || empty($blend['image_path']) ? 'Skip for now' : 'Keep current image' ?>
  </a>
</div>

<script>
(function () {
  var form = document.getElementById('generate-form');
  var statusBox = document.getElementById('status');
  var errorBox = document.getElementById('error');
  var button = document.getElementById('regenerate');

  function generate() {
    errorBox.classList.add('d-none');
    statusBox.classList.remove('d-none');
    button.disabled = true;
    fetch('blend_images.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
      .then(function (r) {
        return r.json().catch(function () { return { ok: false, error: 'Unexpected response from the server.' }; });
      })
      .then(function (data) {
        if (data.ok) {
          window.location.href = 'blend_images.php?id=<?= $blendId ?>';
          return;
        }
        throw new Error(data.error || 'Image generation failed.');
      })
      .catch(function (err) {
        statusBox.classList.add('d-none');
        errorBox.textContent = err.message + ' You can try again.';
        errorBox.classList.remove('d-none');
        button.disabled = false;
      });
  }

  button.addEventListener('click', generate);
  <?php if ($autoStart): ?>generate();<?php endif; ?>
})();
</script>
<?php require __DIR__ . '/includes/views/footer.php'; ?>
