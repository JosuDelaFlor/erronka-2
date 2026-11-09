# EN – pfSense konfigurazioa

Ugarteburu Torlojuak · 2. Erronka

## 1. Makina birtuala (Proxmox)

pfSense-k (VM 104) hiru sare-txartel ditu Proxmox-en: `vtnet0` vmbr0-ra (klaseko sarea, WAN), `vtnet1` vmbr1-era (EN-DMZIT, portu fisikorik gabe) eta `vtnet2` vmbr2-ra (MikroTik-erako trunk-a, tag gabe).

![Proxmox-eko sareak](img/01-proxmox-sareak.png)

## 2. VLANak

LAN aldeko lau VLANak `vtnet2`-ren gainean sortu dira: 110 BEZEROAK, 111 ZERBITZARIAK, 112 SISTEMAK eta 113 Q. VLAN bakoitzaren azpisarea bere tag-aren berdina da (`192.168.110.0/24`, `192.168.111.0/24`…).

![VLANak](img/02-vlanak.png)

## 3. Interfazeak

WAN-ak `192.168.74.1/24` IP-a du, `192.168.74.254` gatewayarekin, eta DMZ-ak `192.168.50.1/24`. VLAN bakoitzean pfSense `.1` helbidea da, sare horretako gatewaya.

`vtnet2` ez dago interfaze gisa esleituta, trunk-etik etiketadun trafikoa bakarrik pasa dadin.

![Interfazeen esleipena](img/03-interfazeak.png)

## 4. DHCP

DHCP zerbitzaria Windows Server-a da (`192.168.111.10`), eta pfSense-k *DHCP Relay* bidez birbidaltzen dizkio BEZEROAK eta SISTEMAK sareetako eskaerak.

![DHCP Relay](img/04-dhcp-relay.png)

BEZEROAK sareak `192.168.110.100`–`149` tartea banatzen du. SISTEMAK sarean erreserba bakarra dago administratzailearen ekipoarentzat (`192.168.112.200`). ZERBITZARIAK eta Q sareek IP finkoak erabiltzen dituzte.

![BEZEROAK esparrua](img/04-dhcp-esparrua-bezeroak.png)

Proba: Erabiltzaile1-ek IP-a jasotzen du VLAN 110ean.

![DHCP proba](img/04-dhcp-proba.png)

## 5. Aliasak

Arauak irakurterrazagoak izateko aliasak sortu dira: `BARNE_SAREAK` (sare pribatu guztiak), `EN_DC` (domeinu-kontrolatzailea), `EN_WEB` (DMZko web zerbitzaria), `AD_PORTUAK` (Active Directory-ren portuak) eta `WEB_PORTUAK` (80 eta 443).

![IP aliasak](img/05-aliasak-ip.png)

![Portu aliasak](img/05-aliasak-portuak.png)

## 6. Firewall arauak

**WAN:** araurik ez. Kanpotik sartzen den guztia blokeatuta dago.

**BEZEROAK:** domeinu-kontrolatzailera AD-ren portuetara bakarrik sar daitezke; gainerako barne-sare guztiak blokeatuta daude (log-arekin), eta Internetera irten daitezke.

![VLAN110 arauak](img/06-arauak-vlan110.png)

**ZERBITZARIAK:** DNS, NTP eta HTTP/HTTPS bidez Internetera bakarrik irten daitezke. Laugarren arauak DC-ak DHCP relay-ari bidaltzen dizkion erantzunak onartzen ditu.

![VLAN111 arauak](img/07-arauak-vlan111.png)

**SISTEMAK:** administratzaileen sarea da eta sarbide osoa du.

![VLAN112 arauak](img/08-arauak-vlan112.png)

**Q:** dena blokeatuta (log-arekin).

![VLAN113 arauak](img/09-arauak-vlan113.png)

**DMZ:** web zerbitzariak Internetera irten daiteke, baina ez du barne-sareetara sarbiderik. Datu-baserako araua desgaituta dago, momentuz ez baita erabiltzen.

![DMZ arauak](img/10-arauak-dmz.png)

## 7. NAT

**DNAT:** WAN-eko 80 eta 443 portuak DMZko web zerbitzarira (`EN_WEB`) birbideratzen dira.

![Port Forward](img/11-nat-port-forward.png)

**SNAT:** irteerako NAT-a eskuzkoa da (*Manual Outbound NAT*), Internetera irten behar duten sareak bakarrik WAN-eko IP-arekin itzultzeko. Modu automatikoak barne-sare guztiak itzultzen ditu, baita behar ez dutenak ere.

| Sarea | SNAT | Zergatik |
|---|---|---|
| 110 BEZEROAK | ✅ | Erabiltzaileek nabigatzen dute |
| 111 ZERBITZARIAK | ✅ | Eguneraketak, NTP, DNS |
| 112 SISTEMAK | ✅ | Administrazioa |
| 50 DMZ | ✅ | Web zerbitzariaren eguneraketak |
| 113 Q | ❌ | Isolatuta |
| 114 OpenVPN | ❌ | *Split tunnel*: bezeroen Internet trafikoa ez da hemendik pasatzen |

Sareak `NAT_SAREAK` aliasean daude, eta arau bakarra dago. IPsec tuneleko trafikoa (U1-era) ez da itzultzen, ez baita WAN-etik zuzenean irteten.

![NAT_SAREAK aliasa](img/11b-nat-alias.png)

![Araua](img/11d-nat-araua.png)

![Outbound NAT](img/11c-nat-outbound.png)

Probak: ZERBITZARIAK-etik (EN-CertServ) eta SISTEMAK-etik Internetera irten daiteke.

![Proba 111](img/11e-nat-proba-111.png)

![Proba 112](img/11f-nat-proba-112.png)

## 8. Kudeaketa

WebGUI-a izen bidez eskuratzen da, `https://pfsense.talde1.lan` (`192.168.112.1`), enpresako PKI-ak sinatutako ziurtagiriarekin (ikus [kudeaketa-atzipena](../kudeaketa-atzipena/README.md)). Administrazio-sareetatik bakarrik dago eskuragarri: SISTEMAK, OpenVPN eta U1-eko administrazio-ekipoa (IPsec tuneletik). Anti-lockout araua desgaitu da, sarbidea firewall arauek bakarrik kontrola dezaten. Hori egiaztatzeko probak [Zero Trust dokumentuan](../zero-trust/README.md) daude.

![Admin Access](img/12-admin-access.png)
