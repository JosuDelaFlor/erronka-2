# Zero Trust: portu eta bide irekien probak

Ugarteburu Torlojuak · 2. Erronka

Sare guztietan "dena debekatuta, beharrezkoa bakarrik baimenduta" printzipioa aplikatu da (ikus [pfSense](../pfsense/README.md), [PA-IT](../U1-PA-IT/README.md), [VPN](../vpn/README.md) eta [kudeaketa-atzipena](../kudeaketa-atzipena/README.md)). Dokumentu honek hori **probatzen** du: sare bakoitzeko ekipo batetik beste sareak eta gailuak eskaneatzen dira, eta emaitza espero denarekin alderatzen da. Behar ez den portu edo bide bat agertzen bada, aurkikuntza gisa erregistratzen da, zuzendu eta berriro probatzen da.

## 1. Metodologia

**Tresnak:** `nmap` (Linux-en `sudo apt install nmap`; Windows-en Nmap + Npcap) eta, Windows-en portu bakarra probatzeko, `Test-NetConnection <IP> -Port <portua>`.

**Komandoak:**

```bash
# Zonen arteko proba: portu esanguratsuak, --open GABE (egoera guztiak ikusteko)
nmap -Pn -sT -T4 -p 22,53,80,88,135,389,443,445,3389,5985,8291,8728 <helburua>

# Gailu baten zerbitzuak: TCP portu guztiak, irekiak bakarrik
nmap -Pn -sT -T4 -p- --open <IP>

# UDP portu nagusiak
sudo nmap -Pn -sU -p 53,67,123,161,500,1194,4500 <IP>
```

`-Pn` behar da suebakiek ping-a blokeatzen dutelako; bestela nmap-ek ekipoa itzalita dagoela uste du eta ez du eskaneatzen.

**Emaitzak nola irakurri:**

| nmap egoera | Esanahia | Zonen artean |
|---|---|---|
| `filtered` | Suebakiak paketea baztertzen du | ✅ Hau da espero dena |
| `closed` | Paketea ekipora iritsi da, baina ez dago zerbitzurik | ❌ **Bidea irekita dago** (araurik ez dago blokeatzeko) |
| `open` | Zerbitzua eskuragarri | Espero den zerbitzua bada ✅, bestela ❌ |

Beraz, zonen arteko probetan `closed` ere aurkikuntza da: zerbitzurik ez egon arren, suebakiak bidea uzten du.

**Legenda:** ✅ espero bezala · ❌ aurkikuntza (ikus 10. atala) · ⬜ egiteko

## 2. Kanpotik (WAN, klaseko 74 sarea)

Jatorria: klaseko ekipo bat `192.168.74.0/24` sarean (ez gure `.1`–`.30` tartekoa). Erasotzaile baten egoera simulatzen da, barne-sareetarako ibilbidea eskuz gehituta ere.

| ID | Helburua | Komandoa | Espero dena | Emaitza | Irudia |
|---|---|---|---|---|---|
| W1 | pfSense WAN `192.168.74.1` (TCP) | `nmap -Pn -sT -T4 -p- --open 192.168.74.1` | 80 eta 443 bakarrik (DNAT → EN-Web). 22 ez | ⬜ | `img/01-w1-pfsense-wan.png` |
| W2 | 443-n nork erantzuten du | `curl -kvI https://192.168.74.1 2>&1 \| grep subject` | `CN=www.talde1.lan`: web zerbitzaria, **ez** pfSense-ren WebGUI-a | ⬜ | `img/02-w2-443-ziurtagiria.png` |
| W3 | pfSense WAN (UDP) | `sudo nmap -Pn -sU -p 500,1194,4500 192.168.74.1` | 1194 `open\|filtered` (tls-crypt: gakorik gabe ez du erantzuten). 500/4500 `filtered` (IPsec peer-arentzat bakarrik, `192.168.74.2`) | ⬜ | `img/03-w3-pfsense-udp.png` |
| W4 | PA-IT WAN `192.168.74.2` | `nmap -Pn -sT -T4 --top-ports 1000 192.168.74.2` | Dena `filtered`: WAN interfazeak ez du kudeaketa-profilik | ⬜ | `img/04-w4-pa-it-wan.png` |
| W5 | Barne-sareak, ibilbidea eskuz gehituta | `sudo ip route add 192.168.110.0/22 via 192.168.74.1`<br>`nmap -Pn -sT -p 22,443,445,3389 192.168.111.10,20 192.168.112.1,2` | Dena `filtered`: WAN-ean ez dago sarrera-araurik | ⬜ | `img/05-w5-ibilbidea.png` |
| W6 | U1 barne-sarea, ibilbidea eskuz gehituta | `sudo ip route add 192.168.20.0/24 via 192.168.74.2`<br>`nmap -Pn -sT -p 22,443,445,3389 192.168.20.1,10,20` | Dena `filtered` | ⬜ | `img/06-w6-ibilbidea-u1.png` |

