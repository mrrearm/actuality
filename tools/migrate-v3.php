<?php
/**
 * tools/migrate-v3.php
 *
 * Aggiunge la tabella "contact_messages" (modulo contatti) al database
 * GIÀ ESISTENTE su Turso, senza toccare nient'altro. Sicuro da rilanciare
 * più volte.
 *
 * USO:
 *   php migrate-v3.php "https://il-tuo-db-tuaorg.turso.io" "il-tuo-token"
 */

require __DIR__ . '/../db/TursoPdo.php';

if ($argc < 3) {
    fwrite(STDERR, "Uso: php migrate-v3.php <URL_TURSO> <TOKEN_TURSO>\n");
    exit(1);
}

$url   = $argv[1];
$token = $argv[2];

$statements = [
    "CREATE TABLE IF NOT EXISTS contact_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        message TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )",
];

$pdo = new TursoPDO($url, $token);
$ok = 0; $skipped = 0;

foreach ($statements as $i => $stmt) {
    $preview = substr(preg_replace('/\s+/', ' ', trim($stmt)), 0, 65);
    try {
        $pdo->prepare($stmt)->execute([]);
        echo "[" . ($i + 1) . "/" . count($statements) . "] OK   → $preview...\n";
        $ok++;
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'already exists') !== false) {
            echo "[" . ($i + 1) . "/" . count($statements) . "] SKIP (già presente) → $preview...\n";
            $skipped++;
        } else {
            echo "[" . ($i + 1) . "/" . count($statements) . "] ERRORE → $preview...\n   " . $msg . "\n";
        }
    }
}

echo "\n=== Fatto: $ok eseguite, $skipped già presenti, su " . count($statements) . " totali ===\n";
echo "Il modulo contatti ora salva i messaggi anche sul database.\n";
