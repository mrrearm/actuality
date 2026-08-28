<?php
/** functions.php — helper per query e formattazione */

function get_categories($pdo): array {
    static $cache = null;
    if ($cache === null) {
        $cache = $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC')->fetchAll();
    }
    return $cache;
}

function get_category_by_slug($pdo, string $slug): ?array {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_articles($pdo, ?string $catSlug = null, bool $onlyPublished = true, ?int $limit = null, ?int $offset = null): array {
    $sql = 'SELECT a.*, c.slug AS cat_slug, c.name AS cat_name, c.color_hex, c.icon_class
            FROM articles a JOIN categories c ON a.category_id = c.id';
    $where = [];
    $params = [];
    if ($onlyPublished) { $where[] = "a.status = 'published'"; }
    if ($catSlug) {
        // Filtra su QUALSIASI categoria associata all'articolo, non solo quella principale
        $where[] = 'a.id IN (SELECT ac.article_id FROM article_categories ac JOIN categories c2 ON ac.category_id = c2.id WHERE c2.slug = ?)';
        $params[] = $catSlug;
    }
    if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY a.published_at DESC, a.id DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int)$limit;
        if ($offset !== null) {
            $sql .= ' OFFSET ' . (int)$offset;
        }
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Conta gli articoli totali (per calcolare quante pagine servono) */
function count_articles($pdo, ?string $catSlug = null, bool $onlyPublished = true): int {
    $sql = 'SELECT COUNT(*) FROM articles a';
    $where = [];
    $params = [];
    if ($onlyPublished) { $where[] = "a.status = 'published'"; }
    if ($catSlug) {
        $where[] = 'a.id IN (SELECT ac.article_id FROM article_categories ac JOIN categories c2 ON ac.category_id = c2.id WHERE c2.slug = ?)';
        $params[] = $catSlug;
    }
    if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/** Tutte le categorie associate a un gruppo di articoli, in UNA sola query (evita N query separate) */
function get_categories_for_articles($pdo, array $articleIds): array {
    if (!$articleIds) { return []; }
    $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
    $stmt = $pdo->prepare("SELECT ac.article_id, c.id, c.slug, c.name, c.color_hex, c.icon_class
                            FROM article_categories ac
                            JOIN categories c ON ac.category_id = c.id
                            WHERE ac.article_id IN ($placeholders)
                            ORDER BY c.sort_order ASC");
    $stmt->execute($articleIds);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int)$row['article_id']][] = $row;
    }
    return $map;
}

function get_article_categories($pdo, int $articleId): array {
    $map = get_categories_for_articles($pdo, [$articleId]);
    return $map[$articleId] ?? [];
}

/** Sostituisce l'intero insieme di categorie di un articolo con quello passato */
function sync_article_categories($pdo, int $articleId, array $categoryIds): void {
    $pdo->prepare('DELETE FROM article_categories WHERE article_id = ?')->execute([$articleId]);
    $stmt = $pdo->prepare('INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)');
    foreach (array_unique($categoryIds) as $catId) {
        if ($catId > 0) {
            $stmt->execute([$articleId, $catId]);
        }
    }
}

/** Statistiche voti (media + conteggio) per un gruppo di articoli, in UNA sola query */
function get_ratings_for_articles($pdo, array $articleIds): array {
    if (!$articleIds) { return []; }
    $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
    $stmt = $pdo->prepare("SELECT article_id, COUNT(*) AS cnt, AVG(rating) AS avg
                            FROM ratings WHERE article_id IN ($placeholders) GROUP BY article_id");
    $stmt->execute($articleIds);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int)$row['article_id']] = [
            'count'   => (int)$row['cnt'],
            'average' => round((float)$row['avg'], 1),
        ];
    }
    return $map;
}

function get_rating_stats($pdo, int $articleId): array {
    $map = get_ratings_for_articles($pdo, [$articleId]);
    return $map[$articleId] ?? ['count' => 0, 'average' => 0.0];
}

/** Stelle in sola visualizzazione (non interattive), es. per le card della griglia */
function render_stars_display(float $average, int $max = 5): string {
    $html = '<span class="stars-display" aria-label="valutazione ' . $average . ' su ' . $max . '">';
    for ($i = 1; $i <= $max; $i++) {
        $html .= ($i <= round($average)) ? '★' : '☆';
    }
    $html .= '</span>';
    return $html;
}

function get_comments($pdo, int $articleId, bool $onlyApproved = true): array {
    $sql = 'SELECT * FROM comments WHERE article_id = ?';
    $params = [$articleId];
    if ($onlyApproved) { $sql .= " AND status = 'approved'"; }
    $sql .= ' ORDER BY created_at ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function count_comments_by_status($pdo, string $status): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE status = ?");
    $stmt->execute([$status]);
    return (int)$stmt->fetchColumn();
}

