<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
require __DIR__ . '/includes/auth-check.php';

$activePage = 'settings';
$error = ''; $success = '';

$textFields = ['site_title','site_tagline','site_eyebrow','hero_image','footer_bio'];
$socialFields = ['social_sito','social_github','social_instagram','social_facebook','social_x','social_youtube','social_tiktok','social_telegram','social_kofi','social_linkedin'];
$projectFields = [
    'project_spiritualita' => 'Spiritualità & Curiosità',
    'project_tech'         => 'Blog Informatico',
    'project_canzoni'      => 'Testi delle canzoni',
    'project_cineblog'     => 'CineBlog',
    'project_laciurma'     => 'La Ciurma',
    'project_card'         => 'La mia Card',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'general') {
    csrf_check();
    foreach (array_merge($textFields, $socialFields) as $key) {
        upsert_setting($pdo, $key, trim($_POST[$key] ?? ''));
    }
    foreach ($projectFields as $key => $label) {
        $url = trim($_POST[$key . '_url'] ?? '');
        $icon = trim($_POST[$key . '_icon'] ?? 'fa-solid fa-link');
        upsert_setting($pdo, $key, $url !== '' ? ($url . '|' . $label . '|' . $icon) : '');
    }
    $success = 'Impostazioni salvate.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'password') {
    csrf_check();
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password_hash'])) {
        $error = 'Password attuale non corretta.';
    } elseif (strlen($new) < 8) {
        $error = 'La nuova password deve avere almeno 8 caratteri.';
    } elseif ($new !== $confirm) {
        $error = 'Le due password non coincidono.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);
        $success = 'Password aggiornata correttamente.';
    }
}

$settings = get_all_settings($pdo);
function sv($settings, $key) { return h($settings[$key] ?? ''); }
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Impostazioni — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= url('admin/includes/admin.css') ?>">
</head>
<body>
<?php require __DIR__ . '/includes/topbar.php'; ?>

<div class="admin-wrap">

  <?php if ($error): ?><div class="flash flash-error"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="flash flash-ok"><?= h($success) ?></div><?php endif; ?>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="general">

    <div class="admin-card">
      <h2>Sito</h2>
      <div class="form-row"><label>Titolo del sito</label><input type="text" name="site_title" value="<?= sv($settings,'site_title') ?>"></div>
      <div class="form-row"><label>Tagline</label><input type="text" name="site_tagline" value="<?= sv($settings,'site_tagline') ?>"></div>
      <div class="form-row"><label>Testo sopra il titolo (eyebrow)</label><input type="text" name="site_eyebrow" value="<?= sv($settings,'site_eyebrow') ?>"></div>
      <div class="form-row"><label>Immagine hero (URL)</label><input type="url" name="hero_image" value="<?= sv($settings,'hero_image') ?>"></div>
      <div class="form-row"><label>Bio nel footer</label><textarea name="footer_bio" style="min-height:90px;"><?= sv($settings,'footer_bio') ?></textarea></div>
    </div>

    <div class="admin-card">
      <h2>Social (footer)</h2>
      <div class="form-grid">
        <div class="form-row"><label>Sito web</label><input type="url" name="social_sito" value="<?= sv($settings,'social_sito') ?>"></div>
        <div class="form-row"><label>GitHub</label><input type="url" name="social_github" value="<?= sv($settings,'social_github') ?>"></div>
        <div class="form-row"><label>Instagram</label><input type="url" name="social_instagram" value="<?= sv($settings,'social_instagram') ?>"></div>
        <div class="form-row"><label>Facebook</label><input type="url" name="social_facebook" value="<?= sv($settings,'social_facebook') ?>"></div>
        <div class="form-row"><label>X (Twitter)</label><input type="url" name="social_x" value="<?= sv($settings,'social_x') ?>"></div>
        <div class="form-row"><label>YouTube</label><input type="url" name="social_youtube" value="<?= sv($settings,'social_youtube') ?>"></div>
        <div class="form-row"><label>TikTok</label><input type="url" name="social_tiktok" value="<?= sv($settings,'social_tiktok') ?>"></div>
        <div class="form-row"><label>Telegram</label><input type="url" name="social_telegram" value="<?= sv($settings,'social_telegram') ?>"></div>
        <div class="form-row"><label>Ko-fi</label><input type="url" name="social_kofi" value="<?= sv($settings,'social_kofi') ?>"></div>
        <div class="form-row"><label>LinkedIn</label><input type="url" name="social_linkedin" value="<?= sv($settings,'social_linkedin') ?>"></div>
      </div>
    </div>

    <div class="admin-card">
      <h2>I miei progetti (riga footer)</h2>
      <?php foreach ($projectFields as $key => $label):
        $raw = $settings[$key] ?? '';
        $p = parse_project_link($raw); ?>
        <div class="form-grid" style="margin-bottom:8px;">
          <div class="form-row"><label><?= h($label) ?> — URL</label><input type="url" name="<?= $key ?>_url" value="<?= h($p['url'] === '#' ? '' : $p['url']) ?>"></div>
          <div class="form-row"><label>Icona Font Awesome</label><input type="text" name="<?= $key ?>_icon" value="<?= h($p['icon']) ?>"></div>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Salva impostazioni</button>
  </form>

  <div class="admin-card" style="margin-top:24px;">
    <h2>Cambia password</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="form" value="password">
      <div class="form-row"><label>Password attuale</label><input type="password" name="current_password" required></div>
      <div class="form-row"><label>Nuova password (min. 8 caratteri)</label><input type="password" name="new_password" required></div>
      <div class="form-row"><label>Conferma nuova password</label><input type="password" name="confirm_password" required></div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Aggiorna password</button>
    </form>
  </div>

</div>
</body>
</html>
