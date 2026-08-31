<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$pageTitle = 'Scopri di più — ' . get_setting($pdo, 'site_title', '');
$videoId = 'OhyAVD5pr0Q';

require __DIR__ . '/partials/header.php';
?>

<div class="article-page">
  <a class="article-back" href="<?= url('index.php') ?>"><i class="fa-solid fa-arrow-left"></i> Torna alla home</a>

  <h1>Scopri di più</h1>
  <p style="color:var(--ink-soft); line-height:1.7; margin-bottom:20px;">Guarda il video per saperne di più.</p>

  <div class="video-embed" id="videoEmbed" data-video-id="<?= h($videoId) ?>" onclick="loadVideo(this)">
    <img src="https://img.youtube.com/vi/<?= h($videoId) ?>/hqdefault.jpg" alt="Anteprima video" loading="lazy">
    <button type="button" class="video-play-btn" aria-label="Riproduci video"><i class="fa-solid fa-play"></i></button>
  </div>
</div>

<script>
function loadVideo(container) {
  var videoId = container.dataset.videoId;
  container.innerHTML = '<iframe src="https://www.youtube-nocookie.com/embed/' + videoId + '?autoplay=1" title="Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
  container.onclick = null;
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
