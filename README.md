# Scopri. Racconta. Sogna. — Progetto PHP con dashboard

Mini-CMS standalone (PHP + MySQL, senza WordPress) per gestire il blog da
`mrrearm.it/actuality/` in modo completamente indipendente dal resto del sito.

## Cosa contiene

- **Sito pubblico**: `index.php` (griglia articoli, filtro categorie funzionante),
  `article.php` (pagina singolo articolo), `newsletter.php` (iscrizioni).
- **Dashboard admin** (`/admin/`): login, elenco articoli con modifica/elimina,
  creazione nuovo articolo (con upload immagine), gestione categorie
  (colore/icona/ordine), impostazioni sito/social/footer, cambio password.
- **Database**: `sql/schema.sql` con tabelle + i 16 articoli iniziali già pronti.

## Requisiti hosting

PHP 8.0+ con estensione PDO MySQL, database MySQL/MariaDB. Va bene qualunque
hosting condiviso con cPanel (compatibile con la maggior parte dei provider
italiani), oppure il tuo NAS CasaOS con un container PHP+MySQL se preferisci
self-hostarlo.

## Passi per l'installazione su mrrearm.it/actuality

1. **Crea il database** da cPanel → MySQL Databases: crea un DB (es.
   `mrrearm_actuality`) e un utente con tutti i privilegi su quel DB.

2. **Importa lo schema**: apri phpMyAdmin sul nuovo database → scheda "Importa"
   → seleziona `sql/schema.sql` → Esegui. Questo crea le tabelle, le 4
   categorie, i 16 articoli e un utente admin di default.

3. **Carica i file**: via FTP/File Manager, carica tutta la cartella
   `actuality/` dentro `public_html/actuality/` (o dove preferisci — basta che
   il nome cartella coincida con `BASE_PATH` in `config.php`).

