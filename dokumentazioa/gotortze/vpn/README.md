# EN ↔ U1 – IPsec VPN

EN egoitza (pfSense) eta U1 egoitza (PA-IT) IPsec tunel baten bidez lotuta daude (site-to-site). Tunela klaseko sarearen gainetik doa (vlan74).

Autentifikazioa ziurtagiri digitalekin egiten da. Ziurtagiriak enpresako PKI-ak sinatu ditu (ikus [PKI dokumentazioa](../pki/README.md)).

## 1. Diseinua

| | EN | U1 |
|---|---|---|
| Suebakia | pfSense | PA-IT (Palo Alto) |
| Peer IP (WAN) | `192.168.74.1` | `192.168.74.2` |
| IKE identitatea (FQDN) | `vpn-en.talde1.lan` | `vpn-u1.talde1.lan` |
| Ziurtagiria | `VPN-EN` | `VPN-U1` |

| Parametroa | Balioa |
|---|---|
| IKE bertsioa | IKEv2 |
| Phase 1 | AES-256-CBC, SHA256, DH 14. taldea, 28800 s |
| Phase 2 (ESP) | AES-256-GCM, PFS 14. taldea, 3600 s |
| Autentifikazioa | Ziurtagiriak (RSA) |

**Zergatik ziurtagiriak eta ez PSK?**

- PSK gako partekatu bat da, eta bi muturretan idatzita dago. Norbaitek kopiatzen badu, ez dugu jakingo.
- Ziurtagiriekin, mutur bakoitzak bere gako pribatua du. Beste muturrak CA-ren bidez egiaztatzen du nor den.
- Ziurtagiri bat arriskuan badago, errebokatu daiteke bestea ukitu gabe.

## 2. Ziurtagiriak pfSense-n (EN)

**CA-ak:** *System › Cert Manager › CAs › Add › Import an existing Certificate Authority*.

Root eta Intermediate CA-ren ziurtagiriak bakarrik inportatzen dira, gako pribaturik gabe. Horregatik *Internal* zutabean ✗ agertzen da. Intermediate-ren *Issuer* `Ugarteburu-Root-CA` da. Horrek esan nahi du katea ondo lotuta dagoela.

![pfSense CA-ak](img/01-pfsense-cas.png)

**Ziurtagiria:** *Certificates › Add/Sign › Import an existing Certificate*. `vpn-en.crt` eta bere gakoa inportatzen dira. *Issuer*: `Ugarteburu-Intermediate-CA`.

![pfSense ziurtagiria](img/02-pfsense-ziurtagiria.png)

## 3. Ziurtagiriak PA-IT-n (U1)

*Device › Certificate Management › Certificates › Import*, ordena honetan:

1. `Ugarteburu-Root-CA`: `ca.crt`, gakorik gabe.
2. `Ugarteburu-Intermediate-CA`: `intermediate.crt`, gakorik gabe.
3. `VPN-U1`: `vpn-u1.crt`, gako zifratuarekin (*Import Private Key* markatuta). *Block Private Key Export* ere markatu dugu, gakoa suebakitik atera ez dadin.

![Inportazioa](img/03-pa-inportatu.png)

Zerrendan hiru ziurtagiriak bata bestearen barruan agertzen dira (Root › Intermediate › VPN-U1). CA-ek ez dute gakorik, eta VPN-U1-ek badu.

![PA ziurtagiriak](img/04-pa-ziurtagiriak.png)

**Ziurtagiri-profila:** `VPN-EN-Profile` sortu dugu, Root eta Intermediate CA-ekin. Phase 1-ean erabiltzen da, pfSense-ren ziurtagiria zein CA-rekin egiaztatu behar den esateko.

CRL eta OCSP desgaituta daude, PKI-ak oraindik ez duelako CRL-rik argitaratzen.

![Ziurtagiri-profila](img/05-pa-ziurtagiri-profila.png)

## 4. Tunela pfSense-n (EN)

### 4.1 WAN interfazea

