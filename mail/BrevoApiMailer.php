<?php
/**
 * mail/BrevoApiMailer.php
 *
 * Invia email tramite l'API HTTP di Brevo (porta 443) invece che via SMTP
 * (porta 587/465). Necessario su Render: dal 2025 i servizi con piano
 * gratuito bloccano tutte le porte SMTP in uscita per prevenire spam — le
 * richieste HTTPS verso api.brevo.com non sono invece soggette a questa
 * restrizione, essendo lo stesso tipo di traffico di una normale pagina web.
 */
class BrevoApiMailer
{
    public function __construct(
        protected string $apiKey,
        private string $fromEmail,
        private string $fromName
    ) {}

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        $payload = $this->buildPayload($toEmail, $toName, $subject, $htmlBody);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'api-key: ' . $this->apiKey,
                'content-type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT    => 15,
        ]);
        $response = curl_exec($ch);

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Errore di connessione a Brevo: ' . $err);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("Errore Brevo (HTTP $status): $response");
        }
    }

    /** Pubblico apposta: permette di testare il payload senza fare una vera chiamata HTTP */
    public function buildPayload(string $toEmail, string $toName, string $subject, string $htmlBody): array
    {
        return [
            'sender'      => ['name' => $this->fromName, 'email' => $this->fromEmail],
            'to'          => [['email' => $toEmail, 'name' => $toName]],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
        ];
    }
}
