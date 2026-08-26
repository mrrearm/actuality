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

function get_articles($pdo, ?string $catSlug = null, bool $onlyPublished = true): array {
    $sql = 'SELECT a.*, c.slug AS cat_slug, c.name AS cat_name, c.color_hex, c.icon_class
            FROM articles a JOIN categories c ON a.category_id = c.id';
    $where = [];
    $params = [];
    if ($onlyPublished) { $where[] = "a.status = 'published'"; }
    if ($catSlug) { $where[] = 'c.slug = ?'; $params[] = $catSlug; }
    if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY a.published_at DESC, a.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_article($pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT a.*, c.slug AS cat_slug, c.name AS cat_name, c.color_hex, c.icon_class
                            FROM articles a JOIN categories c ON a.category_id = c.id
                            WHERE a.id = ?');
    $stmt->execute([$id]);
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
function render_content(string $text): string {
    $paragraphs = preg_split('/\r?\n\r?\n/', trim($text));
    $html = '';
    foreach ($paragraphs as $p) {
        $p = trim($p);
        if ($p === '') { continue; }
        $safe = nl2br(h($p));
        $safe = preg_replace(
            '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/',
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
