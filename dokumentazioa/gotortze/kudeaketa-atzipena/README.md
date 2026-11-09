# Gailuetarako atzipen ziurtatua eta FQDN bidez

Ugarteburu Torlojuak · 2. Erronka

Gailuen administrazioa izen bidez (FQDN) egiten da, ez IP bidez, eta kanal guztiak ziurtatuta daude: web interfazeak enpresako PKI-ak sinatutako HTTPS ziurtagiriekin, eta SSH zerbitzuak gako bidezko autentifikazioarekin. Gainera, administrazio-sareetatik bakarrik dago sarbidea.

| Gailua | Zerbitzua | FQDN | IP | Egoera |
|---|---|---|---|---|
| EN-pfSense | WebGUI | `pfsense.talde1.lan` | `192.168.112.1` | ✅ |
| U1-PaloAlto-IT | WebGUI | `pa-it.talde1.lan` | `192.168.20.1` | ✅ |
| EN-CertServ | SSH | `certserv.talde1.lan` | `192.168.111.20` | ✅ |
| EN-MikroTik | WebGUI, SSH, Winbox | `mikrotik.talde1.lan` | `192.168.112.2` | ✅ |
| EN-Web | HTTPS (web publikoa) | `www.talde1.lan` | `192.168.50.10` | ✅ |
| U1-PaloAlto-OT | WebGUI | | | Egiteko (2. zatia) |
| U1-DMZIT-XAMP | SSH | | | Egiteko (2. zatia) |
| U1-DMZOT-Ubuntu | SSH | | | Egiteko (2. zatia) |

## 1. DNS erregistroak

`talde1.lan` zonan (AD-n integratuta) A erregistro bat sortu da gailu bakoitzeko. IP-a gailuaren kudeaketa-interfazearena da: pfSense-ren WebGUI-a SISTEMAK saretik bakarrik eskuragarri dago, eta PA-IT-rena U1-IT interfazetik. Zona bi DC-en artean erreplikatzen denez, egoitza bakoitzak bertan ebazten ditu izenak.

![DNS erregistroak](img/01-dns-erregistroak.png)

## 2. Web ziurtagiriak

Ziurtagiriak EN-CertServ-eko Intermediate CA-k sinatzen ditu (ikus [PKI dokumentazioa](../pki/README.md)). VPN ziurtagiriekiko desberdintasunak:

- `extendedKeyUsage = serverAuth` bakarrik.
- SAN-ean FQDN-a eta kudeaketa-IP-a, nabigatzaileak izen zein IP bidez onar dezan.
- Iraupena 398 egunekoa, nabigatzaileek ez baitute iraupen luzeagoko zerbitzari-ziurtagiririk onartzen.

```bash
cd /root/ca/intermediate
openssl req -new -newkey rsa:2048 -nodes -keyout private/web-pfsense.key -out csr/web-pfsense.csr \
  -subj "/C=ES/O=Ugarteburu Torlojuak/OU=EN/CN=pfsense.talde1.lan"
openssl ca -config openssl.cnf -extfile web-pfsense.ext -days 398 -notext \
  -in csr/web-pfsense.csr -out certs/web-pfsense.crt
```

![Luzapenak](img/02-web-ext.png)

![Egiaztapena](img/03-web-verify.png)

![pfSense ziurtagiria](img/04-web-pfsense-san.png)

![PA-IT ziurtagiria](img/05-web-pa-it-san.png)

## 3. pfSense

Ziurtagiria eta gakoa *Cert Manager*-en inportatu dira, SISTEMAK-eko administrazio-ekipotik SSH bidez kopiatuta.

![WEB-pfsense](img/06-pfsense-web-ziurtagiria.png)

Hostname-a eta domeinua (`pfsense.talde1.lan`) ziurtagiriarekin bat datoz. Bestela pfSense-k izen bidezko sarbidea blokeatzen du (*DNS rebind* babesa).

![Hostname-a](img/07-pfsense-hostname.png)

WebGUI-ak HTTPS eta `WEB-pfsense` ziurtagiria erabiltzen ditu.

![Admin Access](img/08-pfsense-admin-access.png)

## 4. Root CA-ren banaketa GPO bidez

Nabigatzaileek ziurtagiriak onar ditzaten, domeinuko ekipo guztiek PKI-an konfiantza izan behar dute. `PKI-Ziurtagiriak` GPO-a domeinuari lotu zaio, eta bertan Root CA *Entidades de certificación raíz de confianza*-n eta Intermediate CA *Entidades de certificación intermedias*-en daude. Horrela segurtasun-politika modu zentralizatuan aplikatzen zaie domeinuko ekipo guztiei, bi egoitzetan.

![GPO-a sortu](img/09-gpo-sortu.png)

![Root CA](img/10-gpo-root.png)

![Intermediate CA](img/11-gpo-intermediate.png)

