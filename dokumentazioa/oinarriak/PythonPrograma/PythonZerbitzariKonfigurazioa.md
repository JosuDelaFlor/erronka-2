
# Gmail-perfil — Python bidezko posta elektronikoen automatizazioa

## 1. Sarrera

Proiektu honetan Python bidez garatutako aplikazio bat erabiltzen da, datu-base batean erregistratutako erabiltzaileak automatikoki egiaztatzeko eta profileko irudirik ez duten erabiltzaileei posta elektroniko bat bidaltzeko.

Helburu nagusia prozesu hau automatizatzea da, erabiltzaileak eskuz egiaztatu beharrik ez izateko.

Aplikazioa Ubuntu Server batean instalatu da Docker erabiliz. Gainera, Cron bidezko ataza programatu bat konfiguratu da, programa egunero goizeko 08:00etan automatikoki exekutatzeko.

Docker erabiltzeari esker, ez da beharrezkoa Python zuzenean Ubuntu Serverren instalatzea, aplikazioak bere edukiontzia erabiltzen baitu.

## 2. Proiektuaren helburuak

Proiektuaren helburu nagusiak hauek dira:

- Profileko irudirik ez duten erabiltzaileak automatikoki identifikatzea.
- MySQL datu-baseko erregistroak kontsultatzea.
- Erabiltzaileen posta elektronikoko helbideak lortzea.
- SMTP bidez jakinarazpenak bidaltzea.
- Aplikazioa egunero automatikoki exekutatzea.
- Docker bidez instalazioa eta konfigurazioa erraztea.
- Zerbitzuak edukiontzi desberdinetan banatzea.
- Ubuntu Server erabiliz aplikazioa zentralizatzea.

## 3. Erabilitako teknologiak

| Teknologia | Funtzioa |
|---|---|
| Ubuntu Server 24.04 | Aplikazioa exekutatzen duen zerbitzaria |
| Python 3.10 | Programazio-lengoaia |
| Docker | Edukiontzien exekuzioa |
| Docker Compose | Zerbitzuen kudeaketa |
| MySQL 8 | Datu-basea |
| smtp4dev | Probak egiteko SMTP zerbitzaria |
| Git | Kodearen bertsioen kontrola |
| Cron | Atazen programazioa |
| Bash | Exekuzio-scriptak |

## 4. Aplikazioaren funtzionamendua

Aplikazioaren helburua erabiltzaileen profileko irudiak egiaztatzea eta irudirik ez duten erabiltzaileei jakinarazpen bat bidaltzea da.

Programaren aurreikusitako funtzionamendua honako hau da:

1. Python aplikazioa abiarazten da.
2. MySQL datu-basearekin konexioa egiten du.
3. Erabiltzaileen erregistroak kontsultatzen ditu.
4. Erabiltzaile bakoitzak profileko irudirik duen egiaztatzen du.
5. Irudirik ez duten erabiltzaileen posta elektronikoko helbideak lortzen ditu.
6. SMTP zerbitzuaren bidez jakinarazpenak bidaltzen ditu.
7. Prozesua amaitzean, aplikazioa gelditzen da.

Hasierako garapenean smtp4dev erabiltzen da posta elektronikoen bidalketa probatzeko.

**Oharra:** prozesu osoa behar bezala egiten dela egiaztatzeko, `main.py` fitxategiaren exekuzioa probatu behar da.

## 5. Proiektuaren arkitektura

Aplikazioak Docker bidezko hiru zerbitzu nagusi erabiltzen ditu:

- **db:** MySQL datu-basea.
- **smtp4dev:** posta elektronikoen probetarako zerbitzaria.
- **app:** Python aplikazioa.

Arkitektura honela antolatuta dago:

```text
                  UBUNTU SERVER
                        |
                   Docker Compose
                        |
           +------------+------------+
           |            |            |
         MySQL       smtp4dev      Python
           |            |            |
           +------------+------------+
                        |
                  Python aplikazioa
                        |
                       Cron
                        |
                 Egunero 08:00etan
```

MySQL eta smtp4dev zerbitzuak martxan mantentzen dira, eta Python aplikazioa programatutako orduan exekutatzen da.

