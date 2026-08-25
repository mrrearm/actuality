<?php
/**
 * tools/import-schema.php
 *
 * Carica sql/schema-turso.sql nel database Turso usando l'API HTTP,
 * senza bisogno della CLI di Turso (utile su Termux, dove il binario
 * della CLI non è compatibile).
 *
 * USO:
 *   php import-schema.php "https://il-tuo-db-tuaorg.turso.io" "il-tuo-token"
 *
 * Esegui questo script UNA SOLA VOLTA sul database appena creato.
 * Se lo rilanci su un DB già popolato otterrai errori di "UNIQUE constraint"
 * sulle righe già inserite (categorie, articoli, ecc.) — è normale e innocuo,
 * significa solo che quei dati ci sono già.
 */

require __DIR__ . '/../db/TursoPdo.php';

if ($argc < 3) {
    fwrite(STDERR, "Uso: php import-schema.php <URL_TURSO> <TOKEN_TURSO>\n");
    exit(1);
}

$url   = $argv[1];
$token = $argv[2];
$schemaFile = __DIR__ . '/../sql/schema-turso.sql';

if (!file_exists($schemaFile)) {
    fwrite(STDERR, "File non trovato: $schemaFile\n");
    fwrite(STDERR, "Esegui questo script dalla cartella tools/ dentro il progetto.\n");
    exit(1);
}

$sql = file_get_contents($schemaFile);

// Rimuove i commenti a riga singola (-- ...) mantenendo il resto
$sql = preg_replace('/--.*$/m', '', $sql);

// I blocchi CREATE TRIGGER ... BEGIN ... END; contengono punti e virgola AL
// LORO INTERNO: vanno estratti PRIMA come istruzioni atomiche, altrimenti la
// divisione sul ";" qui sotto li spezzerebbe a metà.
$triggerBlocks = [];
$sql = preg_replace_callback('/CREATE TRIGGER.*?END\s*;/is', function ($m) use (&$triggerBlocks) {
    $key = '@@TRIGGER_BLOCK_' . count($triggerBlocks) . '@@';
    $triggerBlocks[$key] = rtrim(trim($m[0]), ';');
    return $key . ';';
}, $sql);

// Divide il resto in singole istruzioni sul punto e virgola di fine statement
$statements = array_filter(
    array_map('trim', explode(";", $sql)),
    fn($s) => $s !== ''
);

// Rimette al loro posto i blocchi trigger estratti sopra
$statements = array_map(
    fn($s) => $triggerBlocks[$s] ?? $s,
    $statements
);

echo "Trovate " . count($statements) . " istruzioni SQL da eseguire.\n\n";

$pdo = new TursoPDO($url, $token);

$ok = 0;
$skipped = 0;

foreach ($statements as $i => $stmt) {
    $preview = substr(preg_replace('/\s+/', ' ', $stmt), 0, 60);
    try {
        $pdo->prepare($stmt)->execute([]);
        echo "[" . ($i + 1) . "/" . count($statements) . "] OK  → $preview...\n";
        $ok++;
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'UNIQUE') !== false || stripos($msg, 'already exists') !== false) {
            echo "[" . ($i + 1) . "/" . count($statements) . "] SKIP (già presente) → $preview...\n";
            $skipped++;
        } else {
            echo "[" . ($i + 1) . "/" . count($statements) . "] ERRORE → $preview...\n";
            echo "   " . $msg . "\n";
        }
    }
}

echo "\n=== Fatto: $ok eseguite, $skipped già presenti, su " . count($statements) . " totali ===\n";