Emaitza: SISTEMAK-WIN-etik `https://pfsense.talde1.lan` abisurik gabe irekitzen da.

![pfSense kandatua](img/12-pfsense-kandatua.png)

Firefox-ek bere ziurtagiri-biltegia erabiltzen du eta ez du GPO-ko Root CA-a ikusten. **Hobekuntza:** Firefox-en ADMX txantiloiekin *Importar raíces empresariales* politika gaitzea GPO berean.

## 5. PA-IT

Ziurtagiria gako zifratuarekin inportatu da (Palo Alto-k pasahitz gabeko gakorik ez du onartzen), eta gakoaren esportazioa blokeatuta dago.

![Inportazioa](img/13-pa-web-inportatu.png)

`WEB-pa-it-profile` SSL/TLS profilak ziurtagiria eta gutxieneko bertsioa (TLS 1.2) zehazten ditu, eta kudeaketa-ezarpenetan aplikatzen da.

![SSL/TLS profila](img/14-pa-ssl-profila.png)

![Kudeaketa-ezarpenak](img/15-pa-kudeaketa-ezarpenak.png)

Commit-aren ondoren PA-k fabrikako ziurtagiri autosinatua erabiltzen jarraitu zuen; web zerbitzaria berrabiarazi arte (gailua berrabiaraziz) ez zuen ziurtagiri berria kargatu.

![Fabrikako ziurtagiria](img/16-pa-it-lehen-ziurtagiria.png)

U1-admin domeinuan sartu ondoren (GPO-a U1-WS-tik jasotzen du), `https://pa-it.talde1.lan` abisurik gabe irekitzen da.

![PA-IT kandatua](img/17-pa-it-kandatua.png)

## 6. SSH: EN-CertServ

Administratzaileak gako bidez autentifikatzen dira (Ed25519, pasahitz-esaldiarekin). Gako publikoa `pki` erabiltzailearen `authorized_keys` fitxategian dago.

![Gakoa sortu](img/18-ssh-keygen.png)

Lehen konexioan zerbitzariaren hatz-marka egiaztatu da: lehen abiaraztean sortutakoaren berdina da (`SHA256:kvjf7ul140…`), beraz benetako zerbitzaria da.

![Gako publikoa kopiatu](img/19-ssh-scp.png)

`sshd` zerbitzuaren gotortzea `/etc/ssh/sshd_config.d/10-gotortzea.conf` fitxategian:

| Aukera | Zertarako |
|---|---|
| `PermitRootLogin no` | root ezin da zuzenean sartu |
| `PasswordAuthentication no`, `KbdInteractiveAuthentication no` | gakoa bakarrik, pasahitzik ez |
| `AllowUsers pki@192.168.112.0/24 pki@192.168.20.10` | `pki` bakarrik, SISTEMAK-etik eta U1-eko administratzailetik |
| `MaxAuthTries 3`, `LoginGraceTime 30` | indar hutsezko erasoak mugatu |
| `X11Forwarding no` | behar ez den funtzionalitatea desgaitu |

![sshd konfigurazioa](img/20-sshd-conf.png)

![sshd -T](img/21-sshd-T.png)

### Probak

Gakoarekin sartzen da (gakoaren pasahitz-esaldia eskatzen du):

![Gakoarekin](img/22-ssh-proba-gakoa.png)

root-ek ezin du sartu:

![root ukatuta](img/23-ssh-proba-root.png)

Pasahitzarekin ezin da sartu:

![Pasahitza ukatuta](img/24-ssh-proba-pasahitza.png)

## 7. MikroTik

### 7.1 Trunk-aren egiaztapena

MikroTik-ak (CRS112, RouterOS 6.49) aurreko erronkako konfigurazioa zuen, jada 110-113 VLANetara egokituta:

| Ataka | Funtzioa |
|---|---|
| `ether1` | Trunk-a klaseko switchera (110-113 etiketatuta, etiketadun trafikoa bakarrik) |
| `ether3` / `ether4` / `ether5` | Sarbidea: 110 BEZEROAK / 111 ZERBITZARIAK / 112 SISTEMAK |
| `vlan112-kudeaketa` | Kudeaketa-IP-a `192.168.112.2`, gateway-a pfSense (`.1`) |

Erabiltzen ez diren atakak desgaituta daude. Bidea (pfSense → rack-eko switcha → zuntza → klaseko switcha → MikroTik) pfSense-tik egiaztatu da:

![pfSense → MikroTik](img/25-mk-pfsense-ping-mikrotik.png)

Administrazio-ekipoa `ether5`-era konektatuta, IP-a DHCP relay bidez jasotzen du AD-tik. SISTEMAK-eko esparruak helbide bakarra du, administratzailearen ekipoaren MAC-ari lotutako erreserba (`.200`): beste ekipo ezezagun batek ez du IP-rik jasotzen. Esparruari 003 aukera (gateway-a) falta zitzaion eta gehitu egin zen.