4. **Configura la connessione**: apri `config.php` e inserisci le credenziali
   del database create al passo 1:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'mrrearm_actuality');
   define('DB_USER', 'il-tuo-utente');
   define('DB_PASS', 'la-tua-password');
   ```

5. **Primo accesso alla dashboard**: vai su `mrrearm.it/actuality/admin/login.php`
   - Username: `admin`
   - Password: `cambia-subito-2026`

   **Cambia subito la password** da Impostazioni → "Cambia password" appena
   entri: quella di default è nota chiunque legga questo file.

6. **Permessi cartella upload**: assicurati che `assets/uploads/` sia
   scrivibile dal server (chmod 755 o 775 a seconda dell'hosting) per poter
   caricare immagini dagli articoli.

## Come funziona la dashboard

- **Articoli** → crea, modifica, elimina, pubblica o metti in bozza. Puoi
  caricare un'immagine dal computer oppure incollare un URL esterno.
- **Categorie** → aggiungi nuove categorie oltre alle 4 di base, cambia colore
  e icona (usa i nomi delle icone da fontawesome.com/search, es.
  `fa-solid fa-music`). Una categoria non si può eliminare se contiene ancora
  articoli.
- **Impostazioni** → titolo/tagline del sito, immagine hero, bio nel footer,
  tutti i link social, i 6 link "I miei progetti" nel footer (blog, CineBlog,
  La Ciurma, Card), e cambio password.

Tutto quello che era "fisso" nella versione statica HTML (categorie, social,
link ai blog, numerazione articoli) ora è gestito dal database e modificabile
dalla dashboard, senza toccare il codice.

## Nota su WordPress

Questo progetto è volutamente **indipendente da WordPress**: se in futuro
installi WordPress sul dominio principale mrrearm.it, questa cartella
`/actuality` continuerà a funzionare senza conflitti, perché ha il proprio
database e le proprie sessioni admin separate.

## MySQL o Turso?

Il progetto supporta **entrambi**, selezionabili con un solo flag in
`config.php`:

```php
define('DB_DRIVER', 'mysql');  // oppure 'turso'
```

**Come funziona sotto il cofano**: tutto il resto del codice (dashboard,
`functions.php`, ecc.) parla sempre nello stesso modo — `$pdo->prepare()`,
`->execute()`, `->fetch()` — indipendentemente dal driver scelto. Con
`DB_DRIVER = 'turso'`, `config.php` carica `db/TursoPdo.php`, una classe che
traduce quelle stesse chiamate in richieste HTTP verso l'API `/v2/pipeline`
di Turso, restituendo gli stessi tipi di dato di MySQL. Non devi toccare
nessun'altra pagina.

**Compromesso da capire prima di scegliere Turso**: ogni `execute()` diventa
una chiamata di rete verso i server Turso, quindi ogni pagina con più query
(es. la dashboard, che carica statistiche + articoli + categorie) sarà più
lenta di qualche centinaio di millisecondi rispetto a MySQL locale incluso
nell'hosting. Ho già aggiunto una cache per le categorie e le impostazioni
(richieste più volte per pagina) per limitare le chiamate ripetute, ma resta
comunque più lento di MySQL su hosting condiviso. Se il traffico è
contenuto (blog personale) la differenza è impercettibile; se prevedi molte
visite simultanee, MySQL resta la scelta più solida.

### Se scegli Turso

**Da PC/Mac con la CLI di Turso:**
1. Crea il database: `turso db create actuality-blog`
2. Prendi l'URL HTTP: `turso db show actuality-blog --http-url`
3. Crea un token: `turso db tokens create actuality-blog`
4. Importa lo schema (sintassi SQLite, diversa da `schema.sql`):
   ```
   turso db shell actuality-blog < sql/schema-turso.sql
   ```

**Da Termux/Android (la CLI ufficiale di Turso non è compatibile con Termux):**
1. Crea database e token dalla dashboard web: https://turso.tech → accedi →
   crea un nuovo database → copia URL HTTP e genera un token dalla stessa
   pagina.
2. Installa PHP in Termux (pacchetto nativo, funziona senza problemi):
   ```
   pkg install php -y
   ```
3. Usa lo script incluso `tools/import-schema.php` per caricare lo schema
   via API HTTP (stessa tecnica usata dal driver `db/TursoPdo.php`, nessuna
   CLI richiesta):
   ```
   cd actuality
   php tools/import-schema.php "https://il-tuo-db-tuaorg.turso.io" "il-tuo-token"
   ```
   Lo script mostra riga per riga cosa sta eseguendo e alla fine un riepilogo.

5. In `config.php`:
   ```php
   define('DB_DRIVER', 'turso');
   define('TURSO_DB_URL', 'https://actuality-blog-tuoorg.turso.io');
   define('TURSO_AUTH_TOKEN', 'il-token-generato-al-punto-3');
   ```
6. Non serve configurare le costanti `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS`,
   vengono semplicemente ignorate.

Se scegli MySQL invece, usa `sql/schema.sql` (non `schema-turso.sql`) come
indicato nei passi di installazione più sopra.

## Deploy su Render (con Turso)

Questa è l'alternativa all'hosting condiviso: niente cPanel, deploy da
repository Git, container Docker gestito da Render. Il progetto include già
tutto il necessario (`Dockerfile`, `docker/entrypoint.sh`, `render.yaml`).

**Differenza importante rispetto a mrrearm.it/actuality/**: su Render il
sito vive alla radice del proprio dominio (es.
`https://actuality-blog.onrender.com`), non in una sottocartella. Se vuoi
comunque un indirizzo tipo `actuality.mrrearm.it`, lo configuri come
**sottodominio** (non sottocartella) nelle impostazioni "Custom Domain" di
Render, puntando un record CNAME del tuo DNS verso l'host che Render ti
assegna. Una sottocartella dello stesso dominio (`mrrearm.it/actuality`)
richiederebbe un reverse proxy sul server che già ospita mrrearm.it — fuori
dallo scopo di un deploy Render puro.

### Passi

