# Segurtasun-auditoria eta zuzenketa

## Irismena

`web-aplikazioaren` tokiko auditoria: PHP, MySQL, saioak, fitxategien igoera eta
Docker barne. Probak `localhost`-en aurka bakarrik exekutatu behar dira.

## Checklist-a

- [x] SQL Injection autentifikazioan, bilaketetan, produktuetan eta erabiltzaileetan: kontsulta prestatuak.
- [x] XSS islatua eta gordea: HTMLaren escape-a `e()` erabiliz eta sarrera-mugak.
- [x] MD5 bidezko pasahitzak: `password_hash()`/`password_verify()` erabiliz migratuta.
- [x] Saioaren finkapena eta lapurreta: IDaren birsorkuntza eta cookie gogortuak.
- [x] CSRF pasahitz-aldaketetan, erabiltzaileetan eta produktuetan: ausazko tokenak.
- [x] Upload bidezko RCEa: benetako MIMEa, MIMEtik eratorritako luzapena, ausazko izena eta exekutagarria ez den biltegiratzea.
- [x] Sarbide-kontrola: baimena zentralizatuta `require_login()` eta `require_admin()` erabiliz.
- [x] MySQLren esposizioa: `ports` gabe, Docker sare pribatuan eta root ez den erabiltzailearekin.
- [x] Sekretuak: ingurune-aldagaiak eta `.env` Gitetik kanpo.
- [x] HTTP goiburu babesleak: `nosniff`, `SAMEORIGIN` eta referrer politika.
- [ ] HTTPS: inplementazioak kudeatutako reverse proxy batean amaitu behar da.
- [ ] Hasierako sekretuaren biraketa: nahitaezkoa produkzioaren aurretik.

## Aurreko ebidentziak eta ondorengo egiaztapena

Ondorengo PoCak laborategi isolatu batean errepikatzeko erreferentziak dira. Ez exekutatu
besteren sistemetan.

| Bektorea | Aurreko PoCa | Ondorengo egiaztapena |
|---|---|---|
| Login SQLi | Loginaren erabiltzailean `' OR 1=1 #`; lehen saio balioduna itzultzen zuen. | Sarrera testu gisa tratatzen da eta kredentzial okerrak itzultzen ditu. |
| Bilaketako SQLi | `keyword` parametroan komatxo bat eta SQL baldintza bat erabili; lehen kontsulta aldatzen zuen. | Bilaketak bi parametro prestatu erabiltzen ditu eta ez du SQLa aldatzen. |
| XSS | `?action=search&keyword=<script>alert(document.domain)</script>`. | Edukia escapatu gisa bistaratzen da eta JavaScript ez da exekutatzen. |
| Upload bidezko RCEa | `.php` fitxategi bat igo erregistroko edo produktuetako formularioan. | JPEG/PNG/GIF baliodunak bakarrik onartzen dira; izena ausazkoa da eta Apache-k PHP blokeatzen du `uploads`-en. |
| CSRF | Kanpoko POST eskaera bat bidali pasahitza aldatzeko. | Tokena falta bada, erantzuna HTTP 403 da. |
| MD5 | Hasierako `5f4...` hash-a `md5('password')`-ekin alderatu. | Dump-ak `$2y$` bcrypt hash-a dauka eta balidazioak `password_verify()` erabiltzen du. |
| MySQL agerian | 3306 atakara ostalaritik konektatzen saiatu. | Ez dago ataka-maparik; `app` zerbitzuak bakarrik sar daiteke `db`-ra. |

## Aldaketen erregistroa

| Fitxategia | Aldaketa | Arrazoia |
|---|---|---|
| `src/Core/security.php` | Berria | CSRF, escape-a, baimena eta upload-en balidazioa. |
| `src/Modules/Auth/login.php`, `register.php` | Aldatuta | Kontsulta prestatuak, hashing-a, balidazioa eta XSSaren zuzenketa. |
| `src/Modules/Account/account.php` | Aldatuta | CSRF, baimena eta pasahitzen eta erabiltzaileen kudeaketa segurua. |
| `src/Modules/Catalog/main.php`, `description.php`, `updel.php` | Aldatuta | CSRF, baimena, kontsulta prestatuak, balidazioa eta XSSaren zuzenketa. |
| `index.php`, `config/config.php`, `src/Infrastructure/database.php` | Aldatuta | Sarrera-puntua, ingurune-konfigurazioa, saio seguruak eta errore ez-xehatuak. |
| `database/init.sql` | Aldatuta | Hash segurua, luzera egokia eta datu-base esplizitua. |
| `Dockerfile`, `docker/apache.conf`, `docker-compose.yml` | Berriak/aldatuta | Build erreproduzigarria, sare pribatua, HTTP lokalaren argitalpena eta upload-en babesa. |
| `.dockerignore` | Berria | Sekretuak, SQL dump-ak, dokumentazioa eta garapen-fitxategiak irudian ez sartzea. |
| `.env.example`, `.gitignore` | Berriak | Tokiko konfigurazio segurua eta sekretuen bazterketa. |
| `uploads/.gitkeep` | Berria | Upload direktorio hutsa eta bolumenerako prest mantentzea. |
| `includes/main_orig.php` | Ezabatuta | Kontsulta eta irteera ahulak zituen kopia zaharra kentzea. |
| `composer.json`, `phpunit.xml.dist`, `tests/SecurityTest.php` | Berriak | PHPUnit unitate-probak eta garapeneko mendekotasunak. |
| `.github/workflows/security.yml` | Berria/aldatuta | Sintaxi-egiaztapenak, PHPUnit, segurtasun-erregresioa eta Semgrep CI-n. |
| `README.md` | Aldatuta | Instalazioa, exekuzioa, probak eta arkitektura dokumentatzea. |

