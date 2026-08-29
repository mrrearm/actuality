<?php
/**
 * config.php
 * Configurazione database e sessione.
 *
 * Le impostazioni vengono lette PRIMA dalle variabili d'ambiente (utile su
 * Render, Docker, o qualsiasi hosting che le supporti) e, se non presenti,
 * dai valori di default scritti qui sotto (comodo per hosting condiviso
 * classico dove modifichi direttamente questo file).
 */

function env(string $key, string $default = ''): string {
    $value = getenv($key);
    return ($value !== false) ? $value : $default;
}

// ---- QUALE DATABASE USARE ----
// 'mysql' (consigliato su hosting condiviso classico)
// 'turso' (consigliato su Render/Docker: nessuna estensione PHP particolare richiesta)
define('DB_DRIVER', env('DB_DRIVER', 'mysql'));

// ---- MYSQL (usato se DB_DRIVER = 'mysql') ----
// Su hosting condiviso: modifica direttamente i valori di default qui sotto.
// Su Render/Docker: imposta le variabili d'ambiente corrispondenti nel pannello.
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'mrrearm_actuality'));
define('DB_USER', env('DB_USER', 'mrrearm_actuality'));
define('DB_PASS', env('DB_PASS', 'INSERISCI_LA_TUA_PASSWORD'));

// ---- TURSO (usato se DB_DRIVER = 'turso') ----
// Ottieni l'URL con: turso db show <nome-db> --http-url
// Ottieni il token con: turso db tokens create <nome-db>
define('TURSO_DB_URL', env('TURSO_DB_URL', 'https://il-tuo-db-tuaorg.turso.io'));
define('TURSO_AUTH_TOKEN', env('TURSO_AUTH_TOKEN', 'INSERISCI_IL_TUO_TOKEN'));

// ---- BASE PATH ----
// Hosting condiviso in una sottocartella (es. mrrearm.it/actuality/): '/actuality'
// Render/Docker, dove il progetto vive alla radice del proprio dominio: '' (vuoto)
define('BASE_PATH', env('BASE_PATH', '/actuality'));

// ---- EMAIL (newsletter: conferma iscrizione + notifica nuovi articoli) ----
// Disabilitato di default: finché non imposti queste variabili, il sito
// continua a funzionare normalmente ma non tenta di inviare nessuna email.
define('EMAIL_ENABLED', env('EMAIL_ENABLED', 'false') === 'true');
define('SMTP_HOST', env('SMTP_HOST', ''));
define('SMTP_PORT', (int)env('SMTP_PORT', '587'));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_SECURE', env('SMTP_SECURE', 'tls'));   // 'tls' (porta 587) oppure 'ssl' (porta 465)
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'no-reply@example.com'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'Scopri. Racconta. Sogna.'));

// ---- SESSIONE ----
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- CONNESSIONE ----
try {
    if (DB_DRIVER === 'turso') {
        require __DIR__ . '/db/TursoPdo.php';
        $pdo = new TursoPDO(TURSO_DB_URL, TURSO_AUTH_TOKEN);
    } else {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
} catch (Throwable $e) {
    http_response_code(500);
    die('Errore di connessione al database. Controlla config.php / variabili d\'ambiente. (' . htmlspecialchars($e->getMessage()) . ')');
}

// ---- CSRF TOKEN ----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}
function csrf_check(): void {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Richiesta non valida (CSRF token mancante o scaduto). Torna indietro e riprova.');
    }
}

// ---- HELPER ----
function h($str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
function url(string $path = ''): string {
    return BASE_PATH . '/' . ltrim($path, '/');
}