1. **Crea il database Turso** (se non l'hai già fatto):
   ```
   turso db create actuality-blog
   turso db shell actuality-blog < sql/schema-turso.sql
   turso db show actuality-blog --http-url
   turso db tokens create actuality-blog
   ```
   Tieni a portata di mano URL e token: ti servono al passo 4.

2. **Metti il progetto su GitHub** (Render fa il deploy da repository Git,
   non da upload diretto di file): crea un repository nuovo e pusha tutta
   la cartella `actuality/`.

3. **Su Render**: "New +" → "Web Service" → collega il repository →
   Render rileva automaticamente il `Dockerfile` (Environment: Docker).
   In alternativa, usa "New +" → "Blueprint" e punta al file `render.yaml`
   incluso, che pre-compila gran parte della configurazione.

4. **Variabili d'ambiente** (Render → il tuo servizio → "Environment"):
   ```
   DB_DRIVER = turso
   TURSO_DB_URL = https://actuality-blog-tuoorg.turso.io
   TURSO_AUTH_TOKEN = il-token-generato-al-passo-1
   BASE_PATH = (lascia il valore vuoto)
   ```
   Non servono le variabili `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS`, vengono
   ignorate quando `DB_DRIVER=turso`.

5. **Deploy**: Render builda l'immagine Docker e la pubblica. Al primo
   avvio vai su `https://il-tuo-servizio.onrender.com/admin/login.php` con
   `admin` / `cambia-subito-2026` e **cambia subito la password**.

### Cosa sapere sul piano gratuito di Render

- Il servizio "dorme" dopo un periodo di inattività e il primo caricamento
  dopo il risveglio è più lento (10-30 secondi): normale sul piano free, non
  è un bug.
- **Il filesystem è effimero**: ogni redeploy o riavvio cancella i file
  caricati tramite "carica immagine dal computer" nella dashboard. Su
  Render, usa sempre il campo "URL immagine" invece dell'upload diretto, a
  meno di aggiungere un Persistent Disk (disponibile solo sui piani a
  pagamento, montabile su `assets/uploads/`).
- Il dominio gratuito `.onrender.com` ha già HTTPS incluso; anche il
  sottodominio custom (`actuality.mrrearm.it`) ottiene un certificato SSL
  automatico da Render dopo la verifica del DNS.

## URL leggibili degli articoli

Ogni articolo è raggiungibile con un indirizzo che riprende il titolo, in
stile Wikipedia, invece del generico `article.php?id=17`. Esempio:

```
https://actuality-jt1z.onrender.com/Disclosure_Day_(2026)_-_Non_siamo_soli
```

**Come funziona**: il file `.htaccess` nella radice del progetto reindirizza
qualunque percorso che non corrisponda a un file/cartella reale verso
`article.php?slug=...`. Gli articoli **creati da ora in poi** generano lo
slug automaticamente dal titolo, senza bisogno di fare nulla.

**L'URL resta stabile**: modificando il titolo di un articolo già
pubblicato, l'indirizzo pubblico NON cambia (per non rompere eventuali link
già condivisi sui social). Lo vedi comunque scritto nel form di modifica,
sopra il campo Titolo.

**I vecchi link `?id=17` continuano a funzionare** anche dopo questo
aggiornamento: non si rompe nulla di quello che avevi già condiviso.

### Se hai già articoli pubblicati prima di questo aggiornamento

Gli articoli esistenti hanno ancora lo slug nel vecchio formato
(`le-notizie-della-settimana`). Per aggiornarli tutti al nuovo formato
leggibile in un colpo solo:

```bash
php tools/regenerate-slugs.php "https://il-tuo-db-tuaorg.turso.io" "il-tuo-token"
```

(per MySQL, lo stesso aggiornamento si può fare manualmente da phpMyAdmin,
oppure chiedimi uno script equivalente se ti serve)

### Nota per l'hosting condiviso (cPanel)

Il file `.htaccess` richiede che l'hosting abbia `AllowOverride All`
attivo — è il comportamento di default sulla stragrande maggioranza degli
hosting condiviso italiani. Se le URL leggibili danno errore 404 su tutto,
contatta il supporto del tuo hosting chiedendo di verificare questa
impostazione per la tua cartella.

## Newsletter via email (mail di conferma + notifica nuovi articoli)

Il sito raccoglie sempre gli indirizzi email degli iscritti nella tabella
`subscribers`, indipendentemente da questa configurazione. Per far partire
le email vere e proprie (conferma iscrizione + notifica quando pubblichi un
articolo) serve un account SMTP — il progetto non ha bisogno di librerie
esterne, parla il protocollo SMTP direttamente.

