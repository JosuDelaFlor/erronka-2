# Web-aplikazio segurua

## Aurrebaldintzak

Aplikazioa Docker erabiliz exekutatzeko:

- Docker Engine edo Docker Desktop.
- Docker Compose v2 (`docker compose`).
- Git.

Unitate-probak lokalean exekutatzeko:

- PHP CLI 8.2 edo berriagoa.
- Composer 2.
- PHPren `curl`, `dom`, `mbstring` eta `xml` luzapenak.

Debian/Parrot sistemetan, garapen-tresnak instalatzeko:

```bash
sudo apt update
sudo apt install -y docker.io docker-compose-plugin composer \
    php-cli php-curl php-mbstring php-xml
```

Docker Desktop erabiltzen baduzu eta zure shell-ak existitzen ez den Podman socket bat
konfiguratuta badu, ezabatu aldagaia Docker erabili aurretik:

```bash
unset DOCKER_HOST
docker context use desktop-linux
```

## Docker bidezko instalazioa eta exekuzioa

Exekutatu komandoak `web-aplikazioa` karpetatik:

```bash
cd /home/josu/Desktop/Repos/erronka-2/web-aplikazioa
```

1. Sortu ingurune-aldagaien fitxategi lokala:

   ```bash
   cp .env.example .env
   ```

2. Editatu `.env` eta ezarri pasahitz luze eta desberdinak:

   ```env
   MYSQL_DATABASE=BDweb
   MYSQL_USER=webapp
   MYSQL_PASSWORD=jarri-pasahitz-luze-bat
   MYSQL_ROOT_PASSWORD=jarri-beste-pasahitz-luze-bat
   ```

   Ez igo `.env` Git-era.

3. Balidatu konfigurazioa:

   ```bash
   docker compose --env-file .env config
   ```

4. Eraiki irudiak eta abiarazi edukiontziak:

   ```bash
   docker compose --env-file .env up -d --build
   ```

   Docker Desktop eta `DOCKER_HOST` konfiguratuta badituzu:

   ```bash
   env -u DOCKER_HOST docker --context desktop-linux compose \
       --env-file .env up -d --build
   ```

5. Egiaztatu egoera:

   ```bash
   docker compose --env-file .env ps
   ```

   `db` zerbitzuak `healthy` egoeran eta `app` zerbitzuak `Up` egoeran agertu behar dute.

6. Ireki aplikazioa:

   <http://localhost:8090>

PHP eta MySQL edukiontzi bereizietan exekutatzen dira. MySQL-ek ez du atakarik
argitaratzen ostalariaren sisteman; Compose-ren sare pribatuaren bidez bakarrik
eskura daiteke. HTTPko `8090` ataka sarbide lokalerako argitaratzen da. Produkzioan
HTTPS eta firewall baten bidez babestu behar da.

Hasierako demo-erabiltzailea `admin@bdweb` da, eta aldi baterako pasahitza `password`
da. Aldatu berehala eta ez erabili kontu hori produkzioan.

### Erregistroak eta geldiaraztea

```bash
docker compose --env-file .env logs -f app
docker compose --env-file .env logs -f db
docker compose --env-file .env down
```

`docker compose down` komandoak zerbitzuak geldiarazten ditu datuak ezabatu gabe.
Garapeneko datu-basea berriro hasieratzeko eta bolumen iraunkorra ezabatzeko:

```bash
docker compose --env-file .env down -v
docker compose --env-file .env up -d --build
```

Ez erabili `down -v` datuak gorde behar badituzu.

## Egitura

- `index.php`: HTTP sarrera-puntua.
- `config/`: ingurune-aldagaietan oinarritutako konfigurazioa.
- `src/Core/`: zeharkako segurtasuna eta utilitate komunak.
- `src/Infrastructure/`: kanpoko zerbitzuekiko konexioak.
- `src/Modules/Auth/`: erregistroa eta saio-hasiera.
- `src/Modules/Account/`: kontuen kudeaketa.
- `src/Modules/Catalog/`: produktuak, bilaketa eta katalogoaren administrazioa.
- `database/`: MySQL hasieratzeko SQLa.
- `docker/`: Apache-ren konfigurazioa.
- `images/`: katalogoko baliabide estatikoak.
- `uploads/`: igotako fitxategien biltegiratzea.

## Arkitektura-berrantolaketa

Lehen, konfigurazioa eta modulu guztiak erroko karpetan zeuden:

```text
conf.php
includes/
scripts/
apache.conf
```

Orain erantzukizunen arabera antolatuta dago:

```text
config/config.php                 # Ingurune-konfigurazioa
src/Core/security.php             # Zeharkako segurtasuna
src/Infrastructure/database.php  # MySQL-rako sarbidea
src/Modules/Auth/                 # Saio-hasiera eta erregistroa
src/Modules/Account/              # Kontuak eta erabiltzaileak
src/Modules/Catalog/              # Produktuak eta katalogoa
database/init.sql                 # Datu-basearen hasieraketa
docker/apache.conf                # Apache-ren konfigurazioa
```

