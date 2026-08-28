<?php
/**
 * tools/regenerate-slugs.php
 *
 * Rigenera lo slug (usato nell'URL pubblico) di TUTTI gli articoli già
 * esistenti nel database, passando dal vecchio formato
 * ("le-notizie-della-settimana") al nuovo formato leggibile in stile
 * Wikipedia ("Le_notizie_della_settimana").
 *
 * Da eseguire UNA VOLTA SOLA dopo aver aggiornato il codice con il supporto
 * alle URL leggibili. Gli articoli creati DA QUESTO MOMENTO IN POI generano
 * già lo slug corretto da soli (vedi admin/article-form.php) — questo script
 * serve solo a "mettere in pari" quelli già pubblicati prima dell'update.
 *
 * USO:
 *   php regenerate-slugs.php "https://il-tuo-db-tuaorg.turso.io" "il-tuo-token"
 */

require __DIR__ . '/../db/TursoPdo.php';

if ($argc < 3) {
    fwrite(STDERR, "Uso: php regenerate-slugs.php <URL_TURSO> <TOKEN_TURSO>\n");
    exit(1);
}

$url   = $argv[1];
$token = $argv[2];

/** Stessa logica di functions.php::title_to_url_slug(), duplicata qui per
 *  tenere questo script autonomo (eseguibile anche senza config.php) */
function title_to_url_slug(string $title): string {
    $slug = trim($title);
    $slug = str_replace(['/', '\\', '?', '#', '%', '&', '=', '+', '"', "'", '<', '>'], '', $slug);
    $slug = preg_replace('/\s+/u', '_', $slug);
    $slug = trim($slug, '_');
    return $slug !== '' ? $slug : 'articolo';
}

$pdo = new TursoPDO($url, $token);

$articles = $pdo->prepare('SELECT id, title, slug FROM articles ORDER BY id ASC');
$articles->execute([]);
$rows = $articles->fetchAll();

echo 'Trovati ' . count($rows) . " articoli.\n\n";

$usedSlugs = [];
$updateStmt = $pdo->prepare('UPDATE articles SET slug = ? WHERE id = ?');
$updated = 0;

foreach ($rows as $row) {
    $base = title_to_url_slug($row['title']);
    $slug = $base;
    $suffix = 1;
    while (in_array($slug, $usedSlugs, true)) {
        $slug = $base . '_' . (++$suffix);
    }
    $usedSlugs[] = $slug;

    if ($slug === $row['slug']) {
        echo "[#{$row['id']}] invariato   → {$slug}\n";
        continue;
    }

    $updateStmt->execute([$slug, $row['id']]);
    echo "[#{$row['id']}] aggiornato  → {$row['slug']}  ⇒  {$slug}\n";
    $updated++;
}

echo "\n=== Fatto: {$updated} slug aggiornati su " . count($rows) . " articoli totali ===\n";
echo "I vecchi link con ?id= continuano comunque a funzionare (non si rompe nulla).\n";
