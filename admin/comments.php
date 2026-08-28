<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
require __DIR__ . '/includes/auth-check.php';

$activePage = 'comments';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        $pdo->prepare("UPDATE comments SET status='approved' WHERE id=?")->execute([$id]);
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE comments SET status='rejected' WHERE id=?")->execute([$id]);
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM comments WHERE id=?')->execute([$id]);
    }
    header('Location: ' . url('admin/comments.php?status=' . urlencode($_GET['status'] ?? 'pending')));
    exit;
}

$filter = $_GET['status'] ?? 'pending';
if (!in_array($filter, ['pending', 'approved', 'rejected', 'all'], true)) {
    $filter = 'pending';
}

$sql = 'SELECT cm.*, a.title AS article_title, a.id AS article_id, a.slug AS article_slug
        FROM comments cm JOIN articles a ON cm.article_id = a.id';
$params = [];
if ($filter !== 'all') {
    $sql .= ' WHERE cm.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY cm.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll();

$counts = [
    'pending'  => count_comments_by_status($pdo, 'pending'),
    'approved' => count_comments_by_status($pdo, 'approved'),
    'rejected' => count_comments_by_status($pdo, 'rejected'),
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Commenti — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= url('admin/includes/admin.css') ?>">
</head>
<body>
<?php require __DIR__ . '/includes/topbar.php'; ?>

<div class="admin-wrap">

  <div class="stat-row">
    <div class="stat-box"><div class="num"><?= $counts['pending'] ?></div><div class="label">In attesa</div></div>
    <div class="stat-box"><div class="num"><?= $counts['approved'] ?></div><div class="label">Approvati</div></div>
    <div class="stat-box"><div class="num"><?= $counts['rejected'] ?></div><div class="label">Rifiutati</div></div>
  </div>

  <div class="admin-card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
      <h2 style="margin:0;">Commenti</h2>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= url('admin/comments.php?status=pending') ?>" class="btn btn-sm <?= $filter==='pending' ? 'btn-primary' : 'btn-secondary' ?>">In attesa</a>
        <a href="<?= url('admin/comments.php?status=approved') ?>" class="btn btn-sm <?= $filter==='approved' ? 'btn-primary' : 'btn-secondary' ?>">Approvati</a>
        <a href="<?= url('admin/comments.php?status=rejected') ?>" class="btn btn-sm <?= $filter==='rejected' ? 'btn-primary' : 'btn-secondary' ?>">Rifiutati</a>
        <a href="<?= url('admin/comments.php?status=all') ?>" class="btn btn-sm <?= $filter==='all' ? 'btn-primary' : 'btn-secondary' ?>">Tutti</a>
      </div>
    </div>

    <?php if (!$comments): ?>
      <p style="color:var(--ink-soft); padding:20px 0; text-align:center;">Nessun commento in questa categoria.</p>
    <?php endif; ?>

    <?php foreach ($comments as $c): ?>
      <div style="border-bottom:1px solid var(--line); padding:14px 0;">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
          <div>
            <strong><?= h($c['author_name']) ?></strong>
            <?php if ($c['author_email']): ?><span style="color:var(--ink-soft); font-size:12px;"> — <?= h($c['author_email']) ?></span><?php endif; ?>
            <div style="font-size:12px; color:var(--ink-soft); margin-top:2px;">
              su <a href="<?= article_url(['id' => $c['article_id'], 'slug' => $c['article_slug']]) ?>" target="_blank"><?= h($c['article_title']) ?></a>
              · <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
              · <span class="badge <?= $c['status'] !== 'approved' ? 'draft' : '' ?>" style="background:<?= $c['status']==='approved' ? '#4c9a6a' : ($c['status']==='rejected' ? '#e15656' : '') ?>"><?= ucfirst($c['status']) ?></span>
            </div>
          </div>
          <div style="display:flex; gap:6px; white-space:nowrap;">
            <?php if ($c['status'] !== 'approved'): ?>
              <form method="post" action="<?= url('admin/comments.php?status=' . $filter) ?>" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i></button>
              </form>
            <?php endif; ?>
            <?php if ($c['status'] !== 'rejected'): ?>
              <form method="post" action="<?= url('admin/comments.php?status=' . $filter) ?>" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-xmark"></i></button>
              </form>
            <?php endif; ?>
            <form method="post" action="<?= url('admin/comments.php?status=' . $filter) ?>" style="display:inline;" onsubmit="return confirm('Eliminare definitivamente questo commento?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
            </form>
          </div>
        </div>
        <p style="margin-top:10px; font-size:14px; line-height:1.6; white-space:pre-wrap;"><?= h($c['body']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

</div>
</body>
</html>
