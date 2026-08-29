<?php
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$pageTitle = 'Privacy Policy — ' . get_setting($pdo, 'site_title', '');
$siteTitle = get_setting($pdo, 'site_title', 'Scopri. Racconta. Sogna.');
$siteUrl   = get_setting($pdo, 'social_sito', 'https://www.mrrearm.it');

require __DIR__ . '/partials/header.php';
?>

<div class="article-page">
  <a class="article-back" href="<?= url('index.php') ?>"><i class="fa-solid fa-arrow-left"></i> Torna alla home</a>

  <h1>Privacy Policy</h1>
  <p style="font-size:13px; color:var(--ink-soft); margin-top:-10px;">Ultimo aggiornamento: <?= date('d/m/Y') ?></p>

  <p>La presente informativa descrive le modalità di trattamento dei dati personali degli utenti che consultano <strong><?= h($siteTitle) ?></strong> (di seguito, il "Sito"), in conformità al Regolamento (UE) 2016/679 (GDPR) e alla normativa applicabile in materia di protezione dei dati personali.</p>

  <h2 style="font-size:19px; margin:28px 0 10px;">Titolare del trattamento</h2>
  <p>Il Titolare del trattamento dei dati raccolti tramite il Sito è il proprietario di <?= h($siteTitle) ?>, contattabile all'indirizzo email <a href="mailto:info@mrrearm.it">info@mrrearm.it</a> o tramite il sito <a href="<?= h($siteUrl) ?>" target="_blank" rel="noopener"><?= h($siteUrl) ?></a>.</p>

  <h2 style="font-size:19px; margin:28px 0 10px;">Quali dati raccolgo e perché</h2>

  <p><strong>Commenti agli articoli.</strong> Quando lasci un commento raccolgo il nome che inserisci e, se lo fornisci, il tuo indirizzo email (facoltativo, non viene mai pubblicato). Il testo del commento resta in attesa di approvazione prima di comparire pubblicamente. Base giuridica: consenso, prestato inviando volontariamente il commento. Puoi chiedere la rimozione del tuo commento in qualsiasi momento scrivendomi.</p>

  <p><strong>Voto a stelle.</strong> Quando voti un articolo salvo il voto numerico e la data, senza alcun dato che ti identifichi. Per evitare voti multipli dallo stesso browser uso un cookie tecnico che ricorda quali articoli hai già votato — questo cookie non contiene informazioni personali, solo un elenco di numeri identificativi di articoli.</p>

  <p><strong>Iscrizione alla newsletter.</strong> Se ti iscrivi salvo il tuo indirizzo email al solo scopo di inviarti aggiornamenti sui nuovi contenuti del blog. Base giuridica: consenso. Puoi cancellarti in qualsiasi momento scrivendomi a <a href="mailto:info@mrrearm.it">info@mrrearm.it</a>; il tuo indirizzo verrà eliminato dal database.</p>

  <p><strong>Dati di navigazione.</strong> Come qualunque sito web, il server che ospita <?= h($siteTitle) ?> raccoglie in modo automatico alcune informazioni tecniche (es. indirizzo IP, tipo di browser, pagine visitate) necessarie al funzionamento del servizio. Questi dati vengono trattati dal fornitore di hosting per finalità tecniche e di sicurezza e non vengono da me utilizzati per profilare gli utenti.</p>

  <h2 style="font-size:19px; margin:28px 0 10px;">Cookie utilizzati</h2>
  <p>Il Sito utilizza esclusivamente cookie tecnici, necessari al suo funzionamento, per i quali non è richiesto il consenso preventivo ai sensi della normativa vigente:</p>
  <ul style="margin:0 0 16px 20px; color:var(--ink-soft); font-size:15px; line-height:1.8;">
    <li>un cookie di sessione, usato solo quando accedo all'area amministrativa del blog per gestirne i contenuti;</li>
    <li>un cookie che ricorda quali articoli hai già votato, per evitare voti ripetuti;</li>
    <li>un cookie tecnico di sicurezza (protezione CSRF) sui moduli di commento, voto e iscrizione alla newsletter.</li>
  </ul>
  <p>Il Sito non utilizza cookie di profilazione né cookie pubblicitari di terze parti.</p>

  <h2 style="font-size:19px; margin:28px 0 10px;">Servizi esterni utilizzati</h2>
  <p>Per il funzionamento del Sito vengono richiamate alcune risorse da server esterni, che possono ricevere il tuo indirizzo IP come parte normale del funzionamento di internet:</p>
  <ul style="margin:0 0 16px 20px; color:var(--ink-soft); font-size:15px; line-height:1.8;">
    <li><strong>Google Fonts</strong> — fornisce i caratteri tipografici del Sito;</li>
    <li><strong>Font Awesome (tramite Cloudflare CDN)</strong> — fornisce le icone grafiche del Sito;</li>
    <li><strong>Turso</strong> — fornisce l'infrastruttura del database dove sono conservati articoli, commenti e iscrizioni newsletter;</li>
    <li><strong>Render</strong> — fornisce l'hosting su cui il Sito è pubblicato.</li>
  </ul>
  <p>Questi fornitori agiscono in qualità di responsabili del trattamento (o, per le risorse statiche come font e icone, come semplici fornitori tecnici di infrastruttura) e trattano i dati secondo le proprie informative privacy.</p>

  <h2 style="font-size:19px; margin:28px 0 10px;">Conservazione dei dati</h2>
  <p>I commenti approvati restano visibili finché non ne richiedi la rimozione. Gli indirizzi email raccolti per la newsletter vengono conservati fino alla richiesta di cancellazione. I voti a stelle, non essendo collegati a nessun dato personale, vengono conservati senza limiti di tempo.</p>

  <h2 style="font-size:19px; margin:28px 0 10px;">I tuoi diritti</h2>
  <p>In qualunque momento puoi esercitare, scrivendo a <a href="mailto:info@mrrearm.it">info@mrrearm.it</a>, i diritti previsti dagli articoli 15-22 del GDPR: accesso ai tuoi dati, rettifica, cancellazione, limitazione del trattamento, portabilità dei dati e opposizione al trattamento. Hai inoltre diritto di proporre reclamo all'Autorità Garante per la protezione dei dati personali (<a href="https://www.garanteprivacy.it" target="_blank" rel="noopener">www.garanteprivacy.it</a>) qualora ritenga che il trattamento dei tuoi dati violi la normativa vigente.</p>

  <h2 style="font-size:19px; margin:28px 0 10px;">Modifiche a questa informativa</h2>
  <p>Questa informativa può essere aggiornata nel tempo, ad esempio in caso di introduzione di nuove funzionalità sul Sito. La data di ultimo aggiornamento è indicata in cima alla pagina.</p>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
