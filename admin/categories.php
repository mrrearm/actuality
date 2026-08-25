<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
require __DIR__ . '/includes/auth-check.php';

$activePage = 'categories';
$error = ''; $success = '';

// --- creazione / modifica ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    csrf_check();
    $catId    = (int)($_POST['cat_id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $slug     = $catId ? null : slugify($name);
    $color    = trim($_POST['color_hex'] ?? '#1f8a94');
    $icon     = trim($_POST['icon_class'] ?? 'fa-solid fa-globe');
    $order    = (int)($_POST['sort_order'] ?? 0);

    if ($name === '') {
        $error = 'Il nome categoria è obbligatorio.';
    } elseif ($catId) {
        $stmt = $pdo->prepare('UPDATE categories SET name=?, color_hex=?, icon_class=?, sort_order=? WHERE id=?');
        $stmt->execute([$name, $color, $icon, $order, $catId]);
        $success = 'Categoria aggiornata.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO categories (slug, name, color_hex, icon_class, sort_order) VALUES (?,?,?,?,?)');
        $stmt->execute([$slug, $name, $color, $icon, $order]);
        $success = 'Categoria creata.';
    }
}

// --- eliminazione ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $catId = (int)($_POST['cat_id'] ?? 0);
    $countArticles = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE category_id = ?');
    $countArticles->execute([$catId]);
    if ((int)$countArticles->fetchColumn() > 0) {
        $error = 'Non puoi eliminare una categoria che contiene ancora articoli: spostali o eliminali prima.';
    } else {
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$catId]);
        $success = 'Categoria eliminata.';
    }
}

$categories = get_categories($pdo);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Categorie — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= url('admin/includes/admin.css') ?>">
</head>
<body>
<?php require __DIR__ . '/includes/topbar.php'; ?>

<div class="admin-wrap">

  <?php if ($error): ?><div class="flash flash-error"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="flash flash-ok"><?= h($success) ?></div><?php endif; ?>

  <div class="admin-card">
    <h2>Categorie esistenti</h2>
    <table class="admin-table">
      <thead><tr><th>Colore</th><th>Nome</th><th>Slug</th><th>Icona</th><th>Ordine</th><th>Azioni</th></tr></thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
          <tr>
            <td><span class="color-swatch" style="background:<?= h($cat['color_hex']) ?>"></span></td>
            <td><?= h($cat['name']) ?></td>
            <td><code><?= h($cat['slug']) ?></code></td>
            <td><i class="<?= h($cat['icon_class']) ?>"></i> <?= h($cat['icon_class']) ?></td>
            <td><?= (int)$cat['sort_order'] ?></td>
            <td style="white-space:nowrap;">
              <button type="button" class="btn btn-secondary btn-sm" onclick="editCat(<?= (int)$cat['id'] ?>, '<?= h(addslashes($cat['name'])) ?>', '<?= h($cat['color_hex']) ?>', '<?= h(addslashes($cat['icon_class'])) ?>', <?= (int)$cat['sort_order'] ?>)"><i class="fa-solid fa-pen"></i></button>
              <form action="" method="post" style="display:inline;" onsubmit="return confirm('Eliminare questa categoria? È possibile solo se non contiene articoli.');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="cat_id" value="<?= (int)$cat['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-card">
    <h2 id="formTitle">Nuova categoria</h2>
    <form method="post" id="catForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="cat_id" id="cat_id" value="">

      <div class="form-grid">
        <div class="form-row">
          <label>Nome</label>
          <input type="text" name="name" id="cat_name" required>
        </div>
        <div class="form-row">
          <label>Ordine di visualizzazione</label>
          <input type="number" name="sort_order" id="cat_order" value="0">
        </div>
      </div>

      <div class="form-grid">
        <div class="form-row">
          <label>Colore (hex)</label>
          <input type="text" name="color_hex" id="cat_color" value="#1f8a94">
        </div>
        <div class="form-row">
          <label>Icona Font Awesome (es. fa-solid fa-globe)</label>
          <input type="text" name="icon_class" id="cat_icon" value="fa-solid fa-globe">
          <div class="hint">Sfoglia le icone su <a href="https://fontawesome.com/search" target="_blank" rel="noopener">fontawesome.com/search</a></div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Salva categoria</button>
      <button type="button" class="btn btn-secondary" onclick="resetForm()">Nuova (annulla modifica)</button>
    </form>
  </div>
</div>

<script>
function editCat(id, name, color, icon, order){
  document.getElementById('formTitle').textContent = 'Modifica categoria';
  document.getElementById('cat_id').value = id;
  document.getElementById('cat_name').value = name;
  document.getElementById('cat_color').value = color;
  document.getElementById('cat_icon').value = icon;
  document.getElementById('cat_order').value = order;
  window.scrollTo({top: document.getElementById('catForm').offsetTop - 20, behavior:'smooth'});
}
function resetForm(){
  document.getElementById('formTitle').textContent = 'Nuova categoria';
  document.getElementById('catForm').reset();
  document.getElementById('cat_id').value = '';
}
</script>
</body>
</html>