Amaitzean ibilbideak ezabatu: `sudo ip route del 192.168.110.0/22` eta `sudo ip route del 192.168.20.0/24`.

## 3. BEZEROAK (VLAN 110)

Jatorria: Erabiltzaile1 (Windows, domeinuan, `192.168.110.100`–`149`).

| ID | Helburua | Komandoa | Espero dena | Emaitza | Irudia |
|---|---|---|---|---|---|
| B1 | DC `192.168.111.10` | `nmap -Pn -sT -T4 --top-ports 1000 --open 192.168.111.10` | AD portuak bakarrik (53, 88, 135, 389, 445, 464, 636, 3268, 3269). 3389 (RDP) eta 5985 (WinRM) ez | ⬜ | `img/07-b1-dc.png` |
| B2 | EN-CertServ `192.168.111.20` | `nmap -Pn -sT -T4 -p 22,80,443 192.168.111.20` | `filtered` | ⬜ | `img/08-b2-certserv.png` |
| B3 | SISTEMAK (pfSense, MikroTik, admin) | `nmap -Pn -sT -T4 -p 22,80,443,3389,8291 192.168.112.1,2,200` | `filtered` | ⬜ | `img/09-b3-sistemak.png` |
| B4 | pfSense bere sarean `192.168.110.1` | `nmap -Pn -sT -T4 -p 22,80,443 192.168.110.1` | `filtered`: WebGUI-a ez da BEZEROAK-etik ikusten | ⬜ | `img/10-b4-pfsense-110.png` |
| B5 | DMZ, EN-Web `192.168.50.10` | `nmap -Pn -sT -T4 -p 22,80,443,8090 192.168.50.10` | `filtered` (DMZ barne-sareen artean dago) | ⬜ | `img/11-b5-dmz.png` |
| B6 | Q `192.168.113.0/24` | `nmap -Pn -sT -T4 -p 22,80,443,445 192.168.113.1-254` | `filtered` | ⬜ | `img/12-b6-q.png` |
| B7 | U1 `192.168.20.0/24` | `nmap -Pn -sT -T4 -p 22,443,445,3389 192.168.20.1,10,20` | `filtered` | ⬜ | `img/13-b7-u1.png` |
| B8 | Internet | `curl -I https://www.google.com` | Erantzuna jasotzen da | ⬜ | `img/14-b8-internet.png` |

## 4. ZERBITZARIAK (VLAN 111)

Jatorria: EN-CertServ (`192.168.111.20`, Ubuntu).

| ID | Helburua | Komandoa | Espero dena | Emaitza | Irudia |
|---|---|---|---|---|---|
| Z1 | EN-ko beste sareak | `nmap -Pn -sT -T4 -p 22,80,443,445,3389,8291 192.168.110.100 192.168.112.1,2,200 192.168.50.10` | `filtered` | ⬜ | `img/15-z1-barne-sareak.png` |
| Z2 | U1 | `nmap -Pn -sT -T4 -p 22,443,445,3389 192.168.20.1,10,20` | `filtered` (AD portuak bi DC-en artean bakarrik) | ⬜ | `img/16-z2-u1.png` |
| Z3 | Internet: baimendutakoa | `curl -I https://deb.debian.org` | Erantzuna jasotzen da (HTTPS) | ⬜ | `img/17-z3-internet-ok.png` |
| Z4 | Internet: baimendu gabekoa | `nc -zv -w 3 portquiz.net 8080`<br>`nc -zv -w 3 portquiz.net 22` | Timeout: DNS, NTP eta HTTP/HTTPS bakarrik baimenduta (portquiz.net-ek portu guztietan erantzuten du) | ⬜ | `img/18-z4-internet-ukatua.png` |

