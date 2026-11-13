# U1 – PA-OT konfigurazioa

## 1. Makina birtuala (IsardVDI)

PA-VM suebakia IsardVDI-n dago. PA-IT-ren txantiloi beretik sortu dugu, 2 vCPU-rekin (Isard-eko kuotagatik).

Sare-txartelak honela daude:

- Lehen txartela kudeaketa-ataka da (Default sarea).
- ethernet1/1 U1-IT sarean dago (Ziber-T12). Hortik konektatzen da PA-IT-ra.
- ethernet1/2 U1-OT sarean dago (Ziber-T13).
- ethernet1/3 U1-DMZOT sarean dago (Ziber-T14).

## 2. Hasierako konfigurazioa (CLI)

Fabrikako konfiguraziotik hasi gara. Kontsolatik gutxieneko konfigurazioa egin dugu, IT saretik web interfazera sartu ahal izateko. Gainerako guztia web interfazetik egin dugu.

```
request system private-data-reset

configure
set network profiles interface-management-profile MGMT-OT https yes ssh yes ping yes
set network profiles interface-management-profile MGMT-OT permitted-ip 192.168.20.10
set network interface ethernet ethernet1/1 layer3 ip 192.168.20.254/24
set network interface ethernet ethernet1/1 layer3 interface-management-profile MGMT-OT
set zone U1-IT network layer3 ethernet1/1
set network virtual-router default interface ethernet1/1
commit
```

## 3. Zonak

Sare bakoitza bere zonan dago: U1-IT, U1-OT eta U1-DMZOT. Horrela, IT eta OT arteko trafiko guztia segurtasun-politiken bidez kontrolatzen da.

![03-zonak](img/03-zonak-1.png)

## 4. Interfazeak

Interfaze guztiak Layer3 moduan daude. PA-OT da OT eta DMZ-OT sareen gateway-a.

Kudeaketa-profila IT aldeko interfazean bakarrik dago. OT eta DMZ-OT interfazeetatik ezin da suebakia administratu.

![04-interfazeak](img/04-interfazeak-1.png)

## 5. Kudeaketa-profila

Suebakia HTTPS eta SSH bidez bakarrik administratzen da. HTTP eta Telnet desgaituta daude. Gainera, administrazio-ekipotik (192.168.20.10) bakarrik sar daiteke.

![05-kudeaketa](img/05-kudeaketa-1.png)

## 6. Bideratzea

Ibilbide lehenetsiak OT aldetik irteten den trafikoa PA-IT-ra (192.168.20.1) bidaltzen du.

![06-bideratzea](img/06-bideratzea-1.png)

## 7. Sistema

Hostname-ari esker gailua erraz identifikatzen da kudeaketan eta log-etan. DNS zerbitzariak suebakiak berak izenak ebazteko erabiltzen ditu.

![07-sistema](img/07-sistema-1.png)

![07-sistema](img/07-sistema-2.png)

## 8. Segurtasun-politikak

IT eta OT sareak bereizita daude.

- DMZ-OT-tik ezin da OT sarera sartu.
- IT-tik DMZ-OT-ra SSH bidezko administrazioa bakarrik baimentzen da, eta administrazio-ekipotik bakarrik.

Gainerako trafikoa interzone-default arauak blokeatzen du, eta log-etan gordetzen da.

Ez dago NAT-ik. OT sareek ezin dute Internetera irten.

![08-segurtasun-politikak](img/08-segurtasun-politikak-1.png)

## 9. Pasahitz-politika

Administratzaileen pasahitzek arau hauek bete behar dituzte:

- Gutxienez 8 karaktere.
- Karaktere mota guztiak (maiuskulak, minuskulak, zenbakiak eta ikurrak).
- 90 egunean behin aldatu behar dira.
- Ezin dira azken 5 pasahitzak berrerabili.

![09-pasahitz-politika](img/09-pasahitz-politika-1.png)

## 10. Autentifikazio-ezarpenak

5 saiakera oker egin ondoren, kontua 10 minutuz blokeatzen da. Horrela indar hutsezko erasoak saihesten dira. Saio bat 10 minutuz erabili gabe badago, automatikoki ixten da.

![10-autentifikazio-ezarpenak](img/10-autentifikazio-ezarpenak-1.png)

## 11. Administratzaileak

Taldekide bakoitzak bere erabiltzailea du. Horrela log-etan ikus daiteke nork zer egin duen. admin kontua larrialdietarako bakarrik erabiltzen da. Pasahitz guztiak pasahitz-kudeatzailean gordeta daude.

![11-administratzaileak](img/11-administratzaileak-1.png)

## 12. PA-IT: OT sareetarako ibilbideak

PA-IT-n ibilbide estatikoak gehitu ditugu OT eta DMZ-OT sareetarako. Hurrengo jauzia (next hop) PA-OT da (192.168.20.254).

Horrela, sare horietara doan trafikoa PA-OT-ra joaten da, eta ez WAN-era. Sarbidea PA-OT-ren politikek kontrolatzen dute.

![12-pa-it-ibilbideak](img/12-pa-it-ibilbideak-1.png)

![12-pa-it-ibilbideak](img/12-pa-it-ibilbideak-2.png)

## 13. Administrazio-ekipoa

PA-OT eta administrazio-ekipoa sare berean daude (U1-IT). Horregatik ekipoan ibilbide iraunkorrak gehitu ditugu. Horrela OT sareetarako trafikoa zuzenean PA-OT-ra doa, eta joan-etorriak bide beretik egiten dira.

```
route -p add 192.168.30.0 mask 255.255.255.0 192.168.20.254
route -p add 192.168.40.0 mask 255.255.255.0 192.168.20.254
```
