<?php
/** admin/includes/auth-check.php — include DOPO config.php in ogni pagina protetta */
if (!is_logged_in()) {
    header('Location: ' . url('admin/login.php'));
    exit;
}
