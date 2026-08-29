<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

const SEARCH_RESULTS_PER_PAGE = 16;

$query = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$totalArticles = $query !== '' ? count_search_articles($pdo, $query, true) : 0;
$totalPages = max(1, (int)ceil($totalArticles / SEARCH_RESULTS_PER_PAGE));
$page = min($page, $totalPages);
$offset = ($page - 1) * SEARCH_RESULTS_PER_PAGE;

$articles = $query !== '' ? search_articles($pdo, $query, true, SEARCH_RESULTS_PER_PAGE, $offset) : [];
$pageTitle = 'Risultati per "' . $query . '" — ' . get_setting($pdo, 'site_title', '');

$articleIds = array_column($articles, 'id');
$categoriesMap = get_categories_for_articles($pdo, $articleIds);
$ratingsMap = get_ratings_for_articles($pdo, $articleIds);

function search_page_url(int $p, string $query): string {
    return url('search.php?' . http_build_query(['q' => $query, 'page' => $p])) . '#grid';
}

require __DIR__ . '/partials/header.php';
?>

<div class="wrap">
  <h2 class="search-results-title">
    <?php if ($query === ''): ?>
      Scrivi qualcosa da cercare
    <?php else: ?>
      <?= $totalArticles ?> risultat<?= $totalArticles === 1 ? 'o' : 'i' ?> per "<?= h($query) ?>"
    <?php endif; ?>
  </h2>

  <main class="grid" id="grid">
    <?php if ($query !== '' && !$articles): ?>
      <p style="grid-column:1/-1; text-align:center; color:var(--ink-soft); padding:40px 0;">
        Nessun articolo trovato per "<?= h($query) ?>". Prova con un'altra parola chiave.
      </p>
    <?php endif; ?>

    <?php foreach ($articles as $i => $art):
      $artCats = $categoriesMap[(int)$art['id']] ?? [];
      $slugs = implode(' ', array_column($artCats, 'slug'));
      $rating = $ratingsMap[(int)$art['id']] ?? ['count' => 0, 'average' => 0.0];
    ?>
      <article class="card" data-cat="<?= h($slugs) ?>" onclick="window.location.href='<?= article_url($art) ?>'">
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

  <?php if ($totalPages > 1): ?>
    <nav class="pagination">
      <?php if ($page > 1): ?>
        <a href="<?= search_page_url($page - 1, $query) ?>" class="page-link page-nav"><i class="fa-solid fa-chevron-left"></i> Precedenti</a>
      <?php endif; ?>
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="<?= search_page_url($p, $query) ?>" class="page-link <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="<?= search_page_url($page + 1, $query) ?>" class="page-link page-nav">Successivi <i class="fa-solid fa-chevron-right"></i></a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