function get_article($pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT a.*, c.slug AS cat_slug, c.name AS cat_name, c.color_hex, c.icon_class
                            FROM articles a JOIN categories c ON a.category_id = c.id
                            WHERE a.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_article_by_slug($pdo, string $slug): ?array {
    $stmt = $pdo->prepare('SELECT a.*, c.slug AS cat_slug, c.name AS cat_name, c.color_hex, c.icon_class
                            FROM articles a JOIN categories c ON a.category_id = c.id
                            WHERE a.slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_setting($pdo, string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach ($pdo->query('SELECT setting_key, setting_value FROM settings') as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function get_all_settings($pdo): array {
    $out = [];
    foreach ($pdo->query('SELECT setting_key, setting_value FROM settings') as $row) {
        $out[$row['setting_key']] = $row['setting_value'];
    }
    return $out;
}

/** Converte "url|etichetta|icona-fa" in array associativo, usato per i link progetti nel footer */
function parse_project_link(string $raw): array {
    $parts = explode('|', $raw, 3);
    return [
        'url'   => $parts[0] ?? '#',
        'label' => $parts[1] ?? '',
        'icon'  => $parts[2] ?? 'fa-solid fa-link',
    ];
}

/** Trasforma il testo con paragrafi separati da riga vuota in HTML sicuro */
/** Trasforma il testo con paragrafi separati da riga vuota in HTML sicuro.
 *  Sintassi supportate:
 *    **grassetto**        -> <strong>
 *    *corsivo*             -> <em>
 *    ++sottolineato++      -> <u>
 *    ~~barrato~~           -> <del>
 *    [size=grande]testo[/size]  -> dimensione (piccolo|normale|grande|enorme)
 *    [testo del link](https://...) -> link cliccabile
 */
function render_content(string $text): string {
    $paragraphs = preg_split('/\r?\n\r?\n/', trim($text));
    $html = '';

    $sizeMap = [
        'piccolo' => '0.85em',
        'normale' => '1em',
        'grande'  => '1.3em',
        'enorme'  => '1.6em',
    ];

    foreach ($paragraphs as $p) {
        $p = trim($p);
        if ($p === '') { continue; }
        $safe = nl2br(h($p));

        // Grassetto (va prima del corsivo, altrimenti ** verrebbe letto come due *)
        $safe = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $safe);
        // Corsivo
        $safe = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $safe);
        // Sottolineato
        $safe = preg_replace('/\+\+(.+?)\+\+/s', '<u>$1</u>', $safe);
        // Barrato
        $safe = preg_replace('/~~(.+?)~~/s', '<del>$1</del>', $safe);
        // Dimensione testo (solo valori dalla lista sopra, nessun CSS libero dell'utente)
        $safe = preg_replace_callback('/\[size=(piccolo|normale|grande|enorme)\](.+?)\[\/size\]/s',
            function ($m) use ($sizeMap) {
                return '<span style="font-size:' . $sizeMap[$m[1]] . '">' . $m[2] . '</span>';
            }, $safe);
        // Link (tollerante a uno spazio tra ] e ()
        $safe = preg_replace(
            '/\[([^\]]+)\]\s*\((https?:\/\/[^\s)]+)\)/',
            '<a href="$2" target="_blank" rel="noopener">$1</a>',
            $safe
        );

        $html .= '<p>' . $safe . '</p>';
    }
    return $html;
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('~[^-a-z0-9]+~', '', $text);
    return $text ?: 'articolo';
}

/** Slug leggibile in stile Wikipedia: mantiene maiuscole, parentesi e accenti,
 *  sostituisce solo gli spazi con underscore. Usato per gli URL degli articoli
 *  (es. "Disclosure Day (2026) - Non siamo soli" -> "Disclosure_Day_(2026)_-_Non_siamo_soli") */
function title_to_url_slug(string $title): string {
    $slug = trim($title);
    // rimuove i caratteri che avrebbero un significato speciale in una URL
    $slug = str_replace(['/', '\\', '?', '#', '%', '&', '=', '+', '"', "'", '<', '>'], '', $slug);
    // normalizza spazi multipli/tab in un unico underscore
    $slug = preg_replace('/\s+/u', '_', $slug);
    $slug = trim($slug, '_');
    return $slug !== '' ? $slug : 'articolo';
}

/** URL pubblico di un articolo: usa lo slug leggibile se presente, altrimenti ripiega su ?id= */
function article_url(array $article): string {
    $slug = $article['slug'] ?? '';
    return $slug !== '' ? url($slug) : url('article.php?id=' . (int)$article['id']);
}

function is_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

/** Maiuscolo sicuro per lettere accentate, con fallback se mbstring non è disponibile */
function upper(string $text): string {
    return function_exists('mb_strtoupper') ? mb_strtoupper($text, 'UTF-8') : strtoupper($text);
}

/** Insert-or-update di una impostazione, con sintassi corretta per il driver attivo */
function upsert_setting($pdo, string $key, string $value): void {
    if (DB_DRIVER === 'turso') {
        $sql = 'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value';
    } else {
        $sql = 'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)';
    }
    $pdo->prepare($sql)->execute([$key, $value]);
}
