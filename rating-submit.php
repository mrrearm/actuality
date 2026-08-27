<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('index.php'));
    exit;
}
csrf_check();

$articleId = (int)($_POST['article_id'] ?? 0);
$rating    = (int)($_POST['rating'] ?? 0);

$article = $articleId ? get_article($pdo, $articleId) : null;
$redirect = url('article.php?id=' . $articleId);

if (!$article || $rating < 1 || $rating > 5) {
    header('Location: ' . $redirect . '#voto');
    exit;
}

// Limite "un voto per browser" tramite cookie: non a prova di bomba (si aggira
// cancellando i cookie), ma sufficiente per un blog personale senza account utente.
$votedKey = 'voted_articles';
$voted = isset($_COOKIE[$votedKey]) ? explode(',', $_COOKIE[$votedKey]) : [];

if (!in_array((string)$articleId, $voted, true)) {
    $stmt = $pdo->prepare('INSERT INTO ratings (article_id, rating) VALUES (?, ?)');
    $stmt->execute([$articleId, $rating]);

    $voted[] = (string)$articleId;
    setcookie($votedKey, implode(',', $voted), time() + 60 * 60 * 24 * 365, '/');
}

header('Location: ' . $redirect . '#voto');
exit;
