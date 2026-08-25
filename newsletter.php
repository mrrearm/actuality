<?php
require __DIR__ . '/config.php';

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
} catch (PDOException $e) {
    // email già iscritta (UNIQUE) o altro errore: non blocchiamo l'utente
    header('Location: ' . url('index.php?sub_error=1#grid'));
    exit;
}

header('Location: ' . url('index.php?subscribed=1#grid'));
exit;
