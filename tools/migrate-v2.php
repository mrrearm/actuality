<?php
/**
 * tools/migrate-v2.php
 *
 * Aggiunge al database GIÀ ESISTENTE (quello online su Turso) le nuove
 * tabelle per: categorie multiple per articolo, commenti, voti a stelle.
 * Non tocca articoli, categorie, impostazioni o utenti già presenti.
 *
 * È SICURO rilanciarlo più volte: le tabelle già create e i dati già
 * migrati vengono saltati automaticamente (stesso comportamento di
 * tools/import-schema.php).
 *
 * USO:
 *   php migrate-v2.php "https://il-tuo-db-tuaorg.turso.io" "il-tuo-token"
 */

require __DIR__ . '/../db/TursoPdo.php';

if ($argc < 3) {
    fwrite(STDERR, "Uso: php migrate-v2.php <URL_TURSO> <TOKEN_TURSO>\n");
    exit(1);
}

$url   = $argv[1];
$token = $argv[2];

$statements = [
    "CREATE TABLE IF NOT EXISTS article_categories (
        article_id INTEGER NOT NULL REFERENCES articles(id) ON DELETE CASCADE,
        category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
        PRIMARY KEY (article_id, category_id)
    )",
    "CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        article_id INTEGER NOT NULL REFERENCES articles(id) ON DELETE CASCADE,
        author_name TEXT NOT NULL,
        author_email TEXT,
        body TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS ratings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        article_id INTEGER NOT NULL REFERENCES articles(id) ON DELETE CASCADE,
        rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )",
    "INSERT INTO article_categories (article_id, category_id) SELECT id, category_id FROM articles",
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
        if (stripos($msg, 'UNIQUE') !== false || stripos($msg, 'already exists') !== false) {
            echo "[" . ($i + 1) . "/" . count($statements) . "] SKIP (già presente) → $preview...\n";
            $skipped++;
        } else {
            echo "[" . ($i + 1) . "/" . count($statements) . "] ERRORE → $preview...\n   " . $msg . "\n";
        }
    }
}

echo "\n=== Fatto: $ok eseguite, $skipped già presenti, su " . count($statements) . " totali ===\n";
echo "Il tuo database ora supporta categorie multiple, commenti e voti a stelle.\n";
