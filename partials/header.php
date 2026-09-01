<?php
/** partials/header.php — richiede $pdo, opzionale $hideNav = true su article.php */
$siteTitle   = get_setting($pdo, 'site_title', 'Scopri. Racconta. Sogna.');
$siteTagline = get_setting($pdo, 'site_tagline', 'News, curiosità e attualità ogni giorno');
$siteEyebrow = get_setting($pdo, 'site_eyebrow', 'Il blog di Ray D. — Mr ReArm');
$heroImage   = get_setting($pdo, 'hero_image', 'https://picsum.photos/seed/newsdesk/1600/700');
$categories  = get_categories($pdo);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle ?? $siteTitle) ?></title>
<?php
// Tag Open Graph / Twitter Card per le anteprime di condivisione (Facebook, Telegram, WhatsApp, ecc.).
// Le pagine (es. article.php) possono sovrascrivere $ogTitle/$ogDescription/$ogImage/$ogUrl
// prima di includere questo header; altrimenti si usano i valori di default del sito.
$ogTitle       = $ogTitle       ?? ($pageTitle ?? $siteTitle);
$ogDescription = $ogDescription ?? $siteTagline;
$ogImage       = $ogImage       ?? $heroImage;
$ogUrl         = $ogUrl         ?? absolute_url($_SERVER['REQUEST_URI'] ?? '');
?>
<meta property="og:type" content="article">
<meta property="og:site_name" content="<?= h($siteTitle) ?>">
<meta property="og:title" content="<?= h($ogTitle) ?>">
<meta property="og:description" content="<?= h($ogDescription) ?>">
<meta property="og:image" content="<?= h($ogImage) ?>">
<meta property="og:url" content="<?= h($ogUrl) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($ogTitle) ?>">
<meta name="twitter:description" content="<?= h($ogDescription) ?>">
<meta name="twitter:image" content="<?= h($ogImage) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= url('assets/style.css') ?>">
</head>
<body>

<header class="hero" style="background: linear-gradient(180deg, rgba(10,20,35,.55), rgba(10,20,35,.35)), url('<?= h($heroImage) ?>') center/cover no-repeat;">
  <div>
    <div class="hero-eyebrow"><?= h($siteEyebrow) ?></div>
    <h1 onclick="window.location.href='<?= url('index.php') ?>'"><?= h($siteTitle) ?></h1>
    <div class="swoosh"></div>
    <p class="sub"><?= h($siteTagline) ?></p>
  </div>
</header>

<div class="wrap">
  <div class="hero-panel" id="categories">
    <form class="search-bar" action="<?= url('search.php') ?>" method="get">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" name="q" placeholder="Cerca un articolo..." value="<?= h($_GET['q'] ?? '') ?>" maxlength="100">
      <button type="submit">Cerca</button>
    </form>

    <nav class="cat-nav">
      <?php foreach ($categories as $cat): ?>
        <div class="cat-pill" data-cat="<?= h($cat['slug']) ?>" onclick="filterCategory('<?= h($cat['slug']) ?>')"
             style="color:<?= h($cat['color_hex']) ?>; background:<?= h($cat['color_hex']) ?>1a;">
          <i class="<?= h($cat['icon_class']) ?>"></i> <?= upper(h($cat['name'])) ?>
        </div>
      <?php endforeach; ?>
    </nav>
  </div>
</div>
