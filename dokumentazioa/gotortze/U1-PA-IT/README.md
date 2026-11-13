# U1 – PA-IT konfigurazioa

## 1. Makina birtuala (IsardVDI)

PA-VM suebakia IsardVDI-n dago. Lehen sare-txartela kudeaketa-ataka da. Hurrengoak ethernet1/1, ethernet1/2 eta ethernet1/3 dira. Txartelen ordenak zehazten du sare bakoitza zein interfazetan dagoen.

Kudeaketa-ataka Default sarean dago. Isard-ek ez du sare bera bi aldiz gehitzen uzten.

![01-makina-birtuala-isardvdi](img/01-makina-birtuala-isardvdi-1.png)

## 2. Hasierako konfigurazioa (CLI)

Fabrikako konfiguraziotik hasi gara. Kontsolatik gutxieneko konfigurazioa egin dugu, IT saretik web interfazera sartu ahal izateko. Gainerako guztia web interfazetik egin dugu.

```
request system private-data-reset

configure
set network profiles interface-management-profile MGMT-IT https yes ssh yes ping yes
set network interface ethernet ethernet1/2 layer3 ip 192.168.20.1/24
set network interface ethernet ethernet1/2 layer3 interface-management-profile MGMT-IT
set zone U1-IT network layer3 ethernet1/2
set network virtual-router default interface ethernet1/2
commit
```

## 3. Zonak

Sare bakoitza bere zonan dago: WAN, IT eta DMZ. Horrela, zona batetik bestera doan trafiko guztia segurtasun-politiken bidez kontrolatzen da.

![03-zonak](img/03-zonak-1.png)

## 4. Interfazeak

Interfaze guztiak Layer3 moduan daude. PA-IT da sare bakoitzaren gateway-a.

Kudeaketa-profila IT interfazean bakarrik dago. WAN eta DMZ interfazeetatik ezin da suebakia administratu.

![04-interfazeak](img/04-interfazeak-1.png)

## 5. Kudeaketa-profila

Suebakia HTTPS eta SSH bidez bakarrik administratzen da. HTTP eta Telnet desgaituta daude. Gainera, administrazio-ekipotik (192.168.20.10) bakarrik sar daiteke.

![05-kudeaketa-profila](img/05-kudeaketa-profila-1.png)

## 6. Bideratzea

Ibilbide lehenetsiak Internetera doan trafikoa vlan74-ko gateway-ra (192.168.74.254) bidaltzen du. Bideratze-taulan ikusten da ibilbidea aktibo dagoela eta sareak konektatuta daudela.

![06-bideratzea](img/06-bideratzea-1.png)

![06-bideratzea](img/06-bideratzea-2.png)

## 7. Sistema

Hostname-ari esker gailua erraz identifikatzen da kudeaketan eta log-etan. DNS zerbitzariak suebakiak berak izenak ebazteko erabiltzen ditu.

![07-sistema](img/07-sistema-1.png)

![07-sistema](img/07-sistema-2.png)

## 8. NAT

SNAT arau bakarra dago, IT sarearentzat. Sare hori da Internetera irten behar duen bakarra. Trafikoa WAN interfazearen IP-arekin itzultzen da (Dynamic IP and Port).

![08-nat](img/08-nat-1.png)
![08-nat](img/08-nat-2.png)

## 9. Segurtasun-politikak

Oinarrizko araua hau da: dena debekatuta dago, eta beharrezkoa dena bakarrik baimentzen da.

- DMZ-tik ezin da barneko sarera sartu.
- IT-tik DMZ-ra web trafikoa bakarrik baimentzen da.
- SSH administrazio-ekipotik bakarrik baimentzen da.
- Internetera aplikazio jakin batzuk bakarrik irten daitezke.

Gainerako trafikoa interzone-default arauak blokeatzen du, eta log-etan gordetzen da.

![09-segurtasun-politikak](img/09-segurtasun-politikak-1.png)

## 10. Pasahitz-politika

Administratzaileen pasahitzek arau hauek bete behar dituzte:

- Gutxienez 8 karaktere.
- Karaktere mota guztiak (maiuskulak, minuskulak, zenbakiak eta ikurrak).
- 90 egunean behin aldatu behar dira.
- Ezin dira azken 5 pasahitzak berrerabili.

![10-pasahitz-politika](img/10-pasahitz-politika-1.png)

## 11. Autentifikazio-ezarpenak

5 saiakera oker egin ondoren, kontua 10 minutuz blokeatzen da. Horrela indar hutsezko erasoak saihesten dira. Saio bat 10 minutuz erabili gabe badago, automatikoki ixten da.

![11-autentifikazio-ezarpenak](img/11-autentifikazio-ezarpenak-1.png)

## 12. Administratzaileak

Taldekide bakoitzak bere erabiltzailea du. Horrela log-etan ikus daiteke nork zer egin duen. admin kontua larrialdietarako bakarrik erabiltzen da. Pasahitz guztiak pasahitz-kudeatzailean gordeta daude.

![12-administratzaileak](img/12-administratzaileak-1.png)
