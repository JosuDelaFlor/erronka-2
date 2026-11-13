# EN – pfSense konfigurazioa

## 1. Makina birtuala (Proxmox)

pfSense makina birtualak (VM 104) hiru sare-txartel ditu Proxmox-en:

- `vtnet0` → vmbr0: klaseko sarea (WAN).
- `vtnet1` → vmbr1: EN-DMZIT. Ez du portu fisikorik.
- `vtnet2` → vmbr2: MikroTik-erako trunk-a, tag gabe.

![Proxmox-eko sareak](img/01-proxmox-sareak.png)

## 2. VLANak

LAN aldeko lau VLANak `vtnet2`-ren gainean sortu ditugu: 110 BEZEROAK, 111 ZERBITZARIAK, 112 SISTEMAK eta 113 Q.

VLAN bakoitzaren azpisareak VLAN zenbaki bera du. Adibidez: `192.168.110.0/24`, `192.168.111.0/24`…

![VLANak](img/02-vlanak.png)

## 3. Interfazeak

- WAN: `192.168.74.1/24` IP-a, `192.168.74.254` gateway-arekin.
- DMZ: `192.168.50.1/24`.
- VLAN bakoitzean pfSense `.1` helbidea da, sare horren gateway-a.

`vtnet2` ez dago interfaze gisa esleituta. Horrela trunk-etik etiketadun trafikoa bakarrik pasatzen da.

![Interfazeen esleipena](img/03-interfazeak.png)

## 4. DHCP

DHCP zerbitzaria Windows Server-a da (`192.168.111.10`). pfSense-k BEZEROAK eta SISTEMAK sareetako eskaerak zerbitzari horretara bidaltzen ditu, *DHCP Relay* bidez.

![DHCP Relay](img/04-dhcp-relay.png)

- BEZEROAK sareak `192.168.110.100`–`149` tarteko helbideak banatzen ditu.
- SISTEMAK sarean erreserba bakarra dago, administratzailearen ekipoarentzat (`192.168.112.200`).
- ZERBITZARIAK eta Q sareek IP finkoak erabiltzen dituzte.

![BEZEROAK esparrua](img/04-dhcp-esparrua-bezeroak.png)

Proba: Erabiltzaile1-ek IP-a jasotzen du VLAN 110ean.

![DHCP proba](img/04-dhcp-proba.png)

## 5. Aliasak

Arauak errazago irakurtzeko, alias hauek sortu ditugu:

- `BARNE_SAREAK`: sare pribatu guztiak.
- `EN_DC`: domeinu-kontrolatzailea.
- `EN_WEB`: DMZ-ko web zerbitzaria.
- `AD_PORTUAK`: Active Directory-ren portuak.
- `WEB_PORTUAK`: 80 eta 443 portuak.

![IP aliasak](img/05-aliasak-ip.png)

![Portu aliasak](img/05-aliasak-portuak.png)

## 6. Firewall arauak

**WAN:** ez dago araurik. Kanpotik sartzen den guztia blokeatuta dago.

**BEZEROAK:** domeinu-kontrolatzailera AD-ren portuetara bakarrik sar daitezke. Beste barne-sare guztiak blokeatuta daude (log-arekin). Internetera irten daitezke.

![VLAN110 arauak](img/06-arauak-vlan110.png)

**ZERBITZARIAK:** Internetera bakarrik irten daitezke, eta DNS, NTP eta HTTP/HTTPS bidez bakarrik. Laugarren arauak DC-aren erantzunak onartzen ditu, DHCP relay-ak funtziona dezan.

![VLAN111 arauak](img/07-arauak-vlan111.png)

**SISTEMAK:** administratzaileen sarea da. Sarbide osoa du.

![VLAN112 arauak](img/08-arauak-vlan112.png)

**Q:** dena blokeatuta dago (log-arekin).

![VLAN113 arauak](img/09-arauak-vlan113.png)

**DMZ:** web zerbitzaria Internetera irten daiteke, baina ezin da barne-sareetara sartu. Datu-baserako araua desgaituta dago, oraingoz ez delako erabiltzen.

![DMZ arauak](img/10-arauak-dmz.png)

## 7. NAT

**DNAT:** WAN-eko 80 eta 443 portuak DMZ-ko web zerbitzarira (`EN_WEB`) bidaltzen dira.

![Port Forward](img/11-nat-port-forward.png)

**SNAT:** irteerako NAT-a eskuz konfiguratu dugu (*Manual Outbound NAT*). Modu automatikoak barne-sare guztiak itzultzen ditu, baita behar ez dutenak ere. Eskuzko moduan, Internetera irten behar duten sareak bakarrik itzultzen dira WAN-eko IP-ra.

| Sarea | SNAT | Zergatik |
|---|---|---|
| 110 BEZEROAK | ✅ | Erabiltzaileek nabigatzen dute |
| 111 ZERBITZARIAK | ✅ | Eguneraketak, NTP, DNS |
| 112 SISTEMAK | ✅ | Administrazioa |
| 50 DMZ | ✅ | Web zerbitzariaren eguneraketak |
| 113 Q | ❌ | Isolatuta |
| 114 OpenVPN | ❌ | *Split tunnel*: bezeroen Internet trafikoa ez da hemendik pasatzen |

Sare hauek `NAT_SAREAK` aliasean daude, eta arau bakarra dago. IPsec tuneleko trafikoa (U1-era doana) ez da itzultzen, ez delako WAN-etik zuzenean irteten.

![NAT_SAREAK aliasa](img/11b-nat-alias.png)

![Araua](img/11d-nat-araua.png)

![Outbound NAT](img/11c-nat-outbound.png)

Probak: ZERBITZARIAK saretik (EN-CertServ) eta SISTEMAK saretik Internetera irten daiteke.

![Proba 111](img/11e-nat-proba-111.png)

![Proba 112](img/11f-nat-proba-112.png)

## 8. Kudeaketa

WebGUI-ra izenarekin sartzen gara: `https://pfsense.talde1.lan` (`192.168.112.1`). Enpresako PKI-ak sinatutako ziurtagiria erabiltzen du (ikus [kudeaketa-atzipena](../kudeaketa-atzipena/README.md)).

Administrazio-sareetatik bakarrik sar daiteke:

- SISTEMAK.
- OpenVPN.
- U1-eko administrazio-ekipoa (IPsec tuneletik).

Anti-lockout araua desgaitu dugu. Horrela sarbidea firewall arauek bakarrik kontrolatzen dute.

![Admin Access](img/12-admin-access.png)
