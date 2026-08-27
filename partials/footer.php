<?php
/** partials/footer.php — richiede $pdo */
$footerBio  = get_setting($pdo, 'footer_bio', '');
$categories = get_categories($pdo);
$latest     = get_articles($pdo, null, true);
$latest     = array_slice($latest, 0, 4);

$socials = [
    'sito'      => ['icon' => 'fa-solid fa-globe',      'label' => 'Sito'],
    'github'    => ['icon' => 'fa-brands fa-github',     'label' => 'GitHub'],
    'instagram' => ['icon' => 'fa-brands fa-instagram',  'label' => 'Instagram'],
    'facebook'  => ['icon' => 'fa-brands fa-facebook-f', 'label' => 'Facebook'],
    'x'         => ['icon' => 'fa-brands fa-x-twitter',  'label' => 'X'],
    'youtube'   => ['icon' => 'fa-brands fa-youtube',    'label' => 'YouTube'],
    'tiktok'    => ['icon' => 'fa-brands fa-tiktok',     'label' => 'TikTok'],
    'telegram'  => ['icon' => 'fa-brands fa-telegram',   'label' => 'Telegram'],
    'kofi'      => ['icon' => 'fa-solid fa-mug-hot',     'label' => 'Ko-fi'],
    'linkedin'  => ['icon' => 'fa-brands fa-linkedin-in','label' => 'LinkedIn'],
];

$projectKeys = ['project_spiritualita', 'project_tech', 'project_canzoni', 'project_cineblog', 'project_laciurma', 'project_card'];

$subscribed = isset($_GET['subscribed']);
$subError   = isset($_GET['sub_error']);
?>
<footer>
  <div class="wrap">
    <div class="footer-grid">

      <div class="f-brand">
        <h2><?= h(get_setting($pdo, 'site_title', 'Scopri. Racconta. Sogna.')) ?></h2>
        <p><?= h($footerBio) ?></p>
        <a href="<?= url('index.php') ?>" class="f-cta">Scopri di più</a>
      </div>

      <div>
        <h4>Menu</h4>
        <ul>
          <li><a href="<?= url('index.php') ?>">Home</a></li>
          <li><a href="<?= url('index.php') ?>#grid">Categorie</a></li>
          <li><a href="<?= url('index.php') ?>#grid">Archivio</a></li>
          <li><a href="mailto:info@mrrearm.it">Contatti</a></li>
        </ul>
      </div>

      <div>
        <h4>Categorie</h4>
        <ul>
          <?php foreach ($categories as $cat): ?>
            <li><a href="<?= url('index.php?cat=' . urlencode($cat['slug'])) ?>"><i class="<?= h($cat['icon_class']) ?>"></i><?= h($cat['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h4>Ultime news</h4>
        <ul class="f-news">
          <?php foreach ($latest as $art): ?>
            <li><a href="<?= url('article.php?id=' . (int)$art['id']) ?>"><?= h($art['title']) ?><span class="f-date"><?= h($art['cat_name']) ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h4>Seguimi</h4>
        <div class="f-social-row">
          <?php foreach ($socials as $key => $meta):
            $link = get_setting($pdo, 'social_' . $key, '');
            if (!$link) continue; ?>
            <a href="<?= h($link) ?>" target="_blank" rel="noopener" title="<?= h($meta['label']) ?>"><i class="<?= h($meta['icon']) ?>"></i></a>
          <?php endforeach; ?>
        </div>
        <h4>Newsletter</h4>
        <div class="f-newsletter">
          <form action="<?= url('newsletter.php') ?>" method="post">
            <?= csrf_field() ?>
            <input type="email" name="email" placeholder="La tua email" required>
            <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
          </form>
        </div>
        <?php if ($subscribed): ?>
          <small class="ok">Iscrizione avvenuta ✓</small>
        <?php elseif ($subError): ?>
          <small>Email non valida o già iscritta.</small>
        <?php else: ?>
          <small>Ricevi le ultime notizie e curiosità ogni settimana.</small>
        <?php endif; ?>
      </div>

    </div>

    <div class="f-projects">
      <?php foreach ($projectKeys as $key):
        $raw = get_setting($pdo, $key, '');
        if (!$raw) continue;
        $p = parse_project_link($raw); ?>
        <a href="<?= h($p['url']) ?>" target="_blank" rel="noopener"><i class="<?= h($p['icon']) ?>"></i><?= h($p['label']) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="f-bottom">&copy; <?= date('Y') ?> <?= h(get_setting($pdo, 'site_title', 'Scopri. Racconta. Sogna.')) ?> — Tutti i diritti riservati &nbsp;·&nbsp; <a href="<?= url('admin/login.php') ?>" style="color:#7c8ba0">Area Riservata</a></div>
  </div>
</footer>

<script src="<?= url('assets/script.js') ?>"></script>
</body>
</html>
