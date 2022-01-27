# Blink Home Client

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.0-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.0.20220110-orange.svg)](https://github.com/Wilkware/IPSymconBlink)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://github.com/Wilkware/IPSymconBlink/workflows/Check%20Style/badge.svg)](https://github.com/Wilkware/IPSymconBlink/actions)

IP-Symcon Modul für die zentrale Kommunikation mit den Blink Servern.

## Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Versionshistorie](#8-versionshistorie)

### 1. Funktionsumfang

Dieses Modul bildet die zentrale Kommunikation mit den Blink Servern ab.  
Dies erfolgt auf Basis der inoffizielle dokumentierten [Client-API](https://github.com/MattTW/BlinkMonitorProtocol)

Derzeit unterstützt das Modul folgende Funktionalität:  

* *Login*, *Verify* (2FA) und *Logout*
* Zeitliche und manuelle Erstellung von *Snapshots*
* Aktivieren und Deaktivieren von *Motion Detection* (Bewegungserkennung)
* *Arm* (Scharf) und *Disarm>* (Unscharf) stellen der Aufzeichnung bei Bewegungserkennung
* Auslesen von gerätespezifischen Informationen (*Homescreen*)

Folgende Geräte wurden getestet:

* Blink Outddor (schwarze Kamera)
* Blink Sync Modul 2

Wenn jemand noch andere Geräte im Einsatz hat, bitte einfach bei mir melden!

### 2. Voraussetzungen

* IP-Symcon ab Version 6.0

### 3. Installation

* Über den Module Store das 'Blink Home System'-Modul installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/IPSymconBlink` oder `git://github.com/Wilkware/IPSymconBlink.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das *'Blink Home Client'*-Modul unter dem Hersteller _'Amazon'_ aufgeführt.

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
