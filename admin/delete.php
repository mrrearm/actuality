<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
require __DIR__ . '/includes/auth-check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM articles WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: ' . url('admin/dashboard.php?flash=deleted'));
exit;
