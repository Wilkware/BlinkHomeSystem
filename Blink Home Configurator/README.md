# Blink Home Configurator

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.4-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.6.20240606-orange.svg?style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/BlinkHomeSystem/style.yml?branch=main&label=CheckStyle&style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem/actions)

IP-Symcon Modul für die Verwaltung alle im Netzwerk befindlichen Bilnk Geräte.

## Inhaltsverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#user-content-4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#user-content-5-statusvariablen-und-profile)
6. [WebFront](#user-content-6-webfront)
7. [PHP-Befehlsreferenz](#user-content-7-php-befehlsreferenz)
8. [Versionshistorie](#user-content-8-versionshistorie)

### 1. Funktionsumfang

Mit Hilfe des Konfigurations-Moduls kann man schnell und einfach die im Netwerk registrierten Geräte auswählen und die dazugehörigen Modul-Instanzen verwalten bzw. anlegen.

Derzeit unterstützt der Konfigurator Kameras, Türklingeln und Sync Module.

Wenn jemand noch weitere im Einsatz hat, bitte einfach bei mir melden!

### 2. Voraussetzungen

* IP-Symcon ab Version 6.4

### 3. Installation

* Über den Module Store das 'Blink Home System'-Modul installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/BlinkHomeSystem` oder `git://github.com/Wilkware/BlinkHomeSystem.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter 'Instanz hinzufügen' kann das _'Blink Home Konfigurator'_-Modul mithilfe des Schnellfilters gefunden werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen).

__Konfigurationsseite__:

Innerhalb der Konfiguratorliste werden alle im Netzwerk verfügbaren Geräte aufgeführt.
Man kann pro Gerät eine Instanzen anlegen und auch wieder löschen.
Legt man eine entsprechende Zielkategorie fest, werden neu zu erstellende Instanzen unterhalb dieser Kategorie angelegt.

_Einstellungsbereich:_

Name                    | Beschreibung
----------------------- | ---------------------------------
Zielkategorie           | Kategorie unter welcher neue Instanzen erzeugt werden (keine Auswahl im Root). Nur bis Version 7!

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

v1.6.20240606

* _NEU_: Support für Blink Indoor Kamera (3rd Gen)
* _NEU_: Unterstützung für IPS v7.x
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