## 5. SISTEMAK (VLAN 112)

Jatorria: administrazio-ekipoa (`192.168.112.200`). Sare honek sarbide osoa du; hemen egiaztatzen dena da **gailu bakoitzak behar dituen zerbitzuak bakarrik** dituela irekita.

| ID | Helburua | Komandoa | Espero dena | Emaitza | Irudia |
|---|---|---|---|---|---|
| S1 | pfSense `192.168.112.1` | `nmap -Pn -sT -T4 -p- --open 192.168.112.1` | 443 (WebGUI). 53 DNS Resolver aktibo badago. 22 ez (SSH desgaituta badago) | ⬜ | `img/19-s1-pfsense.png` |
| S2 | MikroTik `192.168.112.2` | `nmap -Pn -sT -T4 -p- --open 192.168.112.2` | 22, 443, 8291 bakarrik. 21, 23, 80, 8728 ez | ⬜ | `img/20-s2-mikrotik.png` |
| S3 | EN-CertServ `192.168.111.20` | `nmap -Pn -sT -T4 -p- --open 192.168.111.20` | 22 bakarrik | ⬜ | `img/21-s3-certserv.png` |
| S4 | EN-Web `192.168.50.10` | `nmap -Pn -sT -T4 -p- --open 192.168.50.10` | 80 eta 443 (Apache). 8090 (edukiontzia) **ez**: Apache-ren atzean egon behar du | ⬜ | `img/22-s4-web.png` |
| S5 | DC `192.168.111.10` | `nmap -Pn -sT -T4 --top-ports 1000 --open 192.168.111.10` | AD portuak; 3389 aktibo badago, SISTEMAK-etik bakarrik onargarria | ⬜ | `img/23-s5-dc.png` |

## 6. Q (VLAN 113)

Jatorria: Q sarean IP finkoa duen ekipo bat (adibidez `192.168.113.50/24`, gateway `192.168.113.1`). Ekiporik ez badago, VM bat aldi baterako VLAN 113-ra konektatzen da.

| ID | Helburua | Komandoa | Espero dena | Emaitza | Irudia |
|---|---|---|---|---|---|
| Q1 | EN-ko sareak | `nmap -Pn -sT -T4 -p 22,53,80,443,445 192.168.111.10,20 192.168.112.1,2 192.168.50.10` | `filtered` | ⬜ | `img/24-q1-barne-sareak.png` |
| Q2 | pfSense bere sarean `192.168.113.1` | `nmap -Pn -sT -T4 -p 22,53,80,443 192.168.113.1` | `filtered` | ⬜ | `img/25-q2-pfsense.png` |
| Q3 | Internet | `curl -I -m 5 https://www.google.com` | Ez du erantzunik (SNAT-ik ez eta dena blokeatuta) | ⬜ | `img/26-q3-internet.png` |

## 7. DMZ (EN)

Jatorria: EN-Web (`192.168.50.10`). Web zerbitzaria arriskutsuena da (Internetetik eskuragarri dago), beraz erasotzaile batek kontrolatuko balu ere ezin luke barne-sarera salto egin.

| ID | Helburua | Komandoa | Espero dena | Emaitza | Irudia |
|---|---|---|---|---|---|
| D1 | EN-ko barne-sareak | `nmap -Pn -sT -T4 -p 22,53,88,389,443,445,3389 192.168.111.10,20 192.168.112.1,2,200 192.168.110.100` | `filtered` | ⬜ | `img/27-d1-barne-sareak.png` |
| D2 | pfSense `192.168.50.1` | `nmap -Pn -sT -T4 -p 22,80,443 192.168.50.1` | `filtered`: WebGUI-a ez da DMZ-tik ikusten | ⬜ | `img/28-d2-pfsense.png` |
| D3 | U1 | `nmap -Pn -sT -T4 -p 22,443,445,3389 192.168.20.1,10,20` | `filtered` | ⬜ | `img/29-d3-u1.png` |
| D4 | Internet | `curl -I https://deb.debian.org` | Erantzuna jasotzen da | ⬜ | `img/30-d4-internet.png` |

## 8. OpenVPN (192.168.114.0/24)

Jatorria: OpenVPN-era konektatutako taldekide baten ekipoa.

