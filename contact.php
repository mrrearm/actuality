<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$pageTitle = 'Contatti — ' . get_setting($pdo, 'site_title', '');

require __DIR__ . '/partials/header.php';
?>

<div class="article-page">
  <a class="article-back" href="<?= url('index.php') ?>"><i class="fa-solid fa-arrow-left"></i> Torna alla home</a>

  <h1>Contattami</h1>
  <p style="color:var(--ink-soft); line-height:1.7;">Hai una domanda, un suggerimento o vuoi semplicemente scrivermi? Compila il modulo qui sotto, ti risponderò via email il prima possibile.</p>

  <div class="comment-form-box" style="margin-top:26px;">
    <?php if (isset($_GET['sent'])): ?>
      <div class="comment-flash ok">Messaggio inviato, grazie! Ti risponderò appena possibile.</div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="comment-flash error">Controlla i dati inseriti: nome, email e messaggio sono obbligatori.</div>
    <?php endif; ?>

    <form method="post" action="<?= url('contact-submit.php') ?>">
      <?= csrf_field() ?>
      <!-- campo trappola per bot: invisibile agli utenti reali -->
      <div class="hp-field" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

      <div class="comment-form-row">
        <input type="text" name="name" placeholder="Il tuo nome" maxlength="100" required>
        <input type="email" name="email" placeholder="La tua email" required>
      </div>
      <textarea name="message" placeholder="Scrivi qui il tuo messaggio..." maxlength="3000" required style="min-height:160px;"></textarea>
      <button type="submit" class="btn-comment-submit">Invia messaggio</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
