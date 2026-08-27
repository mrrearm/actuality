<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
require __DIR__ . '/includes/auth-check.php';

$activePage = 'dashboard';

// filtro per categoria opzionale
$catSlug = $_GET['cat'] ?? null;
$allArticles = get_articles($pdo, $catSlug, false); // false = include anche le bozze
$categoriesMap = get_categories_for_articles($pdo, array_column($allArticles, 'id'));

$totalPublished = $pdo->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn();
$totalDraft     = $pdo->query("SELECT COUNT(*) FROM articles WHERE status='draft'")->fetchColumn();
$totalSubs      = $pdo->query("SELECT COUNT(*) FROM subscribers")->fetchColumn();
$categories     = get_categories($pdo);

$flash = $_GET['flash'] ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Articoli — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= url('admin/includes/admin.css') ?>">
</head>
<body>
<?php require __DIR__ . '/includes/topbar.php'; ?>

<div class="admin-wrap">

  <?php if ($flash === 'created'): ?><div class="flash flash-ok">Articolo creato correttamente.</div><?php endif; ?>
  <?php if ($flash === 'updated'): ?><div class="flash flash-ok">Articolo aggiornato correttamente.</div><?php endif; ?>
  <?php if ($flash === 'deleted'): ?><div class="flash flash-ok">Articolo eliminato.</div><?php endif; ?>

  <div class="stat-row">
    <div class="stat-box"><div class="num"><?= (int)$totalPublished ?></div><div class="label">Articoli pubblicati</div></div>
    <div class="stat-box"><div class="num"><?= (int)$totalDraft ?></div><div class="label">Bozze</div></div>
    <div class="stat-box"><div class="num"><?= (int)$totalSubs ?></div><div class="label">Iscritti newsletter</div></div>
  </div>

  <div class="admin-card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
      <h2 style="margin:0;">Tutti gli articoli</h2>
      <a href="<?= url('admin/article-form.php') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nuovo articolo</a>
    </div>

    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
      <a href="<?= url('admin/dashboard.php') ?>" class="btn btn-secondary btn-sm <?= !$catSlug ? 'btn-primary' : '' ?>">Tutte</a>
      <?php foreach ($categories as $cat): ?>
        <a href="<?= url('admin/dashboard.php?cat=' . urlencode($cat['slug'])) ?>"
           class="btn btn-sm <?= $catSlug === $cat['slug'] ? 'btn-primary' : 'btn-secondary' ?>"><?= h($cat['name']) ?></a>
      <?php endforeach; ?>
    </div>

    <table class="admin-table">
      <thead>
        <tr>
          <th>Immagine</th><th>Titolo</th><th>Categoria</th><th>Stato</th><th>Pubblicato</th><th>Azioni</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$allArticles): ?>
          <tr><td colspan="6" style="text-align:center; color:var(--ink-soft); padding:24px;">Nessun articolo trovato.</td></tr>
        <?php endif; ?>
        <?php foreach ($allArticles as $art):
          $artCats = $categoriesMap[(int)$art['id']] ?? [];
        ?>
          <tr>
            <td><img src="<?= h($art['image_url']) ?>" alt=""></td>
            <td><?= h($art['title']) ?></td>
            <td>
              <?php foreach ($artCats as $c): ?>
                <span class="color-swatch" style="background:<?= h($c['color_hex']) ?>" title="<?= h($c['name']) ?>"></span>
              <?php endforeach; ?>
              <?= h(implode(', ', array_column($artCats, 'name'))) ?>
            </td>
            <td><span class="badge <?= $art['status'] === 'draft' ? 'draft' : '' ?>" style="background:<?= $art['status'] === 'draft' ? '' : '#4c9a6a' ?>"><?= $art['status'] === 'draft' ? 'Bozza' : 'Pubblicato' ?></span></td>
            <td><?= date('d/m/Y', strtotime($art['published_at'])) ?></td>
            <td style="white-space:nowrap;">
              <a href="<?= url('admin/article-form.php?id=' . (int)$art['id']) ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-pen"></i></a>
              <a href="<?= url('article.php?id=' . (int)$art['id']) ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i></a>
              <form action="<?= url('admin/delete.php') ?>" method="post" style="display:inline;" onsubmit="return confirm('Eliminare definitivamente questo articolo?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$art['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>
</body>
</html>