## Egitura modularra

```text
web-aplikazioa/
├── index.php                 # Front controllerra eta aplikazioaren konposizioa
├── config/                   # Ingurunetik lortutako konfigurazioa
├── src/
│   ├── Core/                 # Zeharkako segurtasuna eta utilitate komunak
│   ├── Infrastructure/      # Kanpoko egokitzaileak, MySQL konexioa adibidez
│   └── Modules/
│       ├── Auth/             # Erregistroa eta autentifikazioa
│       ├── Account/          # Kontua eta erabiltzaileen administrazioa
│       └── Catalog/          # Bilaketa eta produktuen kudeaketa
├── database/                 # Hasieratzeko SQLa
├── docker/                   # Apache-ren konfigurazioa
├── images/                   # Katalogoko baliabideak
├── uploads/                  # Igotako fitxategiak, baimen bereiziekin
└── .github/workflows/        # CI/CD automatizazioa
```

Banaketak erantzukizun bakarrak jarraitzen ditu: konfigurazioak ez du negozio-logikarik,
azpiegiturak konexioa kapsulatzen du, `Core`-k zeharkako politikak ditu eta modulu bakoitzak
gaitasun funtzional bat taldekatzen du. Eskalatzeko, modulu berriak gehi daitezke fitxategi
monolitiko bat handitu gabe; azpiegitura beste inplementazio batekin ordezka daiteke eta
domeinu-zerbitzuak atera daitezke sarrera-puntua aldatu gabe.

## Ezagutzen diren mugak

Proiektuak HTML zaharra eta erakustaldiko hasierako erabiltzailea mantentzen ditu bateragarritasun
akademikoa dela eta. Argitaratu aurretik, erabiltzaile hori ezabatu, HTTPS gaitu, sekretuak biratu
eta TLS duen proxy bat konfiguratu behar da.

## Ebidentziak eta proben egoera

Dokumentu honek PoC erreproduzigarriak eta espero diren egiaztapenak jasotzen ditu. Pantaila-argazkiak
tokiko urrats bakoitza exekutatzean hartu behar dira, zuzenketaren aurretik eta ondoren. Ez da
binariozko pantaila-argazkirik biltegian sartu.

Exekutatutako egiaztapen automatikoak:

- PHP fitxategi guztietan `php -l`: zuzena.
- `docker compose config`: zuzena.
- `git diff --check`: zuzena.
- `docker compose build`: Docker/Podman daemon-a abiarazita exekutatu behar da.

## Unitate-probak eta CI/CD

Hasieran CI workflow-a bazegoen, baina ez zegoen unitate-proben suit-rik. PHPUnit gehitu da
`web-aplikazioa/` barruan:

- `composer.json`: garapeneko mendekotasuna eta `composer test` komandoa.
- `phpunit.xml.dist`: suitearen konfigurazioa.
- `tests/SecurityTest.php`: HTMLaren escape-aren eta CSRF tokenen probak.
- `.github/workflows/security.yml`: PHPUnit exekutatzen du `push` eta `pull_request` bakoitzean,
  PHP sintaxiarekin eta Semgrep-ekin batera.

Lokalean exekutatzeko, Composer behar da:

```bash
cd web-aplikazioa
composer install
composer test
```

Makina honetan PHPUnit ezin izan zen exekutatu Composer instalatuta ez zegoelako. Suitearen
sintaxia `php -l` erabiliz balidatu zen; exekuzio osoa tokiko Composer-en edo GitHub Actions-en
egiteko dago.

## Instalazio eta exekuzio gida

Gida operatibo osoa `README.md` fitxategian dago. Laburpena:

1. Weba exekutatzeko Docker/Compose instalatu.
2. `.env.example` `.env`-era kopiatu eta aplikaziorako eta MySQLren `root`-erako sekretu desberdinak konfiguratu.
3. `docker compose --env-file .env up -d --build` exekutatu.
4. `db` zerbitzua `healthy` dagoela egiaztatu eta `http://localhost:8090` ireki.
5. Tokiko probetarako PHP CLI, Composer eta `curl`, `dom`, `mbstring` eta `xml` luzapenak instalatu.
6. `web-aplikazioa` karpetatik `composer install` eta `composer test` exekutatu.

Pipeline-ak mendekotasun berak instalatzen ditu GitHub Actions-en eta PHP sintaxia, PHPUnit
probak, segurtasun-erregresioaren egiaztapenak eta Semgrep exekutatzen ditu `push` eta
`pull_request` bakoitzean.

## Berrantolaketaren erregistroa

Arkitektura-berrantolaketa segurtasun-zuzenketaren ondoren egin zen, akoplamendua murrizteko
eta etorkizuneko hedapenak errazteko. Front controllerreko inklusioak, upload-en bideak,
hasierako SQLaren bidea eta `Dockerfile`-eko Apache konfigurazioaren erreferentzia eguneratu ziren.

Ez ziren URL publikoak edo lehendik zeuden ekintzen parametro funtzionalak aldatu. Ondorengo
balidazioak baieztatu zuen `src/` karpetako PHP fitxategi guztiek sintaxi zuzena dutela eta
Docker Compose-k `database/init.sql` eta `docker/apache.conf` aurkitzen dituela.
