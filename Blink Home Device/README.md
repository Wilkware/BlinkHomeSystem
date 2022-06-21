# Blink Home Device

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.0-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.3.20220620-orange.svg)](https://github.com/Wilkware/IPSymconBlink)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://github.com/Wilkware/IPSymconBlink/workflows/Check%20Style/badge.svg)](https://github.com/Wilkware/IPSymconBlink/actions)

Ermöglicht die Kommunikation mit einem Blink Endgerät, derzeit vornehmlich Kameras.

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

Derzeit kann über das Modul nur eien Momentaufnahme (Snapshot) aktiviert und angezeigt werden.  
Es ist derzeit noch nicht absehbar, welchen Funktionsumfang das Modul endgültig umfasst.

### 2. Vorraussetzungen

* IP-Symcon ab Version 6.0

### 3. Installation

* Über den Module Store das 'Blink Home System'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen  
`https://github.com/Wilkware/IPSymconBlink` oder `git://github.com/Wilkware/IPSymconBlink.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das _'Blink Home Device'_-Modul unter dem Hersteller _'Amazon'_ aufgeführt.
* Über den _'Blink Home Configurator'_ kann eine einfache Installation vorgenommen werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

_Einstellungsbereich:_

> Geräteinformationen ...

Name           | Beschreibung
-------------- | ------------------
Gerätetyp      | Typbezeichnung (Kamera)
Gerätemodell   | Modellbezeichnung
Geräte-ID      | Interne Gerätenummer (6-stellig)
Netwerk-ID     | Interne Netwerknummer (6-stellig)

> Bilder ...

Name                                                   | Beschreibung
------------------------------------------------------ | -----------------------------------------------------
Erstellen einer Medienvariablen für Momentausnahmen!   | Schalter für Anlegen eines Medienobjektes (Image) für das Speichern der Aufnahmen
Benutze In-Memory Cache!                               | Schalter zum direkten Speichern des Medienobjektes im Speicher (Cache)
Erstellen eines Zeitstempels auf jeder Momentaufnahme! | Schalter zum Aktivieren eines Zeitstemples auf jeder Aufnahme
Seitenrand Oben                                        | Abstand des Zeitstempels vom oberen Bildrand
Seitenrand Links                                       | Abstand des Zeitstempels vom linken Bildrand
Schriftgröße                                           | Schriftgröße des Zeitstempels
Schriftfarbe                                           | Farbliche Gestaltung des Zeitstempels
Pfad zu der TrueType-Schriftart                        | Angabe welcher Truetype-Font verwendet werden soll (voller Dateipfad)

> Zeitsteuerung ...

Name                     | Beschreibung
------------------------ | ------------------
Aktualisierungsintervall | Zeit zwischen 2 Aufnahemen (Standard 60 Minuten), geht auf die Lebensdauer der Batterie!
Zeitplan                 | Zeitraum in dem Aufnahmen im angeegebenen Intervall erfolgen sollen.

> Erweiterte Einstellungen  ...

Name           | Beschreibung
-------------- | ------------------
Anlegen einer Variabel zur Auslösung einer Momentaufnahme der aktuellen Ansicht der Kamera! | Variable für's Webfront zum Auslösen einer Aufnahme

_Aktionsbereich:_

Aktion              | Beschreibung
------------------- | ------------------
ZEITPLAN HINZUFÜGEN | Es wird ein Wochenplan mit 2 Zuständen (Aktiv & Inaktiv) angelegt und in den Einstellung hinterlegt.
SNAPSHOT            | Löst eine Momentaufnahme(Snapshot) aus.

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Ident               | Name               | Typ     | Profil     | Beschreibung
------------------- | ------------------ | ------- | ---------- | -------------------
circuit_snapshot    | Zeitplan Snapshot  | event   |            | Wochenplan für Momentaufnahmen
thumbnail           | Image              | media   |            | Medienobject zum Speichern der Aufnahme
motion_detection    | Bewegungserkennung | boolean | ~Switch    | Variable zum an- und ausschalten der Bewegungserkennung
snapshot            | Snapshot           | integer | BHS.Update | Variable zum Auslösen einer Momentaufnahme

#### Profile

Folgendes Profil wird angelegt:

Name           | Typ       | Beschreibung
-------------- | --------- | ----------------
BHS.Update     | Integer   | Snapshot Auslöser Profil (1: '►')

### 6. WebFront

Man kann die Statusvariablen direkt im WF verlinken.

### 7. PHP-Befehlsreferenz

Ein direkter Aufruf von öffentlichen Funktionen ist nicht notwendig!

### 8. Versionshistorie

v1.3.20220620

* _FIX_: Bildverarbeitung überarbeitet
* _FIX_: Logging verbessert

v1.2.20220214

* _NEU_: Bewegungserkennung für Blink Mini deaktiviert
* _FIX_: Hintergrundrahmen für Zeitstempel optimiert
* _FIX_: Übersetzungen korrigiert

v1.1.20220130

* _NEU_: Blink Mini Support
* _NEU_: Format für Zeitstempel hinzugefügt
* _NEU_: Hintergrundfarbe für Zeitstempel hinzugefügt
* _FIX_: Bugfix Zeitplan

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
