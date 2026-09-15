<?php

declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require_login();

$currentUser = current_user($pdo);
$blendId = (int) ($_GET['id'] ?? 0);
$blend = get_blend($pdo, $blendId, current_user_id());
if (!$blend) {
    http_response_code(404);
    exit('Blend not found.');
}

function fmt_value($value, string $suffix = ''): string
{
    return $value === null || $value === '' ? '—' : htmlspecialchars((string) $value) . $suffix;
}

$pageTitle = $blend['name'];
require __DIR__ . '/../src/views/header.php';
?>
<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h1 class="h3 mb-1"><?= htmlspecialchars($blend['name']) ?></h1>
    <p class="text-muted mb-0">Roasted <?= htmlspecialchars($blend['roast_date']) ?></p>
  </div>
  <a href="blend_edit.php?id=<?= (int) $blend['id'] ?>" class="btn btn-outline-secondary">Edit</a>
</div>

<?php if (!empty($_GET['created'])): ?><div class="alert alert-success">Blend saved.</div><?php endif; ?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success">Changes saved.</div><?php endif; ?>

<h2 class="h5">Beans</h2>
<div class="table-responsive mb-4">
  <table class="table bg-white align-middle">
    <thead>
      <tr>
        <th>Variety</th><th>Weight</th><th>Roast level</th><th>Roast temp</th><th>Roast time</th>
        <th>Roaster start temp</th><th>Ambient temp</th><th>Sky</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($blend['beans'] as $bean): ?>
        <tr>
          <td><?= htmlspecialchars($bean['variety']) ?></td>
          <td><?= fmt_value($bean['weight_g'], 'g') ?></td>
          <td><span class="badge bg-secondary"><?= ucwords(str_replace('_', ' ', $bean['roast_level'])) ?></span></td>
          <td><?= fmt_value($bean['roast_temperature'], '°') ?></td>
          <td><?= fmt_value($bean['roast_time_minutes'], ' min') ?></td>
          <td><?= fmt_value($bean['roaster_start_temperature'], '°') ?></td>
          <td><?= fmt_value($bean['ambient_temperature'], '°') ?></td>
          <td><?= $bean['cloud_conditions'] ? ucfirst($bean['cloud_conditions']) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="row">
  <div class="col-md-6 mb-3">
    <h2 class="h5">Espresso notes</h2>
    <p class="bg-white p-3 rounded border"><?= $blend['espresso_notes'] !== null && $blend['espresso_notes'] !== '' ? nl2br(htmlspecialchars($blend['espresso_notes'])) : '<span class="text-muted">No notes yet.</span>' ?></p>
  </div>
  <div class="col-md-6 mb-3">
    <h2 class="h5">Milky drink notes</h2>
    <p class="bg-white p-3 rounded border"><?= $blend['milky_notes'] !== null && $blend['milky_notes'] !== '' ? nl2br(htmlspecialchars($blend['milky_notes'])) : '<span class="text-muted">No notes yet.</span>' ?></p>
  </div>
</div>

<a href="home.php" class="btn btn-link ps-0">&larr; Back to all blends</a>

<?php require __DIR__ . '/../src/views/footer.php'; ?>
