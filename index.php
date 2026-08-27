<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$catSlug = $_GET['cat'] ?? null;
$articles = get_articles($pdo, $catSlug, true);
$pageTitle = get_setting($pdo, 'site_title', 'Scopri. Racconta. Sogna.');

// Query in blocco (una sola chiamata) invece che una per articolo: importante soprattutto con Turso
$articleIds = array_column($articles, 'id');
$categoriesMap = get_categories_for_articles($pdo, $articleIds);
$ratingsMap = get_ratings_for_articles($pdo, $articleIds);

require __DIR__ . '/partials/header.php';
?>

<div class="wrap">
  <main class="grid" id="grid">
    <?php if (!$articles): ?>
      <p style="grid-column:1/-1; text-align:center; color:var(--ink-soft); padding:40px 0;">
        Nessun articolo pubblicato <?= $catSlug ? 'in questa categoria' : '' ?> per il momento.
      </p>
    <?php endif; ?>

    <?php foreach ($articles as $i => $art):
      $artCats = $categoriesMap[(int)$art['id']] ?? [];
      $slugs = implode(' ', array_column($artCats, 'slug'));
      $rating = $ratingsMap[(int)$art['id']] ?? ['count' => 0, 'average' => 0.0];
    ?>
      <article class="card" data-cat="<?= h($slugs) ?>" onclick="window.location.href='<?= url('article.php?id=' . (int)$art['id']) ?>'">
        <div class="card-media">
          <span class="card-badge"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
          <img src="<?= h($art['image_url']) ?>" alt="<?= h($art['title']) ?>">
        </div>
        <div class="card-body">
          <div class="card-tags">
            <?php foreach ($artCats as $c): ?>
              <span class="card-tag" style="color:<?= h($c['color_hex']) ?>"><i class="<?= h($c['icon_class']) ?>"></i> <?= h($c['name']) ?></span>
            <?php endforeach; ?>
          </div>
          <h3><?= h($art['title']) ?></h3>
          <?php if ($rating['count'] > 0): ?>
            <div class="card-rating"><?= render_stars_display($rating['average']) ?> <span>(<?= $rating['count'] ?>)</span></div>
          <?php endif; ?>
          <span class="read-more" style="color:<?= h($art['color_hex']) ?>">LEGGI DI PIÙ →</span>
        </div>
      </article>
    <?php endforeach; ?>
  </main>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