**Cosa succede se non configuri nulla**: il sito continua a funzionare
normalmente, gli iscritti vengono comunque salvati nel database, semplicemente
non parte nessuna email. Nessun errore visibile per l'utente.

### ⚠️ Se sei su Render: usa l'API HTTP, non SMTP

Dal 2025 Render blocca tutte le porte SMTP (25, 465, 587) in uscita sui
servizi con piano gratuito, per prevenire spam. Nessuna configurazione SMTP
può funzionare in queste condizioni — non è un problema del codice o delle
credenziali, è un blocco a livello di rete della piattaforma.

Per questo il progetto supporta anche l'**API HTTP di Brevo** (porta 443,
mai bloccata), che dal punto di vista di Render è indistinguibile da una
normale richiesta a un sito web.

**Su Render**, oltre alle variabili già viste, imposta anche:
```
EMAIL_DRIVER = brevo_api
BREVO_API_KEY = la-tua-api-key-di-brevo
```

L'API key si trova in un punto diverso rispetto alla chiave SMTP: Brevo →
**Settings** → **SMTP & API** → scheda **API Keys** (non la scheda SMTP) →
**Generate a new API key**. Le variabili `SMTP_HOST`/`SMTP_PORT`/`SMTP_USER`/
`SMTP_PASS`/`SMTP_SECURE` non servono più con questo driver — restano solo
`SMTP_FROM_EMAIL` e `SMTP_FROM_NAME`, usate anche dall'API per intestare le
email.

**Su hosting condiviso classico** (cPanel), invece, SMTP funziona
normalmente senza questa restrizione: lascia `EMAIL_DRIVER=smtp` (il
default) e segui la procedura sotto.

### Come attivarlo (hosting condiviso con SMTP)

1. **Scegli un provider SMTP**. Alcuni con piano gratuito adatto a un blog
   personale: Brevo (ex Sendinblue, 300 email/giorno gratis), Gmail (con una
   "password per le app", non quella normale del tuo account), Mailgun,
   Resend. Ti servono: host SMTP, porta, username, password.

2. **Imposta queste variabili** (in `config.php` su hosting condiviso,
   oppure come variabili d'ambiente su Render — stesso meccanismo già usato
   per `DB_DRIVER` e le credenziali Turso):

   ```
   EMAIL_ENABLED = true
   SMTP_HOST = smtp-relay.brevo.com        (esempio, dipende dal provider)
   SMTP_PORT = 587
   SMTP_USER = il-tuo-username-smtp
   SMTP_PASS = la-tua-password-smtp
   SMTP_SECURE = tls                        (usa 'ssl' se il provider richiede la porta 465)
   SMTP_FROM_EMAIL = no-reply@mrrearm.it
   SMTP_FROM_NAME = Scopri. Racconta. Sogna.
   ```

3. Riavvia/redeploy il sito perché le nuove variabili vengano lette.

### Quando partono le email

- **Mail di conferma**: subito dopo che qualcuno si iscrive dal footer.
- **Notifica nuovo articolo**: quando pubblichi un articolo nuovo, oppure
  quando porti un articolo da "Bozza" a "Pubblicato" modificandolo. **Non**
  riparte per le modifiche successive a un articolo già pubblicato, per non
  spammare gli iscritti ad ogni piccola correzione.

### Un limite da conoscere

Le email vengono inviate una per una, in sequenza, nello stesso momento in
cui pubblichi l'articolo — per poche decine/centinaia di iscritti va benissimo,
ma se la lista dovesse crescere molto (migliaia di iscritti) la pubblicazione
di un articolo diventerebbe lenta. In quel caso servirebbe un sistema di coda
(invio in background), che possiamo costruire più avanti se necessario.

## Sicurezza inclusa

- Password admin con hash bcrypt (`password_hash`/`password_verify`)
- Protezione CSRF su tutti i form (login, articoli, categorie, impostazioni,
  newsletter, eliminazioni)
- Query sempre parametrizzate via PDO (nessun rischio SQL injection)
- Upload immagini limitato alle estensioni jpg/jpeg/png/webp/gif
- Cartella `sql/` e `assets/uploads/` protette da esecuzione PHP via `.htaccess`
