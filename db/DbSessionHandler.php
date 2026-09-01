<?php
/**
 * db/DbSessionHandler.php
 *
 * Salva le sessioni PHP nel database (Turso o MySQL) invece che come file
 * sul disco locale. Necessario su Render (piano free): il container va in
 * sospensione dopo un periodo di inattività e riparte con un filesystem
 * vuoto, perdendo tutte le sessioni salvate su disco — chi in quel momento
 * stava compilando un form (es. il login) si ritrova con un token CSRF che
 * non corrisponde più a nessuna sessione ("CSRF token mancante o scaduto").
 * Il database invece sopravvive ai riavvii, quindi il problema sparisce.
 *
 * Compatibile sia con PDO (MySQL) sia con TursoPDO, che espone la stessa
 * interfaccia (prepare/execute/fetch).
 */
class DbSessionHandler implements SessionHandlerInterface {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureTable();
    }

    private function ensureTable(): void {
        // Sintassi compatibile sia con MySQL sia con SQLite/Turso
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(191) PRIMARY KEY,
            data TEXT,
            last_activity INT
        )');
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        $stmt = $this->pdo->prepare('SELECT data FROM sessions WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? (string)$row['data'] : '';
    }

    public function write($id, $data): bool {
        $now = time();
        $exists = $this->pdo->prepare('SELECT 1 FROM sessions WHERE id = ?');
        $exists->execute([$id]);
        if ($exists->fetch()) {
            $stmt = $this->pdo->prepare('UPDATE sessions SET data = ?, last_activity = ? WHERE id = ?');
            return $stmt->execute([$data, $now, $id]);
        }
        $stmt = $this->pdo->prepare('INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?)');
        return $stmt->execute([$id, $data, $now]);
    }

    public function destroy($id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function gc($max_lifetime): int {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE last_activity < ?');
        $stmt->execute([time() - $max_lifetime]);
        return $stmt->rowCount();
    }
}
