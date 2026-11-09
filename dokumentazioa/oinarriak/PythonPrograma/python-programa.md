# Erronka 2: Erabiltzaileen Profilaren Kudeaketa eta Email Jakinarazpenak

## 1. Sarrera
Proiektu honen helburua sistema automatizatu bat garatzea da, datu-base bateko erabiltzaileak aztertu eta, profileko argazkirik ez dutenei, argazkia igotzea derrigorrezkoa dela jakinarazteko mezu elektroniko bat bidaltzeko.

Garatutako soluzioak **Python** erabiltzen du motor nagusi gisa, eta **Docker** bidez edukiontzietan isolatuta exekutatzen da. Proiektua diseinatzean diseinu-patroi modernoak erabili dira, bereziki **SOLID printzipioak**, modularitatea eta eskalagarritasuna bermatzeko.

## 2. Erabilitako Teknologiak
* **Python 3.10**: Logika nagusia eta orkestrazioa.
* **MySQL 8.0**: Datu-base erlazionala erabiltzaileen eta produktuen datuak gordetzeko.
* **smtp4dev**: Garapenerako SMTP zerbitzari lokala, mezu elektronikoak atzitzeko web interfaze batekin (errealean inor ez molestatzeko).
* **Docker & Docker Compose**: Zerbitzu guztiak (Datu-basea, SMTP zerbitzaria eta Python aplikazioa) ontziratzeko eta kudeatzeko.
* **Windows Task Scheduler (.bat)**: Atazaren exekuzioa astero automatizatzeko.

## 3. Proiektuaren Egitura
Proiektua geruzatan banatuta dago, erantzukizunak banatzeko (Single Responsibility Principle):

```text
gmail-perfil/
├── config/
│   └── settings.py               # Ingurune-aldagaien eta konfigurazioen kudeaketa (Dataclasses).
├── model/
│   └── erabiltzaileak.py         # Negozio-logika eta domeinu-eredua (Erabiltzailea).
├── repositories/
│   └── erabiltzaileak_repository.py # Datu-basetik datuak lortu eta objektuetara mapatzeko ardura.
├── services/
│   ├── mysql_client.py           # MySQL datu-basearekiko konexioa eta kontsultak.
│   └── smtp_client.py            # SMTP zerbitzariarekiko konexioa eta emailak bidaltzea.
├── scripts/
│   └── crontab.bat               # Windows-erako ataza automatizatuaren script-a.
├── Dockerfile                    # Python aplikazioaren edukiontzia sortzeko irudia.
├── docker-compose.yml            # Arkitektura osoko zerbitzuen definizioa (db, smtp4dev, app).
├── db.sql                        # Datu-basearen hasierako eskema eta datuak.
├── requirements.txt              # Python dependentziak (mysql-connector-python).
└── main.py                       # Aplikazioaren sarrera-puntua eta orkestratzailea.
```

## 4. Arkitektura eta Diseinu Erabakiak
1. **Inbertsioaren Printzipioa (Dependency Inversion)**: `main.py` fitxategiak dependentziak (konfigurazioak, datu-base bezeroa, SMTP bezeroa) sortzen ditu eta klaseei injektatzen dizkie. Horrela, klaseak ez daude inplementazio zehatz bati estuki lotuta.
2. **Erabiltzaileen Repositorioa**: `ErabiltzaileakRepository` klaseak SQL kontsulten eta gure `Erabiltzaileak` domeinu-ereduaren arteko zubi lana egiten du.
3. **Konfigurazio Zentralizatua**: Datu-basearen eta SMTP zerbitzariaren kredentzialak eta portuak `settings.py` fitxategian kudeatzen dira modu seguruan.

## 5. Instalazioa eta Exekuzioa

### Eskakizunak
* Docker eta Docker Compose instalatuta izatea.
* Git (proiektua klonatzeko).

### Pausoak
1. **Klonatu errepositorioa** (edo deskargatu karpeta):
   ```bash
   git clone <https://github.com/JosuDelaFlor/erronka-2>
   cd erronka-2/gmail-perfil
   ```

2. **Ingurunea prestatu eta garbitu**:
   Aurreko exekuzioetako datu-base zaharrak garbitzeko, exekutatu:
   ```bash
   docker-compose down -v
   ```

3. **Zerbitzuak abiarazi**:
   Aplikazioa eraiki eta exekutatzeko, erabili komando hau. `db` eta `smtp4dev` zerbitzuak abiaraziko dira, eta ondoren, `app` edukiontziak script-a exekutatuko du.
   ```bash
   docker-compose up --build
   ```

4. **Egiaztapena**:
   * Ireki nabigatzailea eta joan `http://localhost:8080` helbidera smtp4dev-en interfazea ikusteko. Bertan argazkirik ez duten erabiltzaileei bidalitako mezuak ikusi ahal izango dituzu.

## 6. Automatizazioa (Windows)
Sistemak astero (adibidez, astelehenero goizeko 08:00etan) mezuak automatikoki bidaltzeko, Windows-eko atazen programatzailea erabiliko da.

1. Ireki **Task Scheduler** (Programador de tareas) Windows-en.
2. Sortu ataza berri bat ("Crear tarea básica...").
3. Ezarri abiarazlea "Astero" (Semanalmente) bezala, astelehenetan 08:00etan.
4. "Ekintza" (Acción) atalean, hautatu "Programa bat abiarazi" (Iniciar un programa).
5. Bilatu eta hautatu proiektuaren barruan dagoen `scripts/crontab.bat` fitxategia.
   * *Oharra: Ziurtatu `.bat` fitxategiko bide absolutua zure ordenagailuko bide errealarekin bat datorrela.*
6. Gorde ataza. Hemendik aurrera, Windows-ek Docker edukiontzia piztuko du script-a exekutatzeko, esku-hartzerik gabe.