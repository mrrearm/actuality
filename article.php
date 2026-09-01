<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$slugParam = $_GET['slug'] ?? null;
$idParam = (int)($_GET['id'] ?? 0);

if ($slugParam) {
    $article = get_article_by_slug($pdo, $slugParam);
} elseif ($idParam) {
    $article = get_article($pdo, $idParam);
} else {
    $article = null;
}

if (!$article || $article['status'] !== 'published') {
    http_response_code(404);
    $pageTitle = 'Articolo non trovato';
    require __DIR__ . '/partials/header.php';
    echo '<div class="article-page"><h1>Articolo non trovato</h1><p><a href="' . url('index.php') . '">Torna alla home</a></p></div>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$id = (int)$article['id'];

$pageTitle = $article['title'] . ' — ' . get_setting($pdo, 'site_title', '');
$articleCategories = get_article_categories($pdo, $id);
$ratingStats = get_rating_stats($pdo, $id);
$comments = get_comments($pdo, $id, true);

$hasVoted = isset($_COOKIE['voted_articles']) && in_array((string)$id, explode(',', $_COOKIE['voted_articles']), true);

$currentUrl = absolute_url(article_url($article));
$shareTitle = rawurlencode($article['title']);
$shareUrl = rawurlencode($currentUrl);

// Valori per i tag Open Graph/Twitter Card, letti da partials/header.php per l'anteprima di condivisione
$rawExcerpt = $article['excerpt'] ?: strip_tags($article['content']);
$ogTitle = $article['title'];
$ogDescription = function_exists('mb_substr') ? mb_substr($rawExcerpt, 0, 160) : substr($rawExcerpt, 0, 160);
$ogImage = $article['image_url'];
$ogUrl = $currentUrl;

require __DIR__ . '/partials/header.php';
?>

<div class="article-page">
  <a class="article-back" href="<?= url('index.php') ?>"><i class="fa-solid fa-arrow-left"></i> Torna a tutti gli articoli</a>

  <div class="article-media">
    <img src="<?= h($article['image_url']) ?>" alt="<?= h($article['title']) ?>">
  </div>

  <div class="article-tags">
    <?php foreach ($articleCategories as $c): ?>
      <span class="article-tag" style="color:<?= h($c['color_hex']) ?>">
        <i class="<?= h($c['icon_class']) ?>"></i> <?= h($c['name']) ?>
      </span>
    <?php endforeach; ?>
  </div>

  <h1><?= h($article['title']) ?></h1>

  <?= render_content($article['content']) ?>

  <!-- Condivisione social -->
  <div class="share-row">
    <span class="share-label">Condividi:</span>
    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
    <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank" rel="noopener" title="X"><i class="fa-brands fa-x-twitter"></i></a>
    <a href="https://api.whatsapp.com/send?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
    <a href="https://t.me/share/url?text=<?= $shareTitle ?>&url=<?= $shareUrl ?>" target="_blank" rel="noopener" title="Telegram"><i class="fa-brands fa-telegram"></i></a>
    <button type="button" class="share-copy" onclick="copyArticleLink(this)" title="Copia link"><i class="fa-solid fa-link"></i></button>
  </div>

  <!-- Voto a stelle -->
  <div class="rating-box" id="voto">
    <h3>Quanto ti è piaciuto questo articolo?</h3>
    <?php if ($hasVoted): ?>
      <div class="rating-result">
        <?= render_stars_display($ratingStats['average']) ?>
        <span><?= $ratingStats['average'] ?> su 5 — <?= $ratingStats['count'] ?> <?= $ratingStats['count'] === 1 ? 'voto' : 'voti' ?> (hai già votato, grazie!)</span>
      </div>
    <?php else: ?>
      <form method="post" action="<?= url('rating-submit.php') ?>" class="star-rating">
        <?= csrf_field() ?>
        <input type="hidden" name="article_id" value="<?= (int)$id ?>">
        <input type="radio" id="star5" name="rating" value="5" onchange="this.form.submit()"><label for="star5" title="5 stelle">★</label>
        <input type="radio" id="star4" name="rating" value="4" onchange="this.form.submit()"><label for="star4" title="4 stelle">★</label>
        <input type="radio" id="star3" name="rating" value="3" onchange="this.form.submit()"><label for="star3" title="3 stelle">★</label>
        <input type="radio" id="star2" name="rating" value="2" onchange="this.form.submit()"><label for="star2" title="2 stelle">★</label>
        <input type="radio" id="star1" name="rating" value="1" onchange="this.form.submit()"><label for="star1" title="1 stella">★</label>
      </form>
      <?php if ($ratingStats['count'] > 0): ?>
        <div class="rating-result-small"><?= render_stars_display($ratingStats['average']) ?> media attuale: <?= $ratingStats['average'] ?>/5 (<?= $ratingStats['count'] ?> <?= $ratingStats['count'] === 1 ? 'voto' : 'voti' ?>)</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Commenti -->
  <div class="comments-section" id="commenti">
    <h3><?= count($comments) ?> <?= count($comments) === 1 ? 'commento' : 'commenti' ?></h3>

    <?php foreach ($comments as $c): ?>
      <div class="comment">
        <div class="comment-author"><?= h($c['author_name']) ?> <span class="comment-date"><?= date('d/m/Y', strtotime($c['created_at'])) ?></span></div>
        <p><?= nl2br(h($c['body'])) ?></p>
      </div>
    <?php endforeach; ?>

    <?php if (!$comments): ?>
      <p class="no-comments">Ancora nessun commento. Sii il primo a scrivere cosa ne pensi!</p>
    <?php endif; ?>

    <div id="scrivi-commento" class="comment-form-box">
      <h4>Lascia un commento</h4>
      <?php if (isset($_GET['comment_sent'])): ?>
        <div class="comment-flash ok">Grazie! Il tuo commento è in attesa di approvazione e comparirà a breve.</div>
      <?php elseif (isset($_GET['comment_error'])): ?>
        <div class="comment-flash error">Controlla i dati inseriti: nome e commento sono obbligatori.</div>
      <?php endif; ?>
      <form method="post" action="<?= url('comment-submit.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="article_id" value="<?= (int)$id ?>">
        <!-- campo trappola per bot: invisibile agli utenti reali -->
        <div class="hp-field" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

        <div class="comment-form-row">
          <input type="text" name="author_name" placeholder="Il tuo nome" maxlength="100" required>
          <input type="email" name="author_email" placeholder="Email (facoltativa, non pubblicata)">
        </div>
        <textarea name="body" placeholder="Scrivi il tuo commento..." maxlength="2000" required></textarea>
        <button type="submit" class="btn-comment-submit">Invia commento</button>
      </form>
    </div>
  </div>
</div>

<script>
function copyArticleLink(btn){
  navigator.clipboard.writeText(<?= json_encode($currentUrl) ?>).then(() => {
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check"></i>';
    setTimeout(() => btn.innerHTML = original, 1500);
  });
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