| ID | Helburua | Komandoa | Espero dena | Emaitza | Irudia |
|---|---|---|---|---|---|
| V1 | Kudeaketa-zerbitzuak | `nmap -Pn -sT -T4 -p 22,443,8291 192.168.112.1,2 192.168.111.20` | SISTEMAK-en berdina: pfSense 443, MikroTik 22/443/8291, CertServ 22 | ⬜ | `img/31-v1-kudeaketa.png` |
| V2 | *Split tunnel* | `tracert -d 8.8.8.8` (Linux: `traceroute -n`) | Lehen jauzia **ez** da `192.168.114.1`: Internet trafikoa ez da tuneletik pasatzen | ⬜ | `img/32-v2-split-tunnel.png` |

Ziurtagiririk gabeko edo beste erabiltzaile baten pasahitzarekin egindako konexio-saiakerak [OpenVPN dokumentazioan](../openvpn/README.md) daude probatuta.

## 9. U1 (IPsec tuneletik)

| ID | Jatorria → Helburua | Komandoa | Espero dena | Emaitza | Irudia |
|---|---|---|---|---|---|
| U1 | U1-admin (`192.168.20.10`) → EN kudeaketa | `nmap -Pn -sT -T4 -p 22,443,8291 192.168.112.1,2 192.168.111.20` | EN-ko administratzailearen berdina (irakaslearen baldintza): pfSense 443, MikroTik 22/443/8291, CertServ 22 | ⬜ | `img/33-u1-admin-en.png` |
| U2 | U1-WS (`192.168.20.20`) → EN | `Test-NetConnection 192.168.111.10 -Port 389`<br>`Test-NetConnection 192.168.111.20 -Port 22`<br>`Test-NetConnection 192.168.112.1 -Port 443` | 389: `True`. Beste biak: `False` (DC-ra AD portuak bakarrik) | ⬜ | `img/34-u2-u1ws-en.png` |
| U3 | U1-admin → PA-IT `192.168.20.1` | `nmap -Pn -sT -T4 -p 22,80,443 192.168.20.1` | 22 eta 443. 80 ez | ⬜ | `img/35-u3-pa-it.png` |
| U4 | U1-WS → PA-IT / PA-OT | `Test-NetConnection 192.168.20.1 -Port 443`<br>`Test-NetConnection 192.168.20.254 -Port 443` | `False`: kudeaketa `192.168.20.10`-tik bakarrik (*permitted IP*) | ⬜ | `img/36-u4-u1ws-pa.png` |

## 10. Aurkikuntzak eta zuzenketak

Espero ez den emaitza bakoitza hemen erregistratzen da, zuzenketarekin eta proba errepikatuarekin.

| ID | Proba | Aurkitutakoa | Arriskua | Zuzenketa | Berriz probatuta |
|---|---|---|---|---|---|
| A1 | | | | | ⬜ |

**Bereziki begiratu beharrekoak** (konfigurazioa irakurrita, proban egiaztatzeko):

- **U1 → MikroTik (U1 proba):** MikroTik-en `ip service` zerbitzuek `192.168.112.0/24` eta `192.168.114.0/24` bakarrik onartzen dituzte. U1-admin-ek (`192.168.20.10`) ezin izango du sartu, eta horrek irakaslearen baldintza hausten du. Zuzenketa: `/ip service set www-ssl,ssh,winbox address=192.168.112.0/24,192.168.114.0/24,192.168.20.10/32`.
- **EN-Web 8090 (S4 proba):** Docker-ek `-p 8090:…` bezala argitaratzen badu, edukiontzia HTTP soilean eskuragarri dago saretik, Apache saihestuta. Zuzenketa: `127.0.0.1:8090:…` bezala argitaratu, Apache-k bakarrik iritsi dezan.
- **pfSense-ren WebGUI-a beste interfazeetan (B4, D2, Q2 probak):** pfSense-k WebGUI-a interfaze guztietan entzuten du; arauek blokeatzen ez badute, `.1` helbide guztietatik ikusten da. Zuzenketa: sare horietan *This Firewall* helmugarako blokeo-araua, edo `BARNE_SAREAK` aliasak pfSense-ren helbideak barne hartzen dituela egiaztatu.

## 11. Ondorioa

*(Proba guztiak egin ondoren bete: zenbat proba, zenbat aurkikuntza, zuzendu diren eta azken egoera.)*
