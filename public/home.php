<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$currentUser = current_user($pdo);
$onlyMine = !empty($_GET['mine']);
$blends = get_blends($pdo, $onlyMine ? (int) $currentUser['id'] : null);
$ratings = get_blend_ratings($pdo);

// The chosen sort comes from the menu (?sort=) and is remembered in a cookie
// so the list stays the way you left it next time.
$sort = normalize_blend_sort($_GET['sort'] ?? ($_COOKIE['blend_sort'] ?? null));
if (isset($_GET['sort'], BLEND_SORTS[$_GET['sort']]) && ($_COOKIE['blend_sort'] ?? null) !== $sort) {
    setcookie('blend_sort', $sort, [
        'expires' => time() + 365 * 24 * 3600,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
$blends = sort_blends($blends, $ratings, $sort);

$listUrl = static fn (bool $mine): string => 'home.php' . (
    ($query = http_build_query(array_filter(['mine' => $mine ? 1 : null, 'sort' => $sort !== DEFAULT_BLEND_SORT ? $sort : null]))) !== '' ? '?' . $query : ''
);

$pageTitle = $onlyMine ? 'My blends' : 'Family blends';
require __DIR__ . '/includes/views/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?= $onlyMine ? 'My blends' : 'Family blends' ?></h1>
  <a href="blend_add.php" class="btn btn-primary">+ Add blend</a>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <ul class="nav nav-pills">
    <li class="nav-item"><a class="nav-link <?= $onlyMine ? '' : 'active' ?>" href="<?= htmlspecialchars($listUrl(false)) ?>">Everyone's</a></li>
    <li class="nav-item"><a class="nav-link <?= $onlyMine ? 'active' : '' ?>" href="<?= htmlspecialchars($listUrl(true)) ?>">Mine</a></li>
  </ul>
  <form method="get" action="home.php" class="blend-sort d-flex align-items-center gap-2">
    <?php if ($onlyMine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
    <label for="sort" class="small text-muted mb-0">Sort by</label>
    <select id="sort" name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
      <?php foreach (BLEND_SORTS as $key => $label): ?>
        <option value="<?= $key ?>" <?= $key === $sort ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="btn btn-sm btn-outline-secondary">Go</button></noscript>
  </form>
</div>

<?php if (!empty($_GET['deleted'])): ?>
  <div class="alert alert-success">Blend deleted.</div>
<?php endif; ?>

<?php if (empty($blends)): ?>
  <p class="text-muted"><?= $onlyMine ? "You haven't added any blends yet." : 'No blends recorded yet.' ?> Roast something and add the first blend!</p>
<?php else: ?>
  <div class="blend-list">
    <?php foreach ($blends as $blend): ?>
      <div class="blend-item">
        <a class="blend-item-main" href="blend_view.php?id=<?= (int) $blend['id'] ?>">
          <?php if (!empty($blend['image_path'])): ?>
            <img src="<?= htmlspecialchars(blend_thumb_url($blend['image_path'])) ?>" alt="" width="64" height="64" loading="lazy" decoding="async" class="blend-thumb rounded">
          <?php else: ?>
            <span class="blend-thumb blend-thumb-empty rounded" aria-hidden="true">&#9749;</span>
          <?php endif; ?>
          <span class="blend-item-text">
            <span class="blend-name"><?= htmlspecialchars($blend['name']) ?></span>
            <span class="blend-meta">
              <?= htmlspecialchars(format_date_for_display($blend['roast_date'])) ?>
              &middot; <?= htmlspecialchars(display_name(['name' => $blend['owner_name'], 'email' => $blend['owner_email']])) ?>
              &middot; <?= (int) $blend['bean_count'] ?> <?= (int) $blend['bean_count'] === 1 ? 'bean' : 'beans' ?>
              <?php if (isset($ratings[(int) $blend['id']])): ?>
                &middot; <span class="score-star">&#9733;</span> <?= format_score($ratings[(int) $blend['id']]['average']) ?>
              <?php endif; ?>
            </span>
          </span>
        </a>
        <?php if (can_manage_blend($blend, $currentUser)): ?>
          <a class="btn btn-sm btn-outline-secondary" href="blend_edit.php?id=<?= (int) $blend['id'] ?>">Edit</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/views/footer.php'; ?>
