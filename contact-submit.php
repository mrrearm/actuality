<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('contact.php'));
    exit;
}

csrf_check();

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$message  = trim($_POST['message'] ?? '');
$honeypot = trim($_POST['website'] ?? ''); // campo nascosto: se compilato, è un bot

// Bot rilevato: fingiamo successo senza salvare nulla, per non dargli indizi
if ($honeypot !== '') {
    header('Location: ' . url('contact.php?sent=1'));
    exit;
}

if ($name === '' || mb_strlen($name) > 100 || $message === '' || mb_strlen($message) > 3000 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . url('contact.php?error=1'));
    exit;
}

// Salviamo SEMPRE il messaggio nel database, indipendentemente dall'esito
// dell'invio email: così nessun messaggio va perso anche se la mail fallisce
try {
    $pdo->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?,?,?)')
        ->execute([$name, $email, $message]);
} catch (Throwable $e) {
    error_log('Impossibile salvare il messaggio di contatto: ' . $e->getMessage());
}

// Notifica via email, best-effort: se fallisce il messaggio resta comunque
// salvato e consultabile dalla dashboard
try {
    $siteTitle = get_setting($pdo, 'site_title', 'Scopri. Racconta. Sogna.');
    $html = '<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;">'
          . '<h2 style="color:#1f8a94;">Nuovo messaggio dal modulo contatti</h2>'
          . '<p><strong>Nome:</strong> ' . h($name) . '</p>'
          . '<p><strong>Email:</strong> ' . h($email) . '</p>'
          . '<p style="white-space:pre-wrap; border-left:3px solid #1f8a94; padding-left:12px; color:#333;">' . h($message) . '</p>'
          . '<p style="color:#aaa; font-size:12px; margin-top:24px;">Ricevuto tramite il modulo contatti di ' . h($siteTitle) . '.</p>'
          . '</div>';
    send_email('info@mrrearm.it', 'Ray D', 'Nuovo messaggio da ' . $name . ' — ' . $siteTitle, $html, $email);
} catch (Throwable $e) {
    error_log('Impossibile inviare la notifica del messaggio di contatto: ' . $e->getMessage());
}

header('Location: ' . url('contact.php?sent=1'));
exit;
