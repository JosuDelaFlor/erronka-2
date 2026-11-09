# Sarearen azalpena

Ugarteburu Torlojuak · 2. Erronka

![Sare-eskema](img/01-sare-eskema.png)

Enpresak bi egoitza ditu, EN eta U1, eta biak klaseko sarearen bidez (`192.168.74.0/24`) daude lotuta, gure "Internet"-a balitz bezala. Egoitza bakoitzak bere suebakia du, eta bi suebakien artean IPsec VPN tunel bat dago, bulego batetik bestera modu seguruan komunikatzeko (ikus [VPN](../vpn/README.md)).

## EN egoitza

EN-ko suebakia pfSense da (`192.168.74.1`), Proxmox-en (`192.168.74.11`) makina birtual gisa. Barneko sareak VLANetan banatuta daude, eta pfSense-tik MikroTik switchera trunk bidez iristen dira. VLAN bakoitzak bere zeregina du:

- **110 BEZEROAK** (`192.168.110.0/24`): langileen ekipoak. IP-a DHCP bidez jasotzen dute.
- **111 ZERBITZARIAK** (`192.168.111.0/24`): domeinu-kontrolatzailea (`.10`), PKI-a (Intermediate CA `.20` eta Root CA `.21`, normalean itzalita), pasahitz-kudeatzailea (`.30`) eta email-scripta (`.50`).
- **112 SISTEMAK** (`192.168.112.0/24`): administratzaileen sarea.
- **113 Q** (`192.168.113.0/24`): isolatutako sarea, ez du ezer ikusten.

Horiez gain, **DMZ** bat dago (`192.168.50.0/24`), web orriarentzat (`.10`). Kanpotik eskuragarri dagoen bakarra da, eta horregatik barne-saretik bereizita dago. Taldekideek etxetik **OpenVPN** bidez konekta daitezke (`192.168.114.0/24`).

Sare guztien gateway-a pfSense da (`.1` helbidea), eta pfSense-k klaseko gateway-ra (`192.168.74.254`) bidaltzen du Internetera doan trafikoa.

## U1 egoitza

U1-eko suebakia PA-IT da (Palo Alto, `192.168.74.2`). Bi sare ditu:

- **U1-IT** (`192.168.20.0/24`): U1-eko domeinu-kontrolatzailea (`.20`) eta administrazio-ekipoa (`.10`).
- **U1-DMZIT** (`192.168.10.0/24`): datu-basea (`.10`).

Fabrika (OT) bigarren suebaki baten atzean dago, PA-OT (`192.168.20.254`), bulegoko sarea eta produkzioa bereizteko. Bi sare ditu:

- **U1-OT** (`192.168.30.0/24`): openPLC-a (`.10`).
- **U1-DMZOT** (`192.168.40.0/24`): honeypot-a (`.10`).

## Internetera irteera

Barneko helbideak ezin dira Internetera zuzenean irten, beraz suebakiek beren WAN helbidearekin itzultzen dituzte (SNAT). Behar duten sareei bakarrik egiten zaie: Q eta OpenVPN-ekoei ez. Kanpotik web orrira iristeko, pfSense-k 80 eta 443 portuak DMZ-ko web zerbitzarira birbideratzen ditu (DNAT). Xehetasunak [pfSense](../pfsense/README.md) eta [PA-IT](../U1-PA-IT/README.md) dokumentuetan daude.
