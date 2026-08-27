<?php
/** admin/includes/topbar.php — richiede url() e $activePage impostato dalla pagina chiamante */
$activePage = $activePage ?? '';
$pendingComments = count_comments_by_status($pdo, 'pending');
?>
<div class="admin-topbar">
  <div class="brand"><i class="fa-solid fa-gauge"></i> Dashboard — <?= h(get_setting($pdo, 'site_title', 'Scopri. Racconta. Sogna.')) ?></div>
  <nav>
    <a href="<?= url('admin/dashboard.php') ?>" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">Articoli</a>
    <a href="<?= url('admin/categories.php') ?>" class="<?= $activePage === 'categories' ? 'active' : '' ?>">Categorie</a>
    <a href="<?= url('admin/comments.php') ?>" class="<?= $activePage === 'comments' ? 'active' : '' ?>">Commenti<?php if ($pendingComments > 0): ?> <span class="nav-badge"><?= $pendingComments ?></span><?php endif; ?></a>
    <a href="<?= url('admin/settings.php') ?>" class="<?= $activePage === 'settings' ? 'active' : '' ?>">Impostazioni</a>
    <a href="<?= url('index.php') ?>" target="_blank">Vedi il sito ↗</a>
    <a href="<?= url('admin/logout.php') ?>" class="logout">Esci</a>
  </nav>
</div>
