-- ============================================================
-- Scopri. Racconta. Sogna. — schema database
-- Importa questo file in phpMyAdmin (o via CLI) PRIMA di usare il sito
-- ============================================================

CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(60) NOT NULL,
  color_hex VARCHAR(7) NOT NULL DEFAULT '#1f8a94',
  icon_class VARCHAR(60) NOT NULL DEFAULT 'fa-solid fa-globe',
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  excerpt VARCHAR(300) DEFAULT NULL,
  content TEXT NOT NULL,
  image_url VARCHAR(400) NOT NULL,
  status ENUM('published','draft') NOT NULL DEFAULT 'published',
  published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscribers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categorie multiple per articolo (many-to-many). articles.category_id resta
-- la "categoria principale" (colore/icona sulla card), questa tabella è la
-- lista completa (inclusa la principale) usata per i filtri e i chip.
CREATE TABLE IF NOT EXISTS article_categories (
  article_id INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY (article_id, category_id),
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  article_id INT NOT NULL,
  author_name VARCHAR(100) NOT NULL,
  author_email VARCHAR(190) DEFAULT NULL,
  body TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  article_id INT NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(60) PRIMARY KEY,
  setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Categorie di base
-- ------------------------------------------------------------
INSERT INTO categories (slug, name, color_hex, icon_class, sort_order) VALUES
('attualita',  'Attualità',   '#1f8a94', 'fa-solid fa-globe', 1),
('tecnologia', 'Tecnologia',  '#e08a2b', 'fa-solid fa-microchip', 2),
('curiosita',  'Curiosità',   '#4c9a6a', 'fa-solid fa-wand-magic-sparkles', 3),
('spettacolo', 'Spettacolo',  '#8b5fbf', 'fa-solid fa-clapperboard', 4);

-- ------------------------------------------------------------
-- Impostazioni sito / footer (modificabili da admin/settings.php)
-- ------------------------------------------------------------
INSERT INTO settings (setting_key, setting_value) VALUES
('site_title', 'Scopri. Racconta. Sogna.'),
('site_tagline', 'News, curiosità e attualità ogni giorno'),
('site_eyebrow', 'Il blog di Ray D. — Mr ReArm'),
('footer_bio', 'Il blog di Ray D. (Mr ReArm): notizie, curiosità e attualità raccontate con uno sguardo personale, tra tecnologia, cinema, musica e tanto altro.'),
('hero_image', 'https://picsum.photos/seed/newsdesk/1600/700'),
('social_sito', 'https://www.mrrearm.it'),
('social_github', 'https://github.com/mrrearm'),
('social_instagram', 'https://instagram.com/mrrearm'),
('social_facebook', 'https://facebook.com/mrrearm'),
('social_x', 'https://x.com/RayRearm'),
('social_youtube', 'https://youtube.com/user/RayRearm'),
('social_tiktok', 'https://www.tiktok.com/@raydrearm?_t=ZN-8yguiKDCftm&_r=1'),
('social_telegram', 'https://t.me/mondotek'),
('social_kofi', 'https://ko-fi.com/mrrearm'),
('social_linkedin', 'https://linkedin.com/in/mrrearm'),
('project_spiritualita', 'https://raydrearm81.blogspot.com|Spiritualità & Curiosità|fa-solid fa-star'),
('project_tech', 'https://raydblog.blogspot.com|Blog Informatico|fa-solid fa-microchip'),
('project_canzoni', 'https://pennacuore.blogspot.com|Testi delle mie canzoni|fa-solid fa-feather'),
('project_cineblog', 'https://cineblog.page.gd|CineBlog|fa-solid fa-clapperboard'),
('project_laciurma', 'https://laciurma.xo.je/?i=1|La Ciurma|fa-solid fa-ship'),
('project_card', 'https://mrrearm.it/card.html|La mia Card|fa-solid fa-id-card');

-- ------------------------------------------------------------
-- Utente amministratore di default
-- Username: admin   Password: cambia-subito-2026
-- CAMBIA SUBITO la password dopo il primo accesso da admin/settings.php!
-- Hash generato con password_hash('cambia-subito-2026', PASSWORD_BCRYPT)
-- ------------------------------------------------------------
INSERT INTO admin_users (username, password_hash) VALUES
('admin', '$2y$10$BSz.yG.DkOdbbk.hKLpPgulMEZ4pau.7ikhshTHbignR4hpa45WxG');

-- ------------------------------------------------------------
-- 16 articoli iniziali (gli stessi del sito statico)
-- ------------------------------------------------------------
INSERT INTO articles (category_id, title, slug, excerpt, content, image_url) VALUES
(1,'Le notizie della settimana da non perdere','le-notizie-della-settimana-da-non-perdere','Una rassegna dei fatti che hanno davvero inciso sull''agenda pubblica.',
 'Dai palazzi del potere alle strade delle grandi città, la settimana appena trascorsa ha portato con sé una serie di sviluppi che vale la pena ripercorrere con calma, senza il rumore di fondo dei titoli lampo.\n\nAbbiamo selezionato i fatti che hanno davvero inciso sull''agenda pubblica, cercando di restituire il contesto necessario per capirli, non solo la loro superficie.\n\nUna rassegna pensata per chi vuole restare informato senza perdere tempo, con uno sguardo che privilegia la sostanza rispetto al clamore.',
 'https://picsum.photos/seed/attualita1/900/500'),
(2,'5 innovazioni che cambieranno il 2026','5-innovazioni-che-cambieranno-il-2026','Cinque ambiti dove i progressi tecnologici saranno più tangibili.',
 'Il prossimo anno porterà con sé una serie di novità tecnologiche destinate a incidere sulla vita quotidiana di milioni di persone, dalla produttività personale ai grandi sistemi industriali.\n\nAbbiamo individuato cinque ambiti — intelligenza artificiale applicata, energia, dispositivi indossabili, connettività e automazione domestica — dove i progressi promettono di essere più tangibili.\n\nNon si tratta di fantascienza, ma di strumenti che nel giro di pochi mesi potrebbero entrare stabilmente nelle nostre case e nei nostri uffici.',
 'https://picsum.photos/seed/tech1/900/500'),
(3,'10 fatti incredibili che (forse) non conoscevi','10-fatti-incredibili-che-forse-non-conoscevi','Dieci curiosità verificate tra scienza, natura e storia.',
 'Il mondo è pieno di piccoli dettagli sorprendenti che sfuggono alla vita di tutti i giorni: numeri, record e coincidenze che sembrano usciti da un romanzo.\n\nAbbiamo raccolto dieci curiosità verificate, tra scienza, natura e storia, capaci di far cambiare prospettiva su cose che davamo per scontate.\n\nUna lettura leggera ma istruttiva, perfetta per stupire amici e colleghi con qualche aneddoto inedito.',
 'https://picsum.photos/seed/curiosita1/900/500'),
(4,'I film più attesi di quest''anno','i-film-piu-attesi-di-questanno','Le uscite cinematografiche da non perdere.',
 'Tra grandi produzioni internazionali e opere d''autore, il calendario cinematografico di quest''anno promette emozioni per tutti i gusti.\n\nAbbiamo selezionato le uscite più attese, dai sequel dei franchise più amati alle nuove voci del cinema indipendente che meritano attenzione.\n\nUna guida utile per organizzare le prossime serate al cinema senza perdersi i titoli di cui tutti parleranno.',
 'https://picsum.photos/seed/spettacolo1/900/500'),
(1,'Cosa sta succedendo nel mondo oggi','cosa-sta-succedendo-nel-mondo-oggi','Uno sguardo d''insieme sugli eventi internazionali più rilevanti.',
 'Uno sguardo d''insieme sugli eventi internazionali più rilevanti del momento, per capire come i diversi fatti di cronaca si intreccino tra loro.\n\nDalle dinamiche geopolitiche alle questioni sociali, proviamo a offrire una lettura chiara e priva di sensazionalismo.\n\nL''obiettivo è dare gli strumenti per orientarsi in un flusso di notizie che spesso rischia di sopraffare più che informare.',
 'https://picsum.photos/seed/attualita2/900/500'),
(2,'Intelligenza artificiale: la guida per capirla','intelligenza-artificiale-la-guida-per-capirla','I concetti fondamentali spiegati con un linguaggio semplice.',
 'Se ne parla ovunque, ma non sempre è chiaro cosa sia davvero l''intelligenza artificiale e come funzioni nella pratica.\n\nIn questa guida proviamo a spiegare i concetti fondamentali con un linguaggio semplice, sgombrando il campo da miti ed esagerazioni.\n\nCapire le basi è il primo passo per usare questi strumenti in modo consapevole, sia nel lavoro che nella vita di tutti i giorni.',
 'https://picsum.photos/seed/tech2/900/500'),
(3,'Record e stranezze che lasciano a bocca aperta','record-e-stranezze-che-lasciano-a-bocca-aperta','Una selezione di record autentici, verificati alle fonti ufficiali.',
 'Dal luogo più freddo della Terra all''animale più longevo mai osservato, il pianeta continua a sorprenderci con primati fuori dal comune.\n\nAbbiamo raccolto una selezione di record autentici, alcuni noti e altri decisamente meno, verificati alle fonti ufficiali.\n\nUn piccolo viaggio tra le stranezze del mondo naturale e umano, per guardare la realtà con occhi diversi.',
 'https://picsum.photos/seed/curiosita2/900/500'),
(4,'Le serie TV di cui tutti parlano','le-serie-tv-di-cui-tutti-parlano','Le serie del momento tra thriller, drammi e ritorni attesi.',
 'Le piattaforme di streaming continuano a sfornare titoli capaci di catalizzare l''attenzione del pubblico e dei social.\n\nAbbiamo messo insieme le serie del momento, tra thriller psicologici, drammi familiari e ritorni molto attesi.\n\nUna selezione pensata per chi vuole sapere di cosa parlare alla prossima cena tra amici senza restare indietro.',
 'https://picsum.photos/seed/spettacolo2/900/500'),
(1,'Economia e mercati: gli aggiornamenti chiave','economia-e-mercati-gli-aggiornamenti-chiave','I dati più recenti su tassi, inflazione e mercati.',
 'Tassi di interesse, inflazione e andamento dei mercati: i temi economici continuano a influenzare le scelte di famiglie e imprese.\n\nAnalizziamo i dati più recenti per capire quali tendenze stanno emergendo e cosa potrebbero significare nei prossimi mesi.\n\nUn aggiornamento pensato per chi vuole comprendere l''economia senza perdersi nel gergo tecnico degli addetti ai lavori.',
 'https://picsum.photos/seed/attualita3/900/500'),
(2,'App e strumenti utili da scoprire subito','app-e-strumenti-utili-da-scoprire-subito','Gli strumenti più utili per produttività e creatività.',
 'Tra le migliaia di applicazioni disponibili, poche riescono davvero a semplificare la vita di tutti i giorni.\n\nAbbiamo testato e selezionato gli strumenti più utili per produttività, organizzazione personale e creatività, con un occhio di riguardo per quelli gratuiti.\n\nUna lista pratica da tenere a portata di mano, aggiornata con le novità più interessanti del momento.',
 'https://picsum.photos/seed/tech3/900/500'),
(3,'Misteri irrisolti che affascinano ancora oggi','misteri-irrisolti-che-affascinano-ancora-oggi','Alcuni degli enigmi più affascinanti mai risolti del tutto.',
 'Nonostante i progressi della scienza, alcuni enigmi continuano a resistere a ogni tentativo di spiegazione definitiva.\n\nRipercorriamo alcuni dei casi più affascinanti, tra archeologia, natura e fenomeni ancora dibattuti dagli esperti.\n\nStorie che alimentano da decenni curiosità e teorie, mantenendo intatto il loro fascino misterioso.',
 'https://picsum.photos/seed/curiosita3/900/500'),
(4,'Musica: le uscite da ascoltare questo mese','musica-le-uscite-da-ascoltare-questo-mese','Le uscite musicali più interessanti del mese.',
 'Nuovi album, singoli a sorpresa e ritorni attesi: il panorama musicale di questo mese offre parecchio da scoprire.\n\nAbbiamo raccolto le uscite più interessanti tra generi diversi, dal pop mainstream alle produzioni più di nicchia.\n\nUna playlist ideale per chi vuole restare aggiornato senza rincorrere ogni singola novità sui social.',
 'https://picsum.photos/seed/spettacolo3/900/500'),
(1,'Ambiente e clima: le notizie che contano','ambiente-e-clima-le-notizie-che-contano','Gli aggiornamenti più significativi su clima ed energie rinnovabili.',
 'I temi ambientali sono ormai parte integrante del dibattito pubblico, tra nuove politiche e dati scientifici sempre più allarmanti.\n\nRaccogliamo gli aggiornamenti più significativi su clima, energie rinnovabili e tutela degli ecosistemi, con un approccio basato sui fatti.\n\nInformazioni utili per capire la portata reale dei cambiamenti in corso e le risposte messe in campo a livello globale.',
 'https://picsum.photos/seed/attualita4/900/500'),
(2,'Sicurezza online: come proteggersi davvero','sicurezza-online-come-proteggersi-davvero','Le buone pratiche essenziali per proteggere account e dati.',
 'Truffe digitali, phishing e violazioni dei dati personali sono all''ordine del giorno: sapersi difendere è ormai una necessità.\n\nSpieghiamo le buone pratiche essenziali per proteggere account, dispositivi e informazioni sensibili senza bisogno di competenze tecniche avanzate.\n\nPiccoli accorgimenti quotidiani che fanno una grande differenza nella sicurezza della vita digitale di ognuno.',
 'https://picsum.photos/seed/tech4/900/500'),
(3,'Storia: eventi che hanno cambiato tutto','storia-eventi-che-hanno-cambiato-tutto','Episodi meno noti ma decisivi per il corso della storia.',
 'Alcuni momenti della storia hanno segnato uno spartiacque così netto da cambiare per sempre il corso degli eventi successivi.\n\nRipercorriamo episodi meno noti ma altrettanto decisivi, capaci di offrire uno sguardo diverso su fatti che pensavamo di conoscere bene.\n\nUn modo per riscoprire il passato e comprendere meglio le radici del presente.',
 'https://picsum.photos/seed/curiosita4/900/500'),
(4,'Cinema d''autore: consigli e recensioni','cinema-dautore-consigli-e-recensioni','I titoli più interessanti lontano dai grandi blockbuster.',
 'Lontano dai grandi blockbuster, il cinema d''autore continua a offrire storie originali e sguardi registici fuori dagli schemi.\n\nSelezioniamo i titoli più interessanti del momento, tra opere prime sorprendenti e ritorni di registi affermati.\n\nConsigli pensati per chi cerca al cinema qualcosa di diverso dal solito intrattenimento mainstream.',
 'https://picsum.photos/seed/spettacolo4/900/500');

-- ------------------------------------------------------------
-- Popola la tabella delle categorie multiple con la categoria
-- principale già assegnata ad ogni articolo (installazione nuova)
-- ------------------------------------------------------------
INSERT INTO article_categories (article_id, category_id)
SELECT id, category_id FROM articles;