### Mugimenduen mapa

| Aurreko kokapena | Uneko kokapena | Arrazoia |
|---|---|---|
| `conf.php` | `config/config.php` | Konfigurazioa aplikazioaren logikatik bereiztea. |
| `includes/security.php` | `src/Core/security.php` | Zeharkako segurtasun-politikak zentralizatzea. |
| `includes/mysql.php` | `src/Infrastructure/database.php` | MySQLrekiko mendekotasuna isolatzea. |
| `includes/login.php`, `register.php` | `src/Modules/Auth/` | Autentifikazio-funtzioak taldekatzea. |
| `includes/account.php` | `src/Modules/Account/` | Kontuen kudeaketa bereiztea. |
| `includes/main.php`, `description.php`, `updel.php` | `src/Modules/Catalog/` | Katalogoko eragiketak taldekatzea. |
| `scripts/db.sql` | `database/init.sql` | Datu-baseko baliabideak argi identifikatzea. |
| `apache.conf` | `docker/apache.conf` | Inplementazioaren azpiegitura bereiztea. |

`index.php` front controller gisa mantendu da, uneko inplementazioarekiko bateragarritasuna
bermatzeko. Inklusioak `__DIR__` oinarri duten bide absolutuetara aldatu dira; horrela,
PHP abiarazten den lan-direktorioak ez du eraginik.

### Aplikatutako printzipioak

- **Erantzukizun bakarra:** karpeta bakoitzak erantzukizun tekniko edo funtzional bat du.
- **Modularitatea:** autentifikazioa, kontuak eta katalogoa modu independentean garatu daitezke.
- **Akoplamendu txikia:** MySQLrekiko konexioa `Infrastructure` geruzan isolatuta dago.
- **Hedagarritasuna:** modulu berriak gehi daitezke `index.php` fitxategi monolitiko bihurtu gabe.
- **Mantengarritasuna:** konfigurazioak, SQLak eta Dockerrek kokapen esplizituak dituzte.

Berrantolaketak ez ditu URL publikoak edo ekintzen izenak aldatu (`login`, `register`,
`account`, `description` eta `updel`).

## Inplementatutako segurtasun-neurriak

- Kontsulta prestatuak login, bilaketa, erabiltzaileen kudeaketa eta produktuetarako.
- `password_hash()`/`password_verify()` MD5aren ordez.
- Saio-identifikatzailearen birsorkuntza autentifikazioaren ondoren.
- `HttpOnly`, `SameSite=Lax` eta `Secure` cookieak HTTPS erabiltzen denean.
- CSRF tokenak datuak aldatzen dituzten eragiketa guztietan.
- Testuinguruaren araberako escape-a `htmlspecialchars` erabiliz.
- Upload-ak benetako MIMEarekin, tamaina-mugarekin, ausazko izenarekin eta PHPren exekuzioaren blokeoarekin.
- MySQL erabiltzaile dedikatua eta 3306 atakarik argitaratzen ez duen sare pribatua.
- PHP irudi erreproduzigarria, `mysqli` luzapena build-ean instalatuta.

## Produkzioa

Erabili HTTPS Apache-ren aurrean, sekretuen kudeatzaile bat, ezabatu hasierako
erabiltzailea, aldatu pasahitzak eta ez argitaratu MySQLren ataka. `uploads`
direktorioa babeskopiak eta gutxieneko baimenak dituen bolumen batean gorde behar da.

## Probak eta CI/CD

Unitate-probak `tests/` karpetan daude eta PHPUnit erabiltzen dute. Exekutatu komando
guztiak `web-aplikazioa` karpetatik, ez `web-aplikazioa/tests` karpetatik:

```bash
cd /home/josu/Desktop/Repos/erronka-2/web-aplikazioa
composer install
composer test
```

`composer install` komandoak PHPUnit `vendor/` karpetan instalatzen du eta `composer.lock`
sortzen du. Ez exekutatu Composer `root` erabiltzailearekin; erabili ohiko erabiltzailea
baimen-arazoak saihesteko. Root gisa exekutatu baduzu, zuzendu jabetza:

```bash
sudo chown -R "$USER":"$USER" vendor composer.lock
```

Proba zehatz bat exekutatzeko:

```bash
vendor/bin/phpunit --filter SecurityTest
```

Uneko suiteak honako hauek egiaztatzen ditu:

- HTMLaren escape-a, XSS prebenitzeko.
- CSRF tokenen sorrera.
- CSRF tokenaren berrerabilpen zuzena saio berean.

`.github/workflows/security.yml` workflow-a `push` eta `pull_request` bakoitzean
exekutatzen da, eta honako hauek egiten ditu:

- Garapen-mendekotasunen instalazio erreproduzigarria.
- PHP fitxategi guztien sintaxiaren egiaztapena.
- PHPUnit unitate-probak.
- Segurtasun-erregresioaren egiaztapenak.
- SAST analisia Semgrep bidez.
