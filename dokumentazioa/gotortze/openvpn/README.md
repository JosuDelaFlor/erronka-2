# EN – Urrutiko atzipena (OpenVPN)

Ugarteburu Torlojuak · 2. Erronka

Taldeko administratzaileak (nerea, josu, iker) kanpotik EN-ko sarera konekta daitezen, OpenVPN zerbitzari bat dago pfSense-n. Autentifikazioa bikoitza da:

1. **Ziurtagiria:** erabiltzaile bakoitzak bere bezero-ziurtagiria du, enpresako PKI-ak sinatuta (ikus [PKI dokumentazioa](../pki/README.md)).
2. **Erabiltzailea eta pasahitza:** Active Directory-ren kontra egiaztatzen dira, `VPN-Erabiltzaileak` taldeko kideek bakarrik sar daitezke.

Gainera, pfSense-k egiaztatzen du ziurtagiriaren CN-a eta erabiltzaile-izena berdinak direla: ziurtagiri bat lapurtuta ere, jabearen pasahitzik gabe ezin da erabili, eta beste baten pasahitzarekin ere ez.

| Parametroa | Balioa |
|---|---|
| Zerbitzaria | pfSense WAN, `192.168.74.1`, UDP 1194 |
| Modua | Remote Access (SSL/TLS + User Auth), tun |
| Tunel-sarea | `192.168.114.0/24` |
| Zifratzea | AES-256-GCM, SHA256, ECDH |
| Kontrol-kanala | tls-crypt (TLS Encryption and Authentication) |
| Sare eskuragarriak | `192.168.110-113.0/24`, DMZ `192.168.50.0/24` (split tunnel) |
| DNS | `192.168.111.10`, `talde1.lan` domeinuarentzat bakarrik |

## 1. Active Directory

`Ugarteburu` OU-an sortu dira hiru erabiltzaileak, `VPN-Erabiltzaileak` segurtasun-talde globala eta `svc-pfsense` zerbitzu-kontua (pasahitza ez da iraungitzen). Zerbitzu-kontu horrek pfSense-ri AD-n bilaketak egiteko balio du, administratzaile-kontua erabili gabe.

![Ugarteburu OU](img/01-ad-ou-ugarteburu.png)

![VPN-Erabiltzaileak taldea](img/02-ad-taldea.png)

![DN-ak](img/03-ad-dn.png)

## 2. Ziurtagiriak

Zerbitzariaren ziurtagiriak `serverAuth` bakarrik du, eta bezeroenak `clientAuth` bakarrik. Horrela bezero-ziurtagiri batekin ezin da zerbitzaria ordezkatu. Bezero-ziurtagirien CN-a erabiltzaile-izena da.

| Ziurtagiria | CN | EKU | Iraupena |
|---|---|---|---|
| `ovpn-server` | `openvpn.talde1.lan` (SAN: IP `192.168.74.1`) | serverAuth | 825 egun |
| `ovpn-nerea` / `ovpn-josu` / `ovpn-iker` | `nerea` / `josu` / `iker` | clientAuth | 365 egun |

![Luzapenak](img/04-ovpn-ext.png)

![Bezero-ziurtagiriak sinatzen](img/05-ovpn-bezero-sinadura.png)

![Egiaztapena](img/06-ovpn-verify.png)

![EKU-ak](img/07-ovpn-eku.png)

pfSense-ra eramateko, ziurtagiria eta gakoa PKCS#12 fitxategi batean bildu dira (pasahitzarekin babestuta), SISTEMAK-WIN-era `scp` bidez kopiatu eta *Cert Manager*-en inportatu. Gero fitxategiak ezabatu ziren bi aldeetan.

```bash
openssl pkcs12 -export -inkey private/ovpn-$c.key -in certs/ovpn-$c.crt \
  -name ovpn-$c -out /home/pki/ovpn/ovpn-$c.p12 -passout env:P
```

