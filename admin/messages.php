<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
require __DIR__ . '/includes/auth-check.php';

$activePage = 'messages';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
    header('Location: ' . url('admin/messages.php?flash=deleted'));
    exit;
}

$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messaggi — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= url('admin/includes/admin.css') ?>">
</head>
<body>
<?php require __DIR__ . '/includes/topbar.php'; ?>

<div class="admin-wrap">

  <?php if (isset($_GET['flash']) && $_GET['flash'] === 'deleted'): ?>
    <div class="flash flash-ok">Messaggio eliminato.</div>
  <?php endif; ?>

  <div class="stat-row">
    <div class="stat-box"><div class="num"><?= count($messages) ?></div><div class="label">Messaggi ricevuti</div></div>
  </div>

  <div class="admin-card">
    <h2>Messaggi dal modulo contatti</h2>

    <?php if (!$messages): ?>
      <p style="color:var(--ink-soft); padding:20px 0; text-align:center;">Nessun messaggio ricevuto ancora.</p>
    <?php endif; ?>

    <?php foreach ($messages as $m): ?>
      <div style="border-bottom:1px solid var(--line); padding:16px 0;">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
          <div>
            <strong><?= h($m['name']) ?></strong>
            <span style="color:var(--ink-soft); font-size:12px;"> — <?= h($m['email']) ?></span>
            <div style="font-size:12px; color:var(--ink-soft); margin-top:2px;">
              <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?>
            </div>
          </div>
          <div style="display:flex; gap:6px; white-space:nowrap;">
            <a href="mailto:<?= h($m['email']) ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-reply"></i> Rispondi</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare questo messaggio?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
            </form>
          </div>
        </div>
        <p style="margin-top:10px; font-size:14px; line-height:1.6; white-space:pre-wrap;"><?= h($m['message']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

</div>
</body>
</html>