Klaseko sarea pribatua da (`192.168.74.0/24`). Beraz, PA-IT-ren IKE trafikoa IP pribatu batetik dator.

Horregatik WAN interfazean *Block private networks* desgaitu dugu. Bestela pfSense-k IKE paketeak blokeatuko lituzke VPN arauetara iritsi baino lehen.

![WAN interfazea](img/06-pfsense-wan.png)

### 4.2 Phase 1

*VPN › IPsec › Tunnels › Add P1*. IKEv2, WAN interfazea eta urruneko gateway-a `192.168.74.2`.

![Phase 1 orokorra](img/07-pfsense-p1-orokorra.png)

Autentifikazioa *Mutual Certificate* da. Bi identitateak FQDN motakoak dira, eta ziurtagirien SAN-arekin bat datoz. pfSense-k PA-IT-ren ziurtagiria Intermediate CA-rekin egiaztatzen du.

![Phase 1 autentifikazioa](img/08-pfsense-p1-autentifikazioa.png)

Proposamen bakarra dago: AES-256, SHA256 eta DH 14. Horrela negoziazioan ez da algoritmo ahulagorik eskaintzen. Iraupena 28800 s da.

![Phase 1 zifratzea](img/09-pfsense-p1-zifratzea.png)

NAT-T *Auto* moduan dago. DPD gaituta dago, urruneko muturra erortzen bada konturatzeko.

![Phase 1 aukera aurreratuak](img/10-pfsense-p1-aurreratua.png)

![Phase 1 zerrenda](img/11-pfsense-p1-zerrenda.png)

### 4.3 Phase 2

Phase 2 bakoitzak tuneletik pasa daitekeen sare-bikote bat definitzen du.

U1-eko administratzaileak EN-ko administratzaileen sarbide bera behar du. Horregatik hiru VLAN lotu ditugu U1-IT sarearekin (`192.168.20.0/24`). Q sarea kanpoan geratzen da, isolatuta dagoelako.

| Phase 2 | Local (EN) | Remote (U1) |
|---|---|---|
| ZERBITZARIAK - U1-IT | `192.168.111.0/24` | `192.168.20.0/24` |
| SISTEMAK - U1-IT | `192.168.112.0/24` | `192.168.20.0/24` |
| BEZEROAK - U1-IT | `192.168.110.0/24` | `192.168.20.0/24` |

![Phase 2 orokorra](img/12-pfsense-p2-orokorra.png)

![Phase 2 sareak](img/13-pfsense-p2-sareak.png)

ESP protokoloa AES256-GCM-rekin bakarrik erabiltzen da. GCM-k zifratzea eta osotasuna batera ematen ditu. Beraz, ez da hash algoritmorik behar. PFS 14. taldea da, eta iraupena 3600 s.

![Phase 2 zifratzea](img/14-pfsense-p2-zifratzea.png)

![Phase 2 zerrenda](img/15-pfsense-p2-zerrenda.png)

Phase 2-ek erabakitzen dute zein sare pasatzen diren tuneletik. Firewall arauek erabakitzen dute nork zer egin dezakeen (ikus 6. atala).

## 5. Tunela PA-IT-n (U1)

Palo Alto-n tunela *route-based* da. Tunel-interfaze bat dago, eta bideratze-taulak erabakitzen du zer trafiko sartzen den tunelean.

### 5.1 Zona eta tunel-interfazea

`VPN-EN` zona sortu dugu (Layer3). Horrela tuneletik datorren trafikoa beste zonetatik bereizten da, eta segurtasun-politikekin kontrola daiteke.

![VPN-EN zona](img/16-pa-zona.png)

`tunnel.1` interfazea `default` enrutatzaile birtualean eta `VPN-EN` zonan dago. Ez du IP helbiderik behar, puntutik puntura doan tunela delako.

![tunnel.1](img/17-pa-tunnel1.png)

### 5.2 Profil kriptografikoak

Parametroak pfSense-n bezala daude:

- `EN-IKE` (Phase 1): group14, sha256, aes-256-cbc, 8 ordu.
- `EN-IPSEC` (Phase 2): ESP, aes-256-gcm, autentifikaziorik gabe, group14, ordu 1.

![IKE profila](img/18-pa-ike-kripto.png)

![IPSec profila](img/19-pa-ipsec-kripto.png)

### 5.3 IKE gateway-a

`GW-EN` gateway-aren ezarpenak:

- IKEv2 bakarrik.
- Interfazea: `ethernet1/1` (WAN, `192.168.74.2`). Peer-a: `192.168.74.1`.
- Autentifikazioa `VPN-U1` ziurtagiriarekin.
- Identitateak FQDN motakoak dira, eta zehatz-mehatz bat etorri behar dute.
- pfSense-ren ziurtagiria `VPN-EN-Profile` profilarekin egiaztatzen da.

![IKE gateway orokorra](img/20-pa-ike-gw-orokorra.png)

Modu pasiboa desgaituta dago. Horrela bi muturrek has dezakete tunela. NAT-T ez da behar, bi WAN-en artean ez dagoelako NAT-ik. *Liveness check*-a 5 segundoan behin egiten da.

![IKE gateway aukera aurreratuak](img/21-pa-ike-gw-aurreratua.png)

![IKE gateway zerrenda](img/22-pa-ike-gw-zerrenda.png)

### 5.4 IPSec tunela eta Proxy ID-ak

`VPN-EN` tunelak hiru elementu lotzen ditu: `tunnel.1`, `GW-EN` eta `EN-IPSEC`.

![IPSec tunela](img/23-pa-ipsec-tunela.png)

Proxy ID-ak pfSense-ko Phase 2-en berdinak dira, baina alderantziz (hemen U1 da lokala). Bat ez badatoz, Phase 2 hori ez da altxatzen.

![Proxy ID-ak](img/24-pa-proxy-id.png)

### 5.5 Bideratzea

EN-ko hiru sareetarako ibilbide estatikoak `tunnel.1`-etik doaz, hurrengo jauzirik gabe.

![Ibilbidea](img/25-pa-ibilbidea.png)

![Ibilbideak](img/26-pa-ibilbideak.png)

### 5.6 Segurtasun-politikak

Bi arau gehitu ditugu, *interzone-default*-en gainean:

- `U1-ADMIN-EN`: U1-eko administratzailea (`192.168.20.10`) → `VPN-EN`.
- `EN-SISTEMAK-U1`: EN-ko SISTEMAK (`192.168.112.0/24`) → `U1-IT`.

U1-eko beste ekipoek ezin dute EN-ra sartu.

![Segurtasun-arauak](img/27-pa-segurtasun-arauak.png)

## 6. Firewall arauak pfSense-n (IPsec)

Tuneletik pfSense-ra sartzen den trafikoa *Firewall › Rules › IPsec* fitxako arauek kontrolatzen dute. Lehenespenez dena blokeatuta dago.

PA-IT-ko arauaren berdina gehitu dugu: U1-eko administratzaileak (`192.168.20.10`) EN-ko sareetara sar daiteke. Beste norabidean ez da araurik behar, SISTEMAK sareak jada sarbide osoa duelako.

![IPsec araua](img/30-pfsense-ipsec-araua.png)

## 7. Egiaztapena

pfSense-n:

- Phase 1 *Established* dago, ziurtagiri bidez autentifikatuta (`vpn-en.talde1.lan` ↔ `vpn-u1.talde1.lan`).
- Phase 2 *Installed* dago, AES-GCM 256-rekin.
- Beste Phase 2-ak trafikoa dagoenean altxatzen dira.

![pfSense egoera](img/28-pfsense-egoera.png)

PA-IT-n IKE eta tunela berdez daude.

![PA-IT egoera](img/29-pa-egoera.png)

**Proba U1 → EN:** U1-admin-etik EN-CertServ-era (`192.168.111.20`) ping egin dugu. TTL=62 da. Horrek erakusten du paketeak bi suebakietatik pasatu direla.

![Ping proba](img/31-proba-ping.png)

