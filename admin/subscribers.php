<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
require __DIR__ . '/includes/auth-check.php';

$activePage = 'subscribers';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM subscribers WHERE id = ?')->execute([$id]);
    header('Location: ' . url('admin/subscribers.php?flash=deleted'));
    exit;
}

$subscribers = $pdo->query('SELECT * FROM subscribers ORDER BY created_at DESC')->fetchAll();
$total = count($subscribers);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iscritti newsletter — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= url('admin/includes/admin.css') ?>">
</head>
<body>
<?php require __DIR__ . '/includes/topbar.php'; ?>

<div class="admin-wrap">

  <?php if (isset($_GET['flash']) && $_GET['flash'] === 'deleted'): ?>
    <div class="flash flash-ok">Iscritto rimosso correttamente.</div>
  <?php endif; ?>

  <div class="stat-row">
    <div class="stat-box"><div class="num"><?= $total ?></div><div class="label">Iscritti totali</div></div>
  </div>

  <div class="admin-card">
    <h2>Iscritti alla newsletter</h2>

    <?php if (!$subscribers): ?>
      <p style="color:var(--ink-soft); padding:20px 0; text-align:center;">Nessun iscritto ancora.</p>
    <?php endif; ?>

    <?php if ($subscribers): ?>
    <table class="admin-table">
      <thead>
        <tr><th>Email</th><th>Iscritto il</th><th>Azioni</th></tr>
      </thead>
      <tbody>
        <?php foreach ($subscribers as $sub): ?>
          <tr>
            <td><?= h($sub['email']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
            <td>
              <form method="post" style="display:inline;" onsubmit="return confirm('Rimuovere questo iscritto? Non riceverà più nessuna email.');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$sub['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
