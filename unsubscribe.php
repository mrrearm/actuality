<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$email = trim($_GET['email'] ?? '');
$token = trim($_GET['token'] ?? '');

$pageTitle = 'Disiscrizione — ' . get_setting($pdo, 'site_title', '');

$valid = $email !== '' && $token !== '' && hash_equals(unsubscribe_token($pdo, $email), $token);

if ($valid) {
    $pdo->prepare('DELETE FROM subscribers WHERE email = ?')->execute([$email]);
}

require __DIR__ . '/partials/header.php';
?>

<div class="article-page">
  <?php if ($valid): ?>
    <h1>Disiscrizione completata</h1>
    <p>L'indirizzo <strong><?= h($email) ?></strong> è stato rimosso dalla newsletter. Non riceverai più nessuna email da questo sito.</p>
    <p>Se hai cambiato idea, puoi iscriverti di nuovo in qualsiasi momento dalla <a href="<?= url('index.php') ?>">home</a>.</p>
  <?php else: ?>
    <h1>Link non valido</h1>
    <p>Questo link di disiscrizione non è valido o è scaduto. Se vuoi cancellarti dalla newsletter, scrivi a <a href="mailto:info@mrrearm.it">info@mrrearm.it</a>.</p>
  <?php endif; ?>

  <p><a class="article-back" href="<?= url('index.php') ?>"><i class="fa-solid fa-arrow-left"></i> Torna alla home</a></p>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
