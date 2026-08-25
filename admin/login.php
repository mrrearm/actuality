<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';

if (is_logged_in()) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        header('Location: ' . url('admin/dashboard.php'));
        exit;
    }
    $error = 'Username o password errati.';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accesso — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= url('admin/includes/admin.css') ?>">
</head>
<body>
  <div class="login-box">
    <h1>Accedi alla dashboard</h1>
    <p>Scopri. Racconta. Sogna. — pannello di amministrazione</p>

    <?php if ($error): ?>
      <div class="flash flash-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <div class="form-row">
        <label>Username</label>
        <input type="text" name="username" required autofocus>
      </div>
      <div class="form-row">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Accedi</button>
    </form>
  </div>
</body>
</html>
