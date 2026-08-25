<?php
require __DIR__ . '/../config.php';
$_SESSION = [];
session_destroy();
header('Location: ' . url('admin/login.php'));
exit;
