# EN – PKI (EN-CertServ)

Ugarteburu Torlojuak · 2. Erronka

Enpresako ziurtagiri guztiak (VPN, HTTPS…) EN egoitzako zerbitzari bakar batean sortzen dira, ez ekipoetan bertan. Bi mailako PKI bat da: Root CA-k Intermediate CA bakarrik sinatzen du, eta Intermediate CA-k sinatzen ditu gainerako ziurtagiri guztiak. Horrela Root CA-ren gakoa ia inoiz ez da erabiltzen, eta Intermediate CA arriskuan egonez gero, errebokatu eta berri bat sor daiteke Root-a ukitu gabe.

| | Root CA | Intermediate CA |
|---|---|---|
| CN | Ugarteburu Root CA | Ugarteburu Intermediate CA |
| Gakoa | RSA 4096, AES-256 bidez zifratuta | RSA 4096, AES-256 bidez zifratuta |
| Iraupena | 20 urte | 10 urte |
| Sinatzen du | Intermediate CA | Zerbitzu-ziurtagiriak (VPN…) |

## 1. Makina birtuala (Proxmox)

EN-CertServ VM 106 da: Ubuntu Server 24.04 LTS, 2 vCPU, 2 GB RAM eta 20 GB-eko diskoa. Sare-txartela trunk-eko bridge-an dago, **VLAN tag 111**-rekin (ZERBITZARIAK). Serie-ataka bat ere gehitu zaio, 3. atalean azaltzen den kontsolarako.

![Proxmox hardwarea](img/01-proxmox-hardware.png)

## 2. Sarea

IP finkoa du ZERBITZARIAK sarean:

| | |
|---|---|
| Hostname | `en-certserv` |
| IP | `192.168.111.20/24` |
| Gateway | `192.168.111.1` (pfSense) |
| DNS | `192.168.111.10` (AD) |

pfSense-ko ZERBITZARIAK arauek DNS, NTP eta HTTP/HTTPS irteera bakarrik baimentzen dute (ikus [pfSense dokumentazioa](../pfsense/README.md)), eta horixe da zerbitzari honek behar duena: paketeak eguneratzea eta ordua sinkronizatzea. ICMP ez dagoenez baimenduta, konexioa `apt update` eta `curl` bidez egiaztatzen da.

Ordua zuzena izatea ezinbestekoa da PKI batean: ordua desegokia bada, ziurtagiriak "oraindik ez baliozko" edo "iraungita" gisa ikus daitezke.

```bash
sudo timedatectl set-timezone Europe/Madrid
timedatectl        # System clock synchronized: yes
```

![IP helbidea](img/02-sarea-ip.png)

![Internetera irteera (HTTPS)](img/02-sarea-curl.png)

## 3. Kontsola serie (xterm.js)

Proxmox-eko noVNC kontsolak ez du testua itsasten uzten, eta konfigurazio-fitxategiak luzeak dira. Horregatik VM-ari serie-ataka bat gehitu zaio (*Hardware › Add › Serial Port 0*) eta `getty` gaitu da bertan:

```bash
sudo systemctl enable serial-getty@ttyS0
```

VM-a berrabiarazi ondoren, *Console › xterm.js* kontsolak kopiatu eta itsatsi ahalbidetzen du. Serie-kontsolak testu luzeak itsastean karaktereak gal ditzakeenez, fitxategiak zati txikitan sortu dira (`cat > … <<'EOF'` lehenengoa, `cat >> …` hurrengoak).

## 4. Direktorio-egitura

Dena `root`-en etxean dago, gainerako erabiltzaileek sarbiderik izan ez dezaten. Gako pribatuen karpetek `700` baimenak dituzte.

```bash
sudo -i
mkdir -p /root/ca/{certs,crl,newcerts,private} /root/ca/intermediate/{certs,crl,csr,newcerts,private}
chmod 700 /root/ca/private /root/ca/intermediate/private
touch /root/ca/index.txt /root/ca/intermediate/index.txt
echo 1000 > /root/ca/serial
echo 1000 > /root/ca/intermediate/serial
```

```
/root/ca/
├── openssl.cnf          Root CA-ren konfigurazioa
├── certs/ca.crt         Root CA-ren ziurtagiria
├── private/ca.key       Root CA-ren gakoa
├── index.txt, serial    Sinatutako ziurtagirien datu-basea
└── intermediate/
    ├── openssl.cnf
    ├── certs/           intermediate.crt, ca-chain.crt, vpn-en.crt, vpn-u1.crt
    ├── private/         intermediate.key, vpn-en.key, vpn-u1.key
    ├── csr/
    └── index.txt, serial
```

## 5. OpenSSL konfigurazioa

`/root/ca/openssl.cnf` fitxategiak CA-ren bideak, `policy_loose` politika (CN derrigorrezkoa, gainerakoak aukerakoak) eta bi luzapen-profil definitzen ditu:

- `v3_ca`: Root CA-rentzat (`CA:true`, `keyCertSign`, `cRLSign`).
- `v3_intermediate_ca`: Intermediate CA-rentzat. `pathlen:0`-k esan nahi du Intermediate-k ezin duela beste CA bat sinatu, azken ziurtagiriak bakarrik.

![openssl.cnf](img/03-openssl-cnf.png)

Intermediate-ren konfigurazioa bera da, bideak eta gakoaren izena aldatuta:

```bash
sed -e 's#^dir *= /root/ca#dir           = /root/ca/intermediate#' \
    -e 's#private/ca.key#private/intermediate.key#' \
    -e 's#certs/ca.crt#certs/intermediate.crt#' \
    /root/ca/openssl.cnf > /root/ca/intermediate/openssl.cnf
```

## 6. Root CA

Gakoa AES-256 bidez zifratuta sortzen da, eta ziurtagiria autosinatua da:

```bash
cd /root/ca
openssl genrsa -aes256 -out private/ca.key 4096
openssl req -config openssl.cnf -key private/ca.key -new -x509 -days 7300 \
  -subj "/C=ES/O=Ugarteburu Torlojuak/CN=Ugarteburu Root CA" -out certs/ca.crt
openssl x509 -in certs/ca.crt -noout -subject -issuer -dates
```

`subject` eta `issuer` berdinak dira, autosinatua delako.

![Root CA](img/04-root-ca.png)

## 7. Intermediate CA

Intermediate-k bere gakoa eta CSR-a sortzen ditu, eta Root CA-k sinatzen du `v3_intermediate_ca` profilarekin. Ondoren `ca-chain.crt` sortzen da (Intermediate + Root), zerbitzuek kate osoa egiazta dezaten.

```bash
openssl genrsa -aes256 -out intermediate/private/intermediate.key 4096
openssl req -config intermediate/openssl.cnf -new -key intermediate/private/intermediate.key \
  -subj "/C=ES/O=Ugarteburu Torlojuak/CN=Ugarteburu Intermediate CA" -out intermediate/csr/intermediate.csr
openssl ca -config openssl.cnf -extensions v3_intermediate_ca -days 3650 -notext \
  -in intermediate/csr/intermediate.csr -out intermediate/certs/intermediate.crt

cat intermediate/certs/intermediate.crt certs/ca.crt > intermediate/certs/ca-chain.crt
openssl verify -CAfile certs/ca.crt intermediate/certs/intermediate.crt
```

![Intermediate CA](img/05-intermediate-ca.png)

## 8. VPN ziurtagiriak

Egoitzen arteko IPsec VPN-a ziurtagiriekin autentifikatzen da, ez gako partekatu batekin (PSK). Muturreko bakoitzak bere ziurtagiria du:

| | pfSense (EN) | PA-IT (U1) |
|---|---|---|
| CN | `vpn-en.talde1.lan` | `vpn-u1.talde1.lan` |
| SAN | `DNS:vpn-en.talde1.lan`, `IP:192.168.74.1` | `DNS:vpn-u1.talde1.lan`, `IP:192.168.74.2` |
| Gakoa | RSA 2048 | RSA 2048 |
| Iraupena | 825 egun | 825 egun |

Luzapenak `.ext` fitxategi batean definitzen dira muturreko bakoitzeko:

- `basicConstraints = CA:FALSE`: azken ziurtagiria da, ezin du beste bat sinatu.
- `keyUsage = digitalSignature, keyEncipherment`: IKE negoziazioan sinatzeko.
- `extendedKeyUsage = serverAuth, clientAuth, 1.3.6.1.5.5.8.2.2`: azken OID-a *IKE Intermediate* da, IPsec-erako. Bi muturrek hasi dezaketenez konexioa, server eta client biak gehitzen dira.
- `subjectAltName`: VPN-ko identitatea (FQDN) eta WAN IP-a. Phase 1-eko identitateak SAN honekin bat etorri behar du.

```bash
cd /root/ca/intermediate
cat > vpn-en.ext <<'EOF'
basicConstraints       = CA:FALSE
keyUsage               = critical, digitalSignature, keyEncipherment
extendedKeyUsage       = serverAuth, clientAuth, 1.3.6.1.5.5.8.2.2
subjectAltName         = DNS:vpn-en.talde1.lan, IP:192.168.74.1
subjectKeyIdentifier   = hash
authorityKeyIdentifier = keyid,issuer
EOF
```

`vpn-u1.ext` berdina da, `DNS:vpn-u1.talde1.lan, IP:192.168.74.2`-rekin.

![VPN luzapenak](img/06-vpn-ext.png)

Gakoa eta CSR-a sortu, eta Intermediate CA-rekin sinatu:

```bash
openssl req -new -newkey rsa:2048 -nodes -keyout private/vpn-en.key -out csr/vpn-en.csr \
  -subj "/C=ES/O=Ugarteburu Torlojuak/OU=EN/CN=vpn-en.talde1.lan"
openssl ca -config openssl.cnf -extfile vpn-en.ext -days 825 -notext \
  -in csr/vpn-en.csr -out certs/vpn-en.crt
```

(Berdin `vpn-u1`-rekin.) Egiaztapena:

```bash
openssl verify -CAfile certs/ca-chain.crt certs/vpn-en.crt certs/vpn-u1.crt
openssl x509 -in certs/vpn-en.crt -noout -subject -ext subjectAltName
openssl x509 -in certs/vpn-u1.crt -noout -subject -ext subjectAltName
```

![VPN ziurtagirien egiaztapena](img/07-vpn-verify.png)

![vpn-en ziurtagiria](img/07-vpn-en.png)

![vpn-u1 ziurtagiria](img/07-vpn-u1.png)

## 9. Ziurtagirien banaketa

Ziurtagiriak eta gakoak ez dira sare irekitik bidaltzen. Testu-formatuan (PEM) daudenez, kopiatu eta itsatsi egiten dira kanal seguru batetik:

- **pfSense (EN):** SISTEMAK sareko administrazio-ekipotik SSH bidez sartzen da EN-CertServ-era, fitxategiak pantailaratzen dira eta pfSense-ren web interfazean itsasten dira. `pki` erabiltzaileak ezin duenez `/root` irakurri, EN-ko fitxategiak aldi baterako `/home/pki/en`-ra kopiatu ziren (`chmod 600` gakoarekin), eta gakoa ezabatu egin zen inportatu ondoren.
- **PA-IT (U1):** U1-eko administrazio-ekipoa (`192.168.20.10`) da PA-IT kudeatu dezakeen bakarra, eta VPN-a oraindik ez dago. Fitxategiak xterm.js kontsolatik kopiatu eta U1-admin-en itsatsi ziren IsardVDI-ren SPICE bisorearen arbelaren bidez. Palo Alto-k gako pribatuak pasahitzarekin zifratuta bakarrik onartzen dituenez, gakoaren kopia zifratu bat sortu zen horretarako, eta inportatu ondoren ezabatu egin zen bai U1-admin-en bai EN-CertServ-en:

```bash
openssl rsa -aes256 -in private/vpn-u1.key -out private/vpn-u1-enc.key
```

Bi suebakiek Root eta Intermediate CA-ren ziurtagiriak bakarrik jasotzen dituzte, gakorik gabe. Inportazioa [VPN dokumentazioan](../vpn/README.md) dago.

## 10. Segurtasuna

- CA-en gako pribatuak AES-256 bidez zifratuta daude eta ez dira inoiz zerbitzaritik ateratzen. Pasahitzak pasahitz-kudeatzailean daude.
- `index.txt` fitxategian sinatutako ziurtagiri guztien erregistroa dago. VPN ziurtagirien lehen bertsioa domeinu okerrarekin sortu zen; ziurtagiri horiek errebokatu (`R`) eta berriak sortu ziren (`V`).

```bash
openssl ca -config openssl.cnf -revoke certs/vpn-en.crt
cat /root/ca/intermediate/index.txt
```

![index.txt](img/08-index.png)

- **Hobekuntza:** CRL-a ez da oraindik inon argitaratzen, eta horregatik suebakietan CRL/OCSP egiaztapena desgaituta dago. Hurrengo pausoa CRL-a EN-CertServ-etik HTTP bidez argitaratzea litzateke.
