<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('index.php'));
    exit;
}

csrf_check();

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . url('index.php?sub_error=1#grid'));
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO subscribers (email) VALUES (?)');
    $stmt->execute([$email]);
} catch (Throwable $e) {
    // email già iscritta (UNIQUE) o altro errore: non blocchiamo l'utente
    header('Location: ' . url('index.php?sub_error=1#grid'));
    exit;
}

// Mail di conferma: se fallisce (SMTP non configurato, provider irraggiungibile,
// ecc.) l'iscrizione resta comunque valida, non blocchiamo l'utente per questo
$siteTitle = get_setting($pdo, 'site_title', 'Scopri. Racconta. Sogna.');
$html = '<div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto;">'
      . '<h2 style="color:#1f8a94;">Iscrizione confermata!</h2>'
      . '<p style="color:#5b6472;line-height:1.6;">Grazie per esserti iscritto alla newsletter di <strong>' . h($siteTitle) . '</strong>. Da ora riceverai una email ogni volta che pubblico un nuovo articolo.</p>'
      . '<p style="color:#aaa;font-size:12px;margin-top:30px;">Se non ti sei iscritto tu, o vuoi cancellarti, scrivimi a info@mrrearm.it.</p>'
      . '</div>';
send_email($email, $email, 'Iscrizione confermata — ' . $siteTitle, $html);

header('Location: ' . url('index.php?subscribed=1#grid'));
exit;