**Proba EN → U1:** EN-ko SISTEMAK saretik (`sistemak-win`) U1-admin-era (`192.168.20.10`) ping egin dugu. Windows-ek ICMP sarrera blokeatzen du. Horregatik U1-admin-en arau bat gehitu dugu:

```
netsh advfirewall firewall add rule name="ICMP-in" protocol=icmpv4:8,any dir=in action=allow
```

TTL=126 da. Windows-ek 128 balioarekin bidaltzen ditu paketeak, eta suebaki bakoitzak 1 kentzen dio.

![Ping proba EN → U1](img/32-proba-ping-en-u1.png)

Trafiko horrek SISTEMAK-en Phase 2-a altxatu du. Orain bi Phase 2 daude *Installed* egoeran (111 eta 112), eta pakete-kontagailuak igotzen ari dira.

![pfSense egoera bi Phase 2-ekin](img/33-pfsense-egoera-bi-p2.png)

## 8. Active Directory-ren erreplikazioa tuneletik

U1-eko Windows Server-a (U1-WS, `192.168.20.20`) `talde1.lan` domeinuaren bigarren domeinu-kontrolatzailea da (ikus [Active Directory dokumentazioa](../../oinarriak/active-directory/README.md)).

Bi DC-ek tuneletik erreplikatzen dute. Horretarako AD-ren portuak bi DC-en artean bakarrik ireki ditugu. Beste ekipoek ezin dute portu horietara sartu.

Portuak `AD_PORTUAK` aliasean daude:

- DNS (53)
- Kerberos (88, 464)
- RPC (135 eta 49152-65535 tarte dinamikoa)
- LDAP (389, 636)
- SMB (445)
- Katalogo globala (3268-3269)
- NTP (123)

### 8.1 pfSense

`U1_DC` aliasa sortu dugu U1-eko DC-arentzat. `EN_DC` aliasa lehendik zegoen.

![U1_DC aliasa](img/34-pfsense-alias-u1dc.png)

**IPsec fitxa** (U1-etik datorren trafikoa): `U1_DC` → `EN_DC`, TCP/UDP, `AD_PORTUAK`.

![IPsec arauak](img/35-pfsense-ipsec-ad.png)

**VLAN111_ZERBITZARIAK fitxa** (EN-ko DC-tik U1-era): `EN_DC` → `U1_DC`, TCP/UDP, `AD_PORTUAK`. Arau hau beharrezkoa da, ZERBITZARIAK sareak lehenespenez Internetera bakarrik irten daitekeelako.

![VLAN111 arauak](img/36-pfsense-vlan111-ad.png)

### 8.2 PA-IT

pfSense-ko aliasaren baliokidea zerbitzu-objektuekin sortu dugu:

- `AD-TCP` eta `AD-UDP`: helmuga-portuak bakarrik (jatorrizko portua ausazkoa da).
- `AD-PORTUAK` taldea: bi objektuak batzen ditu.

![AD-TCP](img/37-pa-ad-tcp.png)

![AD-UDP](img/38-pa-ad-udp.png)

![AD-PORTUAK taldea](img/39-pa-ad-portuak.png)

Bi arau daude, norabide bakoitzeko bat, *interzone-default*-en gainean:

- `U1-DC-EN-DC`: U1-IT `192.168.20.20` → VPN-EN `192.168.111.10`, `AD-PORTUAK` zerbitzua.
- `EN-DC-U1-DC`: VPN-EN `192.168.111.10` → U1-IT `192.168.20.20`, `AD-PORTUAK` zerbitzua.

![Arauaren zerbitzua](img/40-pa-ad-araua-zerbitzua.png)

![AD arauak](img/41-pa-ad-arauak.png)

### 8.3 Egiaztapena

U1-WS-tik egiaztatu dugu:

- Domeinuaren izena EN-ko DC-aren bidez ebazten da.
- LDAP (389) eta SMB (445) portuak tuneletik erabil daitezke.

![AD portuen proba](img/42-proba-ad-portuak.png)
