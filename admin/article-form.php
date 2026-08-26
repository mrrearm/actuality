<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
require __DIR__ . '/includes/auth-check.php';

$activePage = 'dashboard';
$categories = get_categories($pdo);

$id = (int)($_GET['id'] ?? 0);
$article = $id ? get_article($pdo, $id) : null;
$isEdit = (bool)$article;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title       = trim($_POST['title'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $excerpt     = trim($_POST['excerpt'] ?? '');
    $content     = trim($_POST['content'] ?? '');
    $status      = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
    $imageUrlIn  = trim($_POST['image_url'] ?? '');

    if ($title === '' || $categoryId <= 0 || $content === '') {
        $error = 'Titolo, categoria e contenuto sono obbligatori.';
    } else {
        $finalImage = $imageUrlIn ?: 'https://picsum.photos/seed/' . uniqid() . '/900/500';

        if (!empty($_FILES['image_file']['name'])) {
            $allowed = ['jpg','jpeg','png','webp','gif'];
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed, true) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
                $newName = uniqid('art_') . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $newName)) {
                    $finalImage = url('assets/uploads/' . $newName);
                }
            } else {
                $error = 'Formato immagine non valido (usa jpg, png, webp o gif).';
            }
        }

        if (!$error) {
            $slugBase = slugify($title);
            $slug = $slugBase;

            if ($isEdit) {
                $stmt = $pdo->prepare('UPDATE articles SET category_id=?, title=?, slug=?, excerpt=?, content=?, image_url=?, status=? WHERE id=?');
                $stmt->execute([$categoryId, $title, $slug, $excerpt, $content, $finalImage, $status, $id]);
                header('Location: ' . url('admin/dashboard.php?flash=updated'));
                exit;
            } else {
                $check = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE slug = ?');
                $suffix = 1;
                while (true) {
                    $check->execute([$slug]);
                    if ((int)$check->fetchColumn() === 0) break;
                    $slug = $slugBase . '-' . (++$suffix);
                }
                $stmt = $pdo->prepare('INSERT INTO articles (category_id, title, slug, excerpt, content, image_url, status) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$categoryId, $title, $slug, $excerpt, $content, $finalImage, $status]);
                header('Location: ' . url('admin/dashboard.php?flash=created'));
                exit;
            }
        }
    }
}

$v = [
    'title'       => $article['title']       ?? ($_POST['title']       ?? ''),
    'category_id' => $article['category_id'] ?? ($_POST['category_id'] ?? ''),
    'excerpt'     => $article['excerpt']      ?? ($_POST['excerpt']     ?? ''),
    'content'     => $article['content']      ?? ($_POST['content']     ?? ''),
    'image_url'   => $article['image_url']    ?? ($_POST['image_url']   ?? ''),
    'status'      => $article['status']       ?? ($_POST['status']      ?? 'published'),
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $isEdit ? 'Modifica articolo' : 'Nuovo articolo' ?> — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= url('admin/includes/admin.css') ?>">
</head>
<body>
<?php require __DIR__ . '/includes/topbar.php'; ?>

<div class="admin-wrap">
  <div class="admin-card">
    <h2><?= $isEdit ? 'Modifica articolo' : 'Nuovo articolo' ?></h2>

    <?php if ($error): ?><div class="flash flash-error"><?= h($error) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="form-row">
        <label>Titolo</label>
        <input type="text" name="title" value="<?= h($v['title']) ?>" required>
      </div>

      <div class="form-grid">
        <div class="form-row">
          <label>Categoria</label>
          <select name="category_id" required>
            <option value="">— scegli —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= (string)$v['category_id'] === (string)$cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <label>Stato</label>
          <select name="status">
            <option value="published" <?= $v['status'] === 'published' ? 'selected' : '' ?>>Pubblicato</option>
            <option value="draft" <?= $v['status'] === 'draft' ? 'selected' : '' ?>>Bozza</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <label>Riassunto breve (facoltativo, mostrato in anteprima)</label>
        <input type="text" name="excerpt" value="<?= h($v['excerpt']) ?>" maxlength="300">
      </div>

      <div class="form-row">
        <label>URL immagine (usata se non carichi un file sotto)</label>
        <input type="url" name="image_url" value="<?= h($v['image_url']) ?>" placeholder="https://...">
      </div>

      <div class="form-row">
        <label>Oppure carica un'immagine dal computer</label>
        <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp,.gif">
        <div class="hint">Se carichi un file, sostituisce l'URL indicato sopra. Su piattaforme con filesystem effimero (es. Render senza disco persistente aggiuntivo) i file caricati vengono persi ad ogni riavvio/redeploy: in quel caso preferisci sempre l'URL immagine.</div>
      </div>

      <div class="form-row">
        <label>Contenuto (separa i paragrafi con una riga vuota)</label>
        <div class="editor-toolbar">
          <button type="button" onclick="wrapSelection('**','**')" title="Grassetto"><b>B</b></button>
          <button type="button" onclick="wrapSelection('*','*')" title="Corsivo"><i>I</i></button>
          <button type="button" onclick="wrapSelection('++','++')" title="Sottolineato"><u>U</u></button>
          <button type="button" onclick="wrapSelection('~~','~~')" title="Barrato"><s>S</s></button>
          <button type="button" onclick="insertLink()" title="Inserisci link"><i class="fa-solid fa-link"></i></button>
          <select onchange="wrapSize(this.value); this.selectedIndex=0;" title="Dimensione testo">
            <option value="">Dimensione…</option>
            <option value="piccolo">Piccolo</option>
            <option value="normale">Normale</option>
            <option value="grande">Grande</option>
            <option value="enorme">Enorme</option>
          </select>
        </div>
        <textarea name="content" id="contentField" required><?= h($v['content']) ?></textarea>
        <div class="hint">Seleziona del testo e usa i pulsanti sopra, oppure scrivi a mano: <code>**grassetto**</code>, <code>*corsivo*</code>, <code>++sottolineato++</code>, <code>~~barrato~~</code>, <code>[testo](https://esempio.it)</code></div>
      </div>

      <script>
      function getContentField(){ return document.getElementById('contentField'); }

      function wrapSelection(before, after){
        const ta = getContentField();
        const start = ta.selectionStart, end = ta.selectionEnd;
        const selected = ta.value.substring(start, end) || 'testo';
        ta.setRangeText(before + selected + after, start, end, 'select');
        ta.focus();
      }

      function insertLink(){
        const ta = getContentField();
        const start = ta.selectionStart, end = ta.selectionEnd;
        const selected = ta.value.substring(start, end) || 'testo del link';
        const url = prompt("Indirizzo del link (deve iniziare con https://):", "https://");
        if (!url) return;
        ta.setRangeText('[' + selected + '](' + url + ')', start, end, 'select');
        ta.focus();
      }

      function wrapSize(size){
        if (!size) return;
        const ta = getContentField();
        const start = ta.selectionStart, end = ta.selectionEnd;
        const selected = ta.value.substring(start, end) || 'testo';
        ta.setRangeText('[size=' + size + ']' + selected + '[/size]', start, end, 'select');
        ta.focus();
      }
      </script>

      <div style="display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> <?= $isEdit ? 'Salva modifiche' : 'Pubblica articolo' ?></button>
        <a href="<?= url('admin/dashboard.php') ?>" class="btn btn-secondary">Annulla</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
