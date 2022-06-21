# Blink Home Configurator

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.0-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.3.20220620-orange.svg)](https://github.com/Wilkware/IPSymconBlink)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://github.com/Wilkware/IPSymconBlink/workflows/Check%20Style/badge.svg)](https://github.com/Wilkware/IPSymconBlink/actions)

IP-Symcon Modul für die Verwaltung alle im Netzwerk befindlichen Bilnk Geräte.

## Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Versionshistorie](#8-versionshistorie)

### 1. Funktionsumfang

Mit Hilfe des Konfigurations-Moduls kann man schnell und einfach die im Netwerk registrierten Geräte auswählen und die dazugehörigen Modul-Instanzen verwalten bzw. anlegen.

Derzeit unterstützt der Konfigurator Kameras, Türklingeln und Sync Module.

Wenn jemand noch weitere im Einsatz hat, bitte einfach bei mir melden!

### 2. Vorraussetzungen

* IP-Symcon ab Version 6.0

### 3. Installation

* Über den Module Store das 'Blink Home System'-Modul installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/IPSymconBlink` oder `git://github.com/Wilkware/IPSymconBlink.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter 'Instanz hinzufügen' kann das _'Blink Home Configurator'_-Modul mithilfe des Schnellfilters gefunden werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen).

__Konfigurationsseite__:

Innerhalb der Konfiguratorliste werden alle im Netzwerk verfügbaren Geräte aufgeführt.
Man kann pro Gerät eine Instanzen anlegen und auch wieder löschen.
Legt man eine entsprechende Zielkategorie fest, werden neu zu erstellende Instanzen unterhalb dieser Kategorie angelegt.

_Einstellungsbereich:_

Name                    | Beschreibung
----------------------- | ---------------------------------
Zielkategorie           | Kategorie unter welcher neue Instanzen erzeugt werden (keine Auswahl im Root)

_Aktionsbereich:_

Name                    | Beschreibung
----------------------- | ---------------------------------
Geräte                  | Konfigurationsliste zum Verwalten der entsprechenden Geräte-Instanzen

### 5. Statusvariablen und Profile

Es werden keine zusätzlichen Statusvariablen oder Profile benötigt.

### 6. WebFront

Es ist keine weitere Steuerung oder gesonderte Darstellung integriert.

### 7. PHP-Befehlsreferenz

Das Modul bietet keine direkten Funktionsaufrufe.

### 8. Versionshistorie

v1.3.20220620

* _NEU_: Blink Doorbell Support
* _NEU_: Weitere Modellbezeichnungungen aufgenommen
* _FIX_: Instanzmanagement nochmal verbessert

v1.2.20220214

* _FIX_: Punkt 15 der Review-Richtlinien umgesetzt

v1.1.20220130

* _NEU_: Blink Mini Support

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
