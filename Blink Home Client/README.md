# Blink Home Client

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.4-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.7.20240628-orange.svg?style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/BlinkHomeSystem/style.yml?branch=main&label=CheckStyle&style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem/actions)

IP-Symcon Modul für die zentrale Kommunikation mit den Blink Servern.

## Inhaltverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#user-content-4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#user-content-5-statusvariablen-und-profile)
6. [WebFront](#user-content-6-webfront)
7. [PHP-Befehlsreferenz](#user-content-7-php-befehlsreferenz)
8. [Versionshistorie](#user-content-8-versionshistorie)

### 1. Funktionsumfang

Dieses Modul bildet die zentrale Kommunikation mit den Blink Servern ab.  
Dies erfolgt auf Basis der inoffizielle dokumentierten [Client-API](https://github.com/MattTW/BlinkMonitorProtocol)

Derzeit unterstützt das Modul folgende Funktionalität:  

* _Login_, _Verify_ (2FA) und _Logout_
* Zeitliche und manuelle Erstellung von _Snapshots_
* Aktivieren und Deaktivieren von _Motion Detection_ (Bewegungserkennung)
* _Arm_ (Scharf) und _Disarm_ (Unscharf) stellen der Aufzeichnung bei Bewegungserkennung
* Auslesen von gerätespezifischen Informationen (_Homescreen_)
* Download von Videos/Clips (Cloud & Lokal)

Folgende Geräte wurden getestet:

* Blink Sync Modul 2 (1st Gen & 2nd Gen)
* Blink Outdoor (3rd Gen)
* Blink Indoor (1st Gen & 3rd Gen)
* Blink Mini (1st Gen)
* Blink Doorbell

Wenn jemand noch andere Geräte im Einsatz hat, bitte einfach bei mir melden!

### 2. Voraussetzungen

* IP-Symcon ab Version 6.4

### 3. Installation

* Über den Module Store das 'Blink Home System'-Modul installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/BlinkHomeSystem` oder `git://github.com/Wilkware/BlinkHomeSystem.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das _'Blink Home Client'_-Modul unter dem Hersteller _'Amazon'_ aufgeführt.

__Konfigurationsseite__:

_Einstellungsbereich:_

> Konto-Informationen ...

Name                    | Beschreibung
----------------------- | ----------------------------------
Blink Account eMail     | Registrierte Mail-Adresse bei Blink
Blink Account Kennwort  | Hinterlegtes Kennwort

> Erweiterte Einstellungen ...

Name                    | Beschreibung
----------------------- | ---------------------------------
Heartbeat-Intervall     | Zeitraum zwischen 2 automatischen Loginversuchen

_Aktionsbereich:_

Aktion                  | Beschreibung
----------------------- | ---------------------------------
ANMELDEN                | Senden der Logindaten an Blink Server
ÜBERPRÜFEN              | Senden eines Codes zur Verifizierung der Login-Daten
ABMELDEN                | Abmelden vom System (Blink Server)
OPTIONEN                | Abrufen und Anzeigen der eingestellten Optionen

### 5. Statusvariablen und Profile

Es werden keine zusätzlichen Statusvariablen oder Profile benötigt.

### 6. WebFront

Es ist keine weitere Steuerung oder gesonderte Darstellung integriert.

### 7. PHP-Befehlsreferenz

```php
void BHS_Login(int $InstanzID);
```

Versucht den Client mit den Account-Daten an den Blink-Servern anzumelden.  
Die Funktion liefert '0' im Fehlerfall, '1' im Erfolgsfall und '2' im Verifizierungsfall.

```php
void BHS_Verify(int $InstanzID);
```

Sendet den per Telefon oder Mail erhaltenen Verifizierungscode an die Blink-Server.
Die Funktion liefert '1' im Erfolgsfall, sonst '0'.

```php
void BHS_Logout(int $InstanzID);
```

Meldet den Client von den Blink-Servern ab.
Die Funktion liefert '1' im Erfolgsfall, sonst '0'.

```php
void BHS_Notification(int $InstanzID);
```

Gibt im angemeldeten Zusatnd die Benachrichtigungsoptionen aus.
Die Funktion liefert '1' im Erfolgsfall, sonst '0'.

### 8. Versionshistorie

v1.7.20240628

* _NEU_: Blink API Layer für Zubehör erweitert
* _FIX_: Konfigurationsformular überarbeitet und vereinheitlicht

v1.6.20240606

* _FIX_: Downloads von Videos verbessert
* _FIX_: Interne Bibliotheken überarbeitet und vereinheitlicht
* _FIX_: Dokumentation überarbeitet

v1.5.20231013

* _FIX_: Übersetzungen ausgebaut bzw. vervollständigt
* _FIX_: Blink API Layer erweitert, aktualisiert und neu dokumentiert
* _FIX_: Style-Checks aktualisiert
* _FIX_: Interne Bibliotheken überarbeitet und vereinheitlicht
* _FIX_: Dokumentation überarbeitet

v1.4.20220815

* _FIX_: API für Blink Doorbells angeasst

v1.3.20220620

* _NEU_: Login überarbeitet, jeder Client hat jetzt eigene UUID
* _NEU_: Verarbeitung von Binärdaten (Bilder) für spätere IPS Versionen vorbereitet
* _FIX_: Fehlerhafter Login wird jetzt abgefangen

v1.2.20220214

* _FIX_: Kennwort Validation Pattern um Minus-Zeichen erweitert

v1.1.20220130

* _NEU_: Blink Mini Support
* _FIX_: Mail Validation Pattern angepasst
* _FIX_: Kennwort Validation Pattern angepasst

v1.0.20220110

* _NEU_: Initialversion

## Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der IP-Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