Horrela, Python programa ez da etengabe martxan egon behar.

## 6. Proiektuaren egitura

Proiektuaren fitxategi nagusiak hauek dira:

```text
gmail-perfil/
|
|-- Dockerfile
|-- docker-compose.yml
|-- requirements.txt
|-- main.py
|
|-- scripts/
|   |-- crontab.sh
|   |
|   |-- init/
|       |-- db.sql
```

### Fitxategien azalpena

**Dockerfile**

Python aplikazioaren edukiontzia sortzeko erabiltzen da. Python-en bertsioa eta beharrezko mendekotasunak instalatzen ditu.

**docker-compose.yml**

MySQL, smtp4dev eta Python zerbitzuak definitzen ditu.

**requirements.txt**

Python aplikazioak behar dituen liburutegiak jasotzen ditu.

**main.py**

Aplikazioaren fitxategi nagusia da. Programaren exekuzioa bertatik hasten da.

**scripts/crontab.sh**

Python aplikazioa Docker bidez exekutatzeko erabiltzen den Bash script-a da.

**scripts/init/db.sql**

MySQL datu-basearen hasierako konfigurazioa egiteko erabiltzen da.

## 7. Ubuntu Server prestatzea

Aplikazioa instalatzeko Ubuntu Server makina birtual bat erabili da.

Hasieran zerbitzariak ez zuen Docker instalatuta, eta beharrezko tresnak instalatu behar izan dira.

Python ez da zuzenean zerbitzarian instalatu, Docker edukiontziaren barruan erabiltzen delako.

### 7.1. Sistema eguneratzea

Lehenik, Ubuntu-ren paketeen zerrenda eguneratu da:

```bash
sudo apt update
```

Ondoren, beharrezko tresnak instalatu dira:

```bash
sudo apt install -y ca-certificates curl git cron
```

Tresna horien funtzioak:

- `ca-certificates`: HTTPS ziurtagiriak egiaztatzeko.
- `curl`: Internetetik fitxategiak deskargatzeko.
- `git`: proiektuaren kodea deskargatzeko.
- `cron`: programatutako atazak exekutatzeko.

### 7.2. Docker instalatzea

Docker instalatzen saiatzean errore bat agertu da, Ubuntu-k ez zituelako Docker-en pakete ofizialak aurkitzen.

Arazoa konpontzeko Docker-en biltegi ofiziala gehitu da.

Lehenik, gakoentzako direktorioa sortu da:

```bash
sudo install -m 0755 -d /etc/apt/keyrings
```

Ondoren, Docker-en GPG gakoa deskargatu da:

```bash
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
```

Gakoari irakurtzeko baimenak eman zaizkio:

```bash
sudo chmod a+r /etc/apt/keyrings/docker.asc
```

Docker-en biltegia konfiguratu da Ubuntu 24.04 sistemarako:

```bash
sudo tee /etc/apt/sources.list.d/docker.sources > /dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: noble
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF
```

Ondoren, paketeen zerrenda berriro eguneratu da:

```bash
sudo apt update
```

Azkenik, Docker eta Docker Compose instalatu dira:

```bash
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

### 7.3. Docker zerbitzua aktibatzea

Instalazioa amaitu ondoren, Docker zerbitzua aktibatu da:

```bash
sudo systemctl enable --now docker
```

Komando honek Docker abiarazten du eta zerbitzaria piztean automatikoki hasteko prestatzen du.

Docker-en bertsioa egiaztatzeko:

```bash
docker --version
```

Docker Compose egiaztatzeko:

```bash
docker compose version
```

Instalazioa probatzeko:

```bash
sudo docker run hello-world
```

Dena ondo badago, Docker-ek proba-mezu bat erakusten du.

## 8. Git biltegia deskargatzea

Aplikazioaren kodea Git biltegi batean gordeta dago.

Zerbitzarira deskargatzeko `git clone` komandoa erabiltzen da:

```bash
git clone URL_DEL_REPOSITORIO
```

Proiektuaren kokapena zerbitzarian honako hau da:

```text
/home/erronka-2/gmail-perfil
```

Proiektuaren direktoriora sartzeko:

```bash
cd /home/erronka-2/gmail-perfil
```

Fitxategiak ikusteko:

```bash
ls -la
```

Horrela, aplikazioaren fitxategiak zerbitzarian daudela egiaztatzen da.

## 9. Docker-en konfigurazioa

Aplikazioa Docker Compose bidez exekutatzen da.

Docker Compose erabilita, zerbitzu guztiak fitxategi bakar batetik kudeatu daitezke.

### 9.1. MySQL datu-basea

MySQL zerbitzuak irudi hau erabiltzen du:

```yaml
image: mysql:8.0
```

Konfiguratutako datu-basearen izena:

```text
ziber_db
```

Datu-basearen hasierako egitura sortzeko fitxategi hau erabiltzen da:

```text
scripts/init/db.sql
```

MySQL zerbitzuak `healthcheck` bat ere badu, datu-basea erabilgarri dagoen egiaztatzeko.

Python aplikazioa MySQL zerbitzuaren menpe dago, datu-baseko informazioa kontsultatu behar duelako.

### 9.2. smtp4dev zerbitzaria

Posta elektronikoak probatzeko smtp4dev erabiltzen da.

Docker-en erabiltzen den irudia:

```yaml
image: rnwood/smtp4dev:v3
```

Konfiguratutako atakak:

```text
8080:80
2525:25
```

- `8080`: smtp4dev zerbitzuaren web-interfazea.
- `2525`: SMTP konexioetarako ataka.

Web-interfazera sartzeko:

```text
http://ZERBITZARIAREN_IP:8080
```

smtp4dev aplikazioak probetan sortutako mezuak ikusteko aukera ematen du.

Kontuan izan behar da smtp4dev probetarako zerbitzua dela eta ez duela berez posta elektronikorik kanpoko erabiltzaileei bidaltzen.

### 9.3. Python aplikazioa

Python aplikazioa Dockerfile bidez prestatzen da.

Dockerfile-a Python 3.10 irudian oinarritzen da eta `requirements.txt` fitxategiko mendekotasunak instalatzeko prestatuta dago.

Erabilitako egituraren adibidea:

```dockerfile
FROM python:3.10-slim

WORKDIR /app

COPY requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt

COPY . .

CMD ["python", "main.py"]
```

Dockerfile honek honako prozesua egiten du:

1. Python 3.10 irudia erabiltzen du.
2. `/app` lan-direktorioa sortzen du.
3. Mendekotasunen fitxategia kopiatzen du.
4. Beharrezko liburutegiak instalatzen ditu.
5. Aplikazioaren kodea kopiatzen du.
6. `main.py` exekutatzen du.

Horri esker, Ubuntu Serverren ez da Python zuzenean instalatu behar.

## 10. Docker edukiontziak prestatzea

Proiektuaren direktoriora sartu gara:

```bash
cd /home/erronka-2/gmail-perfil
```

Lehenik Docker Compose konfigurazioa egiaztatu da:

```bash
sudo docker compose config
```

Ondoren, Python aplikazioaren irudia eraikitzeko komandoa erabili da:

```bash
sudo docker compose build app
```

Komando honek Dockerfile irakurtzen du eta Python aplikazioaren edukiontzia exekutatzeko irudia prestatzen du.

### 10.1. MySQL eta smtp4dev abiaraztea

Bi zerbitzuak martxan jartzeko:

```bash
sudo docker compose up -d db smtp4dev
```

`-d` parametroarekin edukiontziak bigarren planoan exekutatzen dira.

Zerbitzuen egoera ikusteko:

```bash
sudo docker compose ps
```

MySQL eta smtp4dev martxan egotea beharrezkoa da Python aplikazioak haien zerbitzuak erabili ahal izateko.

### 10.2. Python aplikazioa eskuz exekutatzea

Python aplikazioa probatzeko:

```bash
sudo docker compose run --rm app
```

Komando honek:

1. Python edukiontzi bat sortzen du.
2. Aplikazioa exekutatzen du.
3. Programa amaitu arte itxaroten du.
4. Edukiontzi tenporala ezabatzen du.

Horrela, Python aplikazioa une jakin batean exekutatu daiteke, etengabe martxan mantendu gabe.

## 11. Cron bidezko automatizazioa

Proiektuaren helburuetako bat Python aplikazioa egunero automatikoki exekutatzea da.

Horretarako Cron erabili da.

Cron Linux sistemetako ataza-programatzaile bat da, komandoak edo script-ak ordu jakin batean automatikoki exekutatzeko aukera ematen duena.

Kasu honetan, aplikazioa egunero goizeko 08:00etan exekutatzeko konfigurazioa prestatu da.

### 11.1. Cron instalatzea eta aktibatzea

Cron instalatzeko:

```bash
sudo apt install cron -y
```

Zerbitzua aktibatzeko:

```bash
sudo systemctl enable --now cron
```

Cron martxan dagoen ikusteko:

```bash
sudo systemctl status cron
```

Zerbitzua ondo badago, `active (running)` egoeran agertuko da.

### 11.2. crontab.sh fitxategia

Aplikazioak exekuziorako script bat dauka:

```text
/home/erronka-2/gmail-perfil/scripts/crontab.sh
```

Hasierako konfigurazioan komando hau erabiltzen zen:

```bash
docker compose up --build app
```

Komando hau aldatu da, aplikazioa egunero exekutatzeko ez delako beharrezkoa irudia behin eta berriro eraikitzea.

Erabiltzeko prestatu den script-a:

```bash
#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd -- "${SCRIPT_DIR}/.." && pwd)"

