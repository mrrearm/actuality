<?php
/**
 * mail/SmtpMailer.php
 *
 * Client SMTP scritto da zero, senza dipendenze esterne (niente Composer/
 * PHPMailer), per restare coerente con il resto del progetto — un blog
 * personale non ha bisogno di una libreria intera solo per mandare email.
 *
 * Supporta:
 * - connessione SSL implicita (tipicamente porta 465)
 * - connessione STARTTLS (tipicamente porta 587)
 * - autenticazione AUTH LOGIN (quella richiesta dalla maggior parte dei
 *   provider: Gmail, Brevo/Sendinblue, Mailgun, ecc.)
 */

class SmtpMailer
{
    public function __construct(
        private string $host,
        private int $port,
        private string $username,
        private string $password,
        private string $secure, // 'ssl' | 'tls' | ''
        private string $fromEmail,
        private string $fromName
    ) {}

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $replyTo = null): void
    {
        $transport = $this->secure === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client(
            $transport . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            15
        );
        if (!$socket) {
            throw new RuntimeException("Connessione SMTP fallita verso {$this->host}:{$this->port} — $errstr ($errno)");
        }

        $this->expect($socket, 220);
        $this->command($socket, 'EHLO ' . $this->heloDomain(), 250);

        if ($this->secure === 'tls') {
            $this->command($socket, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Attivazione TLS fallita durante STARTTLS');
            }
            $this->command($socket, 'EHLO ' . $this->heloDomain(), 250);
        }

        $this->command($socket, 'AUTH LOGIN', 334);
        $this->command($socket, base64_encode($this->username), 334);
        $this->command($socket, base64_encode($this->password), 235);

        $this->command($socket, 'MAIL FROM:<' . $this->fromEmail . '>', 250);
        $this->command($socket, 'RCPT TO:<' . $toEmail . '>', 250);
        $this->command($socket, 'DATA', 354);

        $headers = [
            'From: ' . $this->encodeHeader($this->fromName) . ' <' . $this->fromEmail . '>',
            'To: ' . $this->encodeHeader($toName) . ' <' . $toEmail . '>',
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'Date: ' . date('r'),
        ];
        if ($replyTo) {
            $headers[] = 'Reply-To: ' . $this->sanitizeHeaderValue($replyTo);
        }

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $this->stuffDots($htmlBody) . "\r\n.";
        $this->command($socket, $message, 250);

        $this->command($socket, 'QUIT', 221);
        fclose($socket);
    }

    /** Toglie ritorni a capo da un valore che finirà in un header: previene
     *  l'header injection (es. qualcuno che tenta di aggiungere un Bcc: finto) */
    private function sanitizeHeaderValue(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }

    private function heloDomain(): string
    {
        return preg_replace('/[^a-zA-Z0-9.\-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost') ?: 'localhost';
    }

    private function encodeHeader(string $text): string
    {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }

    /** Raddoppia i punti a inizio riga: lo richiede il protocollo SMTP (RFC 5321),
     *  altrimenti una riga che inizia per "." verrebbe scambiata per la fine del messaggio */
    private function stuffDots(string $body): string
    {
        return preg_replace('/^\./m', '..', $body);
    }

    private function command($socket, string $command, int $expectedCode): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->expect($socket, $expectedCode);
    }

    private function expect($socket, int $expectedCode): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Le righe di continuazione hanno un trattino dopo il codice (es. "250-"),
            // l'ultima riga della risposta ha uno spazio al suo posto (es. "250 ")
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int)substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new RuntimeException("Risposta SMTP inattesa: atteso $expectedCode, ricevuto: " . trim($response));
        }
        return $response;
    }
}
