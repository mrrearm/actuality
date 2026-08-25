<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$id = (int)($_GET['id'] ?? 0);
$article = $id ? get_article($pdo, $id) : null;

if (!$article || $article['status'] !== 'published') {
    http_response_code(404);
    $pageTitle = 'Articolo non trovato';
    require __DIR__ . '/partials/header.php';
    echo '<div class="article-page"><h1>Articolo non trovato</h1><p><a href="' . url('index.php') . '">Torna alla home</a></p></div>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = $article['title'] . ' — ' . get_setting($pdo, 'site_title', '');
require __DIR__ . '/partials/header.php';
?>

<div class="article-page">
  <a class="article-back" href="<?= url('index.php') ?>"><i class="fa-solid fa-arrow-left"></i> Torna a tutti gli articoli</a>

  <div class="article-media">
    <img src="<?= h($article['image_url']) ?>" alt="<?= h($article['title']) ?>">
  </div>

  <span class="article-tag" style="color:<?= h($article['color_hex']) ?>">
    <i class="<?= h($article['icon_class']) ?>"></i> <?= h($article['cat_name']) ?>
  </span>
  <h1><?= h($article['title']) ?></h1>

  <?= render_content($article['content']) ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