cd "${PROJECT_DIR}"

/usr/bin/docker compose run --rm app
```

Script-ak lehenik bere kokapena identifikatzen du.

Ondoren, proiektuaren direktorio nagusira sartzen da eta Python aplikazioa Docker Compose bidez exekutatzen du.

`run --rm` erabilita, edukiontzi tenporala ezabatzen da programa amaitzean.

### 11.3. Script-ari baimenak ematea

Script-a exekutagarria izateko:

```bash
chmod +x scripts/crontab.sh
```

Bere kokapena egiaztatzeko:

```bash
realpath scripts/crontab.sh
```

Lortutako bidea:

```text
/home/erronka-2/gmail-perfil/scripts/crontab.sh
```

Bide hau Cron konfigurazioan erabili da.

### 11.4. Cron-en ataza sortzea

Cron konfiguratzeko komando hau erabili da:

```bash
sudo crontab -e
```

Lehen aldiz exekutatzean, Ubuntu-k testu-editore bat aukeratzeko eskatu du.

Nano editorea aukeratu da:

```text
1. /bin/nano
```

Ondoren, fitxategiaren amaieran lerro hau gehitu da:

```cron
0 8 * * * /bin/bash /home/erronka-2/gmail-perfil/scripts/crontab.sh >> /var/log/python-diario.log 2>&1
```

Lerro honekin Python aplikazioaren exekuzioa egunero 08:00etan programatzen da.

### 11.5. Cron-en konfigurazioaren azalpena

Erabilitako Cron adierazpena:

```cron
0 8 * * *
```

| Eremua | Balioa | Esanahia |
|---|---|---|
| Minutua | 0 | 00 minutuan |
| Ordua | 8 | Goizeko 08:00etan |
| Eguna | * | Egunero |
| Hilabetea | * | Hilabete guztietan |
| Asteko eguna | * | Asteko egun guztietan |

Horrela, programa egunero 08:00etan exekutatzeko prestatuta dago.

Script-a exekutatzeko erabiltzen den komandoa:

```bash
/bin/bash /home/erronka-2/gmail-perfil/scripts/crontab.sh
```

Eta irteera eta erroreak fitxategi batean gordetzeko:

```bash
>> /var/log/python-diario.log 2>&1
```

Horri esker, programaren exekuzioan sortutako mezuak eta erroreak berrikusi daitezke.

### 11.6. Cron konfigurazioa egiaztatzea

Programatutako atazak ikusteko:

```bash
sudo crontab -l
```

Espero den lerroa:

```cron
0 8 * * * /bin/bash /home/erronka-2/gmail-perfil/scripts/crontab.sh >> /var/log/python-diario.log 2>&1
```

Lerro hau agertzen bada, Cron-en ataza gordeta dagoela esan nahi du.

## 12. Ordu-eremuaren konfigurazioa

Aplikazioa Espainiako penintsulako ordutegiaren arabera exekutatzeko `Europe/Madrid` ordu-eremua erabiltzen da.

Ordu-eremua konfiguratzeko:

```bash
sudo timedatectl set-timezone Europe/Madrid
```

Konfigurazioa egiaztatzeko:

```bash
timedatectl
```

Espero den ordu-eremua:

```text
Time zone: Europe/Madrid
```

Horrela, Cron-ek Espainiako ordu lokala erabiliko du, udako eta neguko ordutegi-aldaketak kontuan hartuta.

## 13. Funtzionamendu-probak

Automatizazioa erabili aurretik, aplikazioa eskuz exekutatzea komeni da.

Horri esker, Docker eta Python behar bezala funtzionatzen dutela egiazta daiteke.

### 13.1. Script-a eskuz exekutatzea

Komando hau erabil daiteke:

```bash
sudo bash /home/erronka-2/gmail-perfil/scripts/crontab.sh
```

Honek Cron-ek erabiliko duen script bera exekutatzen du.

Programa amaitzean, irteera eta balizko erroreak aztertu behar dira.

### 13.2. Docker edukiontziak egiaztatzea

Edukiontzien egoera ikusteko:

```bash
sudo docker compose ps
```

### 13.3. Cron egiaztatzea

Cron zerbitzuaren egoera ikusteko:

```bash
sudo systemctl status cron
```

Programatutako atazak ikusteko:

```bash
sudo crontab -l
```

### 13.4. Exekuzioen erregistroak

Cron bidezko lehen exekuzioa egin ondoren, programaren irteera ikusteko:

```bash
sudo tail -n 100 /var/log/python-diario.log
```

Erregistroa zuzenean jarraitzeko:

```bash
sudo tail -f /var/log/python-diario.log
```

MySQL zerbitzuaren erregistroak kontsultatzeko:

```bash
sudo docker compose logs --tail=50 db
```

SMTP zerbitzuaren erregistroak ikusteko:

```bash
sudo docker compose logs --tail=50 smtp4dev
```

Proba horien bidez zerbitzuen egoera eta exekuzioan gertatutako arazoak aztertu daitezke.

## 14. Proiektua eguneratzea

Python aplikazioaren kodean aldaketak egiten direnean, Git biltegitik azken bertsioa deskargatu daiteke.

Lehenik, proiektuaren direktoriora sartu:

```bash
cd /home/erronka-2/gmail-perfil
```

Ondoren, azken aldaketak deskargatu:

```bash
git pull
```

Python aplikazioaren Docker irudia berriro eraiki:

```bash
sudo docker compose build app
```

Hurrengo exekuzioan Python aplikazioaren bertsio berria erabiliko da.

Behar izanez gero, MySQL eta SMTP zerbitzuak berriro abiarazteko:

```bash
sudo docker compose up -d db smtp4dev
```

Cron-en konfigurazioa ez da aldatu behar Python kodea eguneratzen den bakoitzean, script-aren kokapena berdina mantentzen bada.

## 15. Mantentze-lanetarako komandoak

### Docker edukiontzi aktiboak ikusi

```bash
sudo docker ps
```

### Proiektuko zerbitzuak ikusi

```bash
sudo docker compose ps
```

### MySQL eta SMTP zerbitzuak abiarazi

```bash
sudo docker compose up -d db smtp4dev
```

### Python aplikazioa eskuz exekutatu

```bash
sudo docker compose run --rm app
```

### Docker zerbitzuak gelditu

```bash
sudo docker compose down
```

### Programatutako atazak ikusi

```bash
sudo crontab -l
```

### Cron konfigurazioa aldatu

```bash
sudo crontab -e
```

### Python-en exekuzioen erregistroak ikusi

```bash
sudo tail -f /var/log/python-diario.log
```

### Docker zerbitzua berrabiarazi

```bash
sudo systemctl restart docker
```

### Zerbitzariaren ordua ikusi

```bash
date
```

## 16. Segurtasun-neurriak eta hobekuntzak

Aplikazioa ingurune erreal batean erabiltzeko, hainbat segurtasun-neurri kontuan hartu behar dira.

### 16.1. MySQL datu-basea

Jatorrizko Docker Compose konfigurazioan MySQL pasahitz bat zuzenean idatzita dago.

Segurtasuna hobetzeko:

- Pasahitz sendoak erabili.
- Pasahitzak ingurune-aldagaien bidez kudeatu.
- Datu-basearen erabiltzaileen baimenak mugatu.
- Datu-basearen segurtasun-kopiak egin.

Gainera, MySQL zerbitzuak `3306` ataka argitaratzen du.

Python aplikazioak Docker sarearen bidez bakarrik konektatu behar badu, ez da beharrezkoa ataka hori kanpora argitaratzea.

### 16.2. Datuen iraunkortasuna

Jatorrizko Docker Compose fitxategian ez dago MySQL datuentzako bolumen iraunkor izendaturik konfiguratuta.

Datuak babesteko, bolumen bat gehitzea komeni da:

```yaml
volumes:
  - db_data:/var/lib/mysql
