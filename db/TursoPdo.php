<?php
/**
 * db/TursoPdo.php
 *
 * Driver alternativo a MySQL: parla con Turso via API HTTP (endpoint /v2/pipeline)
 * ma espone gli stessi metodi di PDO (prepare, query, exec, lastInsertId) e uno
 * statement con execute/fetch/fetchAll/fetchColumn, così tutto il resto del
 * progetto (functions.php, pagine admin) funziona SENZA MODIFICHE.
 *
 * Attenzione: ogni execute() è una chiamata HTTP separata, quindi ogni pagina
 * con più query sarà più lenta rispetto a MySQL locale (latenza di rete).
 * Nessuna ottimizzazione di batching è implementata qui: è la scelta
 * "via di mezzo" — stesso codice applicativo, driver intercambiabile.
 */

class TursoPDO {
    private string $baseUrl;
    private string $token;
    private string $lastInsertId = '0';

    public function __construct(string $baseUrl, string $token) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
    }

    public function prepare(string $sql): TursoStmt {
        return new TursoStmt($this, $sql);
    }

    /** Compatibile con $pdo->query($sql) usato per SELECT senza parametri */
    public function query(string $sql): TursoStmt {
        $stmt = new TursoStmt($this, $sql);
        $stmt->execute([]);
        return $stmt;
    }

    /** Compatibile con $pdo->exec($sql) per istruzioni senza risultati */
    public function exec(string $sql): int {
        $stmt = new TursoStmt($this, $sql);
        $stmt->execute([]);
        return $stmt->rowCount();
    }

    public function lastInsertId(): string {
        return $this->lastInsertId;
    }

    /** @internal chiamato da TursoStmt::execute() */
    public function runPipeline(array $requests): array {
        $ch = curl_init($this->baseUrl . '/v2/pipeline');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(['requests' => $requests]),
            CURLOPT_TIMEOUT    => 15,
        ]);
        $response = curl_exec($ch);

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Errore di connessione a Turso: ' . $err);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $data = json_decode($response, true);
        if ($status >= 400 || !isset($data['results'])) {
            throw new RuntimeException('Errore Turso (HTTP ' . $status . '): ' . $response);
        }
        return $data['results'];
    }

    /** @internal */
    public function setLastInsertId(string $id): void {
        $this->lastInsertId = $id;
    }
}

class TursoStmt implements IteratorAggregate {
    private TursoPDO $db;
    private string $sql;
    private array $rows = [];
    private int $cursor = 0;
    private int $affectedRows = 0;

    public function __construct(TursoPDO $db, string $sql) {
        $this->db = $db;
        $this->sql = $sql;
    }

    /** Solo parametri posizionali (?) — è lo stile usato in tutto il progetto */
    public function execute(array $params = []): bool {
        $args = array_map([$this, 'toTursoArg'], $params);

        $results = $this->db->runPipeline([
            ['type' => 'execute', 'stmt' => ['sql' => $this->sql, 'args' => $args]],
            ['type' => 'close'],
        ]);

        $first = $results[0] ?? null;
        if (!$first || $first['type'] !== 'ok') {
            $msg = $first['error']['message'] ?? 'errore sconosciuto';
            throw new RuntimeException('Errore query Turso: ' . $msg . ' — SQL: ' . $this->sql);
        }

        $result = $first['response']['result'];
        $colNames = array_map(fn($c) => $c['name'], $result['cols'] ?? []);

        $this->rows = array_map(function ($row) use ($colNames) {
            $assoc = [];
            foreach ($row as $i => $cell) {
                $assoc[$colNames[$i] ?? $i] = $this->fromTursoValue($cell);
            }
            return $assoc;
        }, $result['rows'] ?? []);

        $this->cursor = 0;
        $this->affectedRows = $result['affected_row_count'] ?? 0;

        if (!empty($result['last_insert_rowid'])) {
            $this->db->setLastInsertId((string)$result['last_insert_rowid']);
        }
        return true;
    }

    private function toTursoArg($value): array {
        if ($value === null)   { return ['type' => 'null']; }
        if (is_int($value))    { return ['type' => 'integer', 'value' => (string)$value]; }
        if (is_float($value))  { return ['type' => 'float', 'value' => (string)$value]; }
        return ['type' => 'text', 'value' => (string)$value];
    }

    private function fromTursoValue(array $cell) {
        return match ($cell['type'] ?? 'text') {
            'null'    => null,
            'integer' => (int)$cell['value'],
            'float'   => (float)$cell['value'],
            default   => $cell['value'] ?? null, // text / blob
        };
    }

    public function fetch() {
        if (!isset($this->rows[$this->cursor])) { return false; }
        return $this->rows[$this->cursor++];
    }

    public function fetchAll(): array {
        return $this->rows;
    }

    public function fetchColumn(int $col = 0) {
        $row = $this->rows[$this->cursor] ?? null;
        if ($row === null) { return false; }
        $values = array_values($row);
        return $values[$col] ?? false;
    }

    public function rowCount(): int {
        return $this->affectedRows ?: count($this->rows);
    }

    public function getIterator(): Iterator {
        return new ArrayIterator($this->rows);
    }
}
