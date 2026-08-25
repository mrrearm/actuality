<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$catSlug = $_GET['cat'] ?? null;
$articles = get_articles($pdo, $catSlug, true);
$pageTitle = get_setting($pdo, 'site_title', 'Scopri. Racconta. Sogna.');

require __DIR__ . '/partials/header.php';
?>

<div class="wrap">
  <main class="grid" id="grid">
    <?php if (!$articles): ?>
      <p style="grid-column:1/-1; text-align:center; color:var(--ink-soft); padding:40px 0;">
        Nessun articolo pubblicato <?= $catSlug ? 'in questa categoria' : '' ?> per il momento.
      </p>
    <?php endif; ?>

    <?php foreach ($articles as $i => $art): ?>
      <article class="card" data-cat="<?= h($art['cat_slug']) ?>" onclick="window.location.href='<?= url('article.php?id=' . (int)$art['id']) ?>'">
        <div class="card-media">
          <span class="card-badge"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
          <img src="<?= h($art['image_url']) ?>" alt="<?= h($art['title']) ?>">
        </div>
        <div class="card-body">
          <div class="card-tag" style="color:<?= h($art['color_hex']) ?>">
            <i class="<?= h($art['icon_class']) ?>"></i> <?= h($art['cat_name']) ?>
          </div>
          <h3><?= h($art['title']) ?></h3>
          <span class="read-more" style="color:<?= h($art['color_hex']) ?>">LEGGI DI PIÙ →</span>
        </div>
      </article>
    <?php endforeach; ?>
  </main>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