```

Horrez gain, datu-basearen segurtasun-kopiak egitea gomendatzen da.

### 16.3. Posta elektronikoak

smtp4dev probak egiteko erabiltzen da.

Benetako erabiltzaileei mezuak bidali nahi bazaizkie, SMTP zerbitzu egoki bat konfiguratu beharko da.

Kontuan hartu beharreko alderdiak:

- SMTP zerbitzariaren autentifikazioa.
- Konexio segurua erabiltzea.
- Posta elektronikoko kredentzialak babestea.
- Erabiltzaile berari mezu bera behin eta berriro ez bidaltzea.

### 16.4. Cron eta erregistroak

Cron bidezko exekuzioak aldizka egiaztatu behar dira.

Horretarako, erregistro-fitxategia berrikusi daiteke:

```bash
sudo tail -n 100 /var/log/python-diario.log
```

Horrela, aplikazioaren exekuzioan sortutako erroreak antzeman daitezke.

## 17. Lortutako konfigurazioa

Proiektuan Ubuntu Server ingurune bat prestatu da Python aplikazioa Docker bidez exekutatzeko.

Egindako lan nagusiak:

1. Ubuntu Server prestatzea.
2. Docker eta Docker Compose instalatzea.
3. Docker-en biltegi ofiziala konfiguratzea.
4. Git bidez proiektua zerbitzarira deskargatzea.
5. Python aplikazioaren Docker irudia prestatzea.
6. MySQL eta smtp4dev zerbitzuak konfiguratzea.
7. Python aplikazioa eskuz exekutatzeko komandoak prestatzea.
8. `crontab.sh` script-a egokitzea.
9. Cron bidez egunero 08:00etan exekutatzeko ataza konfiguratzea.
10. Exekuzioen erregistroak gordetzeko bidea prestatzea.

Konfigurazioarekin, Python aplikazioak ez du zertan etengabe martxan egon.

Cron-ek programatutako orduan abiarazten du aplikazioa, eta prozesua amaitzen denean edukiontzi tenporala ezabatzen da.

**Azken egiaztapena:** exekuzio automatikoa guztiz baliozkotzeko, lehenengo exekuzio programatuaren erregistroa aztertu behar da.

## 18. Ondorioak

Proiektu honetan Python aplikazio baten exekuzioa automatizatzeko ingurune bat prestatu da Ubuntu Server erabiliz.

Docker erabiltzeari esker, aplikazioa eta bere mendekotasunak edukiontzi batean exekutatzen dira, Python zuzenean zerbitzarian instalatu beharrik gabe.

Docker Compose bidez MySQL, smtp4dev eta Python zerbitzuak kudeatu daitezke.

Bestalde, Cron erabiliz aplikazioa egunero goizeko 08:00etan exekutatzeko konfigurazioa prestatu da.

Sistema honen abantaila nagusia prozesua automatizatzea da, eskuz egunero programa abiarazi beharrik gabe.

Gainera, Docker-ek zerbitzuen kudeaketa eta aplikazioaren eguneraketak errazten ditu.

Etorkizunean, posta elektronikoen bidalketa erreala konfiguratzea, datu-basearen segurtasuna hobetzea eta exekuzioen monitorizazioa zabaltzea komeni da.