![DHCP 003](img/27-mk-dhcp-003.png)

![Erreserba](img/28-mk-dhcp-erreserba.png)

![ipconfig](img/26-mk-pc-ether5-ipconfig.png)

![Probak](img/29-mk-pc-ether5-probak.png)

### 7.2 FQDN eta HTTPS

DNS erregistroa (`mikrotik.talde1.lan` → `192.168.112.2`) eta PKI-ko ziurtagiria, pfSense-ren prozedura berarekin. Ziurtagiria eta gako zifratua Winbox-eko *Files*-era igo eta inportatu dira; `K` (gako pribatua) eta `T` (konfiantzazkoa) markak ditu. Inportatu ondoren fitxategiak ezabatu dira.

![DNS](img/30-mk-dns-mikrotik.png)

![Ziurtagiria sortu](img/31-mk-cert-sortu.png)

![Inportazioa](img/32-mk-cert-import.png)

![Xehetasunak](img/33-mk-cert-detail.png)

Zerbitzuak:

```
/ip service set www-ssl certificate=mikrotik.crt_0 disabled=no address=192.168.112.0/24,192.168.114.0/24
/ip service set winbox address=192.168.112.0/24,192.168.114.0/24
/ip service set ssh address=192.168.112.0/24,192.168.114.0/24
/ip ssh set strong-crypto=yes
```

ssh, www-ssl eta winbox bakarrik daude aktibo, SISTEMAK-etik eta OpenVPN-etik bakarrik; telnet, ftp, www (HTTP) eta api desgaituta. Aldaketak *Safe Mode*-n egin dira, konexioa galduz gero automatikoki desegiteko.

![Zerbitzuak](img/34-mk-ip-service.png)

RouterOS 6-k bere ziurtagiria bakarrik bidaltzen du, katerik gabe; beraz bezeroak Intermediate CA izan behar du bere biltegian. Domeinuko ekipoek GPO bidez dute; domeinutik kanpoko ekipoan eskuz inportatu da (`certutil -user -addstore CA intermediate.crt`).

![MikroTik kandatua](img/35-mk-mikrotik-kandatua.png)

## 8. Web zerbitzaria (DMZ) — HTTPS

EN-ko DMZ-ko web zerbitzaria (`www.talde1.lan`, `192.168.50.10`) Docker edukiontzi batean dago, 8090 atakan eta HTTP soilean. HTTPS emateko, Apache **alderantzizko proxy** gisa konfiguratu da: 443 atakan entzuten du PKI-ko ziurtagiriarekin (`www.talde1.lan`, ikus [PKI dokumentazioa](../pki/README.md)), eta edukiontzira birbidaltzen du; 80 atakan 443-ra berbideratzen du. Edukiontziaren kodea ukitu gabe geratzen da.

![Atakak: Apache 80/443, Docker 8090](img/36-web-ss-portuak.png)

Apache-ren konfigurazioa (`/etc/apache2/sites-available/www-ssl.conf`):

```apache
<VirtualHost *:80>
    ServerName www.talde1.lan
    Redirect permanent / https://www.talde1.lan/
</VirtualHost>

<VirtualHost *:443>
    ServerName www.talde1.lan
    SSLEngine on
    SSLCertificateFile      /etc/apache2/ssl/web-www.crt
    SSLCertificateKeyFile   /etc/apache2/ssl/web-www.key
    SSLCertificateChainFile /etc/apache2/ssl/ca-chain.crt
    ProxyPreserveHost On
    RequestHeader set X-Forwarded-Proto "https"
    ProxyPass        / http://127.0.0.1:8090/
    ProxyPassReverse / http://127.0.0.1:8090/
</VirtualHost>
```

![Apache 443 proxy: curl localhost](img/37-web-apache-curl.png)

**Argitalpena:** pfSense-k WAN-eko 80/443 DMZ-ko web zerbitzarira (`EN_WEB` = `192.168.50.10`) birbidaltzen ditu (DNAT), bakoitza bere firewall-arau elkartuarekin.

**DNS:** `www.talde1.lan` → `192.168.50.10` AD-n. Domeinuko eta VPN-eko ekipoek izen bidez sartzen dira, nabigatzaileak PKI-an konfiantza duenez candadoarekin. Kanpotik (klaseko 74 sarea) ezin da izenez automatikoki, haiek ez baitute gure DNS-a erabiltzen; `hosts` fitxategian `192.168.74.1  www.talde1.lan` jarrita sartzen dira (DNS publikoaren simulazioa).

![www.talde1.lan HTTPS candadoarekin](img/38-web-kandatua.png)

## 9. Egiteko

- PA-OT eta DMZ-ko zerbitzariak (2. zatia) prozedura berarekin.
- Taldekide bakoitzak bere SSH gakoa izatea.
- Firefox-en *Importar raíces empresariales* GPO bidez.