![PKCS#12 inportazioa](img/08-pfsense-p12-inportatu.png)

![Ziurtagiriak pfSense-n](img/09-pfsense-ovpn-ziurtagiriak.png)

## 3. LDAP autentifikazio-zerbitzaria

*System › User Manager › Authentication Servers*: `AD-talde1`, WS-TALDE1-en kontra (`192.168.111.10:389`). Bilaketa `OU=Ugarteburu` edukiontzian egiten da, eta *Extended query*-ak taldeko kideak bakarrik onartzen ditu:

```
memberOf=CN=VPN-Erabiltzaileak,OU=Ugarteburu,DC=talde1,DC=lan
```

![LDAP 1](img/10-ldap-1.png)

![LDAP 2](img/11-ldap-2.png)

![LDAP 3](img/12-ldap-3.png)

Proba (*Diagnostics › Authentication*): `nerea` autentifikatzen da; taldetik kanpoko erabiltzaile batek huts egiten du.

![Proba ondo](img/13-ldap-proba-ondo.png)

![Proba gaizki](img/14-ldap-proba-gaizki.png)

## 4. OpenVPN zerbitzaria

![Orokorra](img/15-ovpn-server-1.png)

![Endpoint-a](img/16-ovpn-server-2.png)

Aukera kriptografikoak:

- **TLS Key (tls-crypt):** kontrol-kanala zifratu eta autentifikatzen du. Gakoa ez duen edonoren paketeak erantzunik gabe baztertzen dira, beraz portua irekita egonda ere zerbitzua ez da ikusten (eskaneatzeen eta DoS-en aurka).
- **Peer Certificate Authority:** Intermediate CA.
- **Certificate Depth: Two:** katean Intermediate CA dago.
- **Strict User-CN Matching:** ziurtagiriaren CN-a = erabiltzailea.
- **Client Certificate Key Usage Validation:** `clientAuth` EKU-a duten ziurtagiriak bakarrik.

![Kriptografia](img/17-ovpn-server-3.png)

![Algoritmoak](img/18-ovpn-server-4.png)

![CN egiaztapena](img/19-ovpn-server-5.png)

Tunelaren ezarpenak: *split tunnel* (Internet trafikoa ez da VPN-tik pasatzen), gehienez 3 konexio, konpresioa ukatuta (VORACLE motako erasoen aurka), bezeroen arteko komunikaziorik ez eta erabiltzaile bakoitzeko saio bakarra.

![Tunela](img/20-ovpn-server-6.png)

![Tunela 2](img/21-ovpn-server-7.png)

Bezeroei DNS zerbitzaria (AD) eta `talde1.lan` domeinua bidaltzen zaizkie, FQDN bidez administratu ahal izateko.

![DNS](img/22-ovpn-server-8.png)

![tls-crypt](img/23-ovpn-tls-crypt.png)

![Zerbitzaria](img/24-ovpn-server-zerrenda.png)

## 5. Firewall arauak

**WAN:** UDP 1194 klaseko saretik (`192.168.74.0/24`, gure "Internet"-a) WAN helbidera.

![WAN araua](img/25-arauak-wan.png)

**OpenVPN fitxa:** konektatutako administratzaileek SISTEMAK-ek duen sarbide bera dute.

![OpenVPN araua](img/26-arauak-openvpn.png)

EN-CertServ-eko SSH-an VPN sarea gehitu da `AllowUsers`-en:

![AllowUsers](img/27-ssh-allowusers.png)

## 6. Bezero-fitxategiak

`openvpn-client-export` paketea instalatzean errore bat agertu zen: pfSense-k 2.7.0 bertsioaren pakete-biltegia erabiltzen zuen, eta han paketeak PHP berriago baterako konpilatuta daude. pfSense eguneratu gabe konpontzeko, eguneraketa-adarra **PREVIOUS version (v2.6.0)**-ra aldatu da (*System › Update › Update Settings*), instalatutako bertsioaren biltegia erabil dezan. Ez da sistema eguneratu. Aldaketa aurretik konfigurazioaren babeskopia (XML) atera zen.

![Eguneraketa-adarra](img/38-pkg-adarra.png)

![Paketea](img/39-pkg-bilatu.png)

![Instalatuta](img/40-pkg-instalatuta.png)

*VPN › OpenVPN › Client Export*: WAN interfazearen IP-a, `verify-x509-name` automatikoa eta DNS zatiturako bi aukera gehigarri:

```
dns server 1 address 192.168.111.10
dns server 1 resolve-domains talde1.lan
```

`dns` aukerak Windows-en NRPT arau bat sortzen du, `*.talde1.lan` bakarrik AD-ra bidaltzeko. OpenVPN 2.6 edo berriagoa behar du bezeroan; horregatik *Most Clients* (inline `.ovpn`) fitxategia erabiltzen da OpenVPN Community 2.6+ bezeroarekin, eta ez paketeak dakarren 2.5.8 instalatzailea.

![Export ezarpenak](img/41-export-dns.png)

Bezero bakoitza bere ziurtagiriarekin agertzen da (*Certificate with External Auth*: erabiltzailea AD-n dago). Fitxategi bakoitzak jabearen gako pribatua dauka, beraz modu pribatuan banatzen da eta ez da GitHub-era igotzen.

![Bezeroak](img/42-export-bezeroak.png)

Paketea instalatu aurretik, fitxategiak eskuz sortu ziren EN-CertServ-en, paketeak egiten duena bera eginez (CA katea, bezero-ziurtagiria eta gakoa, eta `tls-crypt` gakoa `.ovpn` bakarrean). Proba (7. atala) fitxategi horrekin egin zen; paketea instalatu ondoren eskuzko fitxategiak ezabatu dira.

## 7. Probak

Klaseko saretik (`192.168.74.0/24`) dagoen PC batetik, OpenVPN GUI-rekin, `nerea` erabiltzailearekin:

![Konektatuta](img/29-proba-konektatuta.png)

![IP-a](img/30-proba-ipconfig.png)

![Ping](img/31-proba-ping.png)

`nslookup`-ek beti adaptadore nagusiaren DNS-a erabiltzen du (Google), baina AD-ren DNS-a tuneletik erantzuten du:

![nslookup Google](img/32-proba-nslookup-google.png)

![nslookup AD](img/33-proba-nslookup-ad.png)

DNS zatituarekin Windows-en ebazlea `talde1.lan` AD-ra bidaltzen du:

![DNS zatitua](img/34-proba-dns-zatitua.png)

pfSense-ren WebGUI-a FQDN bidez VPN-tik administratzen da (`admin@192.168.114.2`). PC hau domeinutik kanpo dagoenez, Root CA eskuz inportatu da erabiltzailearen biltegian (`certutil -user -addstore Root ca.crt`); domeinuko ekipoek GPO bidez jasotzen dute.

![WebGUI VPN-tik](img/35-proba-webgui-vpn.png)

![Ziurtagiria](img/36-proba-ziurtagiria-vpn.png)

![Kandatua](img/37-proba-kandatua.png)

Zerbitzariaren egoera (konexiorik gabe):

![Egoera](img/28-ovpn-egoera-hutsik.png)

### 7.1 Linux bezeroa (josu)

`josu` erabiltzailea Linux batetik (Parrot OS, OpenVPN 2.6.14) konektatu da, pfSense-tik esportatutako fitxategiarekin:

```bash
sudo apt install openvpn
sudo openvpn --config pfsense-UDP4-1194-josu-config.ovpn
```

Linux-en `dns` aukerak ez du DNS-a automatikoki konfiguratzen, beraz DNS zatitua `systemd-resolved`-ekin ezartzen da konektatu ondoren:

```bash
sudo resolvectl dns tun0 192.168.111.10
sudo resolvectl domain tun0 '~talde1.lan'
```

Emaitza: `192.168.114.3` IP-a, barne-sareetara (`192.168.111.20`) eta pfSense-ren WebGUI-ra sarbidea.

**Arazo bat eta konponbidea:** lehen saiakeran bezeroak IP-a jaso eta `192.168.114.1` erantzuten zuen, baina ez zen barne-sareetara iristen (pfSense-ko pakete-harrapaketan ez zen ezer agertzen). Log-ean ikusi zen arrazoia:

```
TUN/TAP device tun1 opened
sitnl_send: rtnl: generic error (-17): File exists   (x5)
```

Aurretik NetworkManager-ekin egindako konexio bat (`tun0`) aktibo zegoen oraindik. Konexio berriak `tun1` sortu zuen, eta bost ibilbideak ezin izan ziren gehitu, jada `tun0`-ra zuzenduta baitzeuden. pfSense-k konexio zaharra moztu zuen (erabiltzaile bakoitzeko saio bakarra onartzen da), beraz trafikoa tunel hil batera zihoan. Konponbidea: OpenVPN eta NetworkManager-eko VPN guztiak itxi (`sudo killall openvpn`, `nmcli connection down …`), `tun` interfazerik ez dagoela egiaztatu eta berriro konektatu.

**Ziurtagiriak:** domeinutik kanpoko ekipoa denez, Root eta Intermediate CA-ak `.ovpn` fitxategiko `<ca>` bloketik atera eta sisteman instalatu dira; Firefox-en eskuz inportatu behar dira, bere biltegia erabiltzen baitu.

```bash
sed -n '/<ca>/,/<\/ca>/p' pfsense-UDP4-1194-josu-config.ovpn | grep -v 'ca>' > ugarteburu-ca.crt
sudo cp ugarteburu-ca.crt /usr/local/share/ca-certificates/
sudo update-ca-certificates
```

## 8. Egiteko / hobekuntzak

- iker erabiltzailearekin proba egin eta hiru konexioak batera erakutsi (*Status › OpenVPN*).
- LDAP-a LDAPS-era pasa (DC-arentzat PKI-ko ziurtagiria).
- CRL-a argitaratu eta OpenVPN-en gaitu, ziurtagiriak errebokatu ahal izateko.
- Nerearen gako pribatua eta tls-crypt gakoa txat batean partekatu ziren proban zehar; ingurune erreal batean ziurtagiria errebokatu eta tls-crypt gakoa berritu beharko litzateke.
