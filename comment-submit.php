<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('index.php'));
    exit;
}
csrf_check();

$articleId = (int)($_POST['article_id'] ?? 0);
$name      = trim($_POST['author_name'] ?? '');
$email     = trim($_POST['author_email'] ?? '');
$body      = trim($_POST['body'] ?? '');
$honeypot  = trim($_POST['website'] ?? ''); // campo nascosto: se compilato, è un bot

$article = $articleId ? get_article($pdo, $articleId) : null;

if (!$article) {
    header('Location: ' . url('index.php'));
    exit;
}

$redirect = url('article.php?id=' . $articleId);

// Bot rilevato: fingiamo successo senza salvare nulla, per non dargli indizi
if ($honeypot !== '') {
    header('Location: ' . $redirect . '#commenti');
    exit;
}

if ($name === '' || mb_strlen($name) > 100 || $body === '' || mb_strlen($body) > 2000) {
    header('Location: ' . $redirect . '?comment_error=1#scrivi-commento');
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $redirect . '?comment_error=1#scrivi-commento');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO comments (article_id, author_name, author_email, body, status) VALUES (?,?,?,?,?)');
$stmt->execute([$articleId, $name, $email ?: null, $body, 'pending']);

header('Location: ' . $redirect . '?comment_sent=1#commenti');
exit;
