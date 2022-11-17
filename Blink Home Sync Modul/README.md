# Blink Home Sync Modul

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.0-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.1.20220130-orange.svg)](https://github.com/Wilkware/IPSymconBlink)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://github.com/Wilkware/IPSymconBlink/workflows/Check%20Style/badge.svg)](https://github.com/Wilkware/IPSymconBlink/actions)

IP-Symcon Modul für die Steuerung aller aktiven Kameras im gleichen Netzwerk.

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

Derzeit kann über das Modul nur die Aufzeichnung von Bewegungsereignissen für alle aktivierten Kameras im Netzwerk gesteuert werden.
Es ist derzeit noch nicht absehbar, welchen Funktionsumfang das Modul endgültig umfasst.

### 2. Voraussetzungen

* IP-Symcon ab Version 6.0

### 3. Installation

* Über den Module Store das 'Blink Home System'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen  
`https://github.com/Wilkware/IPSymconBlink` oder `git://github.com/Wilkware/IPSymconBlink.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das _'Blink Home Sync Modul'_-Modul unter dem Hersteller _'Amazon'_ aufgeführt.
* Über den _'Blink Home Configurator'_ kann eine einfache Installation vorgenommen werden  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Einstellungsbereich:

> Modulinformationen ...

Name           | Beschreibung
-------------- | ------------------
Gerätetyp      | Typbezeichnung (Sync Modul)
Gerätemodell   | Modellbezeichnung (Model 1 oder 2)
Geräte-ID      | Interne Gerätenummer (6-stellig)
Netwerk-ID     | Interne Netwerknummer (6-stellig)

> Aufzeichnung von Bewegungsereignissen ...

Name           | Beschreibung
-------------- | ------------------
STARTEN        | Schalter für direktes scharf Stellen der Aufnahme
STOPPEN        | Schalter zum direkten Stoppen von Aufnahmen
Zeitplan       | Zeitplan zum Starten und Stoppen von Aufnahmen

> Erweiterte Einstellungen  ...

Name           | Beschreibung
-------------- | ------------------
Variable zum Umschalten der Aufzeichnung erstellen? | Schaltvariable für's Webfront

Aktionsbereich:

Aktion              | Beschreibung
------------------- | ------------------
ZEITPLAN HINZUFÜGEN | Es wird ein Wochenplan mit 2 Zuständen (Aktiv & Inaktiv) angelegt und in den Einstellung hinterlegt.
NETZWERK            | Ausgabe der Netwerkinformationen.
SYNC MODUL          | Ausgabe der Modulinformationen.

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Ident         | Name          | Typ     | Profil    | Beschreibung
------------- | ------------- | ------- | --------- | -------------------
 recording    | Aufzeichnung  | boolean | ~Switch   | An/Aus-Schalter für Aufzeichnungen

#### Profile

Es werden keine zusätzlichen Profile benötigt.

### 6. WebFront

Man kann die Statusvariablen direkt im WF verlinken.

### 7. PHP-Befehlsreferenz

```php
    boolean BHS_Arm(integer $InstanzID);
```

Schaltet alle im Netwerk befindlichen Kameras scharf.

__Beispiel__: `BHS_Arm(12345);`

```php
    boolean BHS_Disarm(integer $InstanzID);
```

Schaltet alle im Netwerk befindlichen Kameras unscharf.

__Beispiel__: `BHS_Disarm(12345);`

### 8. Versionshistorie

v1.1.20220130

* _NEU_: Zeitplan für Aufnahmenaktivierung hinzugefügt
* _FIX_: Funktionen Network() und SyncModul() nur für internen Gebrauch verändert

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
