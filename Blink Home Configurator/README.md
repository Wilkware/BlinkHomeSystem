# 🔎 Blink Home Configurator

[![Home](https://img.shields.io/badge/Home-wilkware.de-0b1830.svg?style=flat-square)](https://wilkware.de/module/blink/configurator/)
[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-8.1-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-2.7.20260929-orange.svg?style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/BlinkHomeSystem/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem/actions)

Symcon Modul für die Verwaltung aller im Netzwerk befindlichen Blink Geräte.

## Inhaltsverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichtung](#user-content-4-einrichtung)
5. [Statusvariablen](#user-content-5-statusvariablen)
6. [Darstellungen](#user-content-6-darstellungen)
7. [Visualisierung](#user-content-7-visualisierung)
8. [Befehlsreferenz](#user-content-8-befehlsreferenz)
9. [Versionshistorie](#user-content-9-versionshistorie)

### 1. Funktionsumfang

Mit Hilfe des Konfigurations-Moduls kann man schnell und einfach die im Netzwerk registrierten Geräte auswählen und die dazugehörigen Modul-Instanzen verwalten bzw. anlegen.

Derzeit unterstützt der Konfigurator Kameras, Türklingeln und Sync Module.

Wenn jemand noch weitere im Einsatz hat, bitte einfach bei mir melden!

### 2. Voraussetzungen

* Symcon ab Version 8.1

### 3. Installation

* Über den Modul Store die Bibliothek _Blink Home System_ installieren.
* Alternativ über das Modul Control folgende URL hinzufügen.  
`https://github.com/Wilkware/BlinkHomeSystem` oder `git://github.com/Wilkware/BlinkHomeSystem.git`

### 4. Einrichtung

* Unter 'Instanz hinzufügen' ist das _Blink Home Konfigurator_-Modul unter dem Hersteller 'Amazon' aufgeführt.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen).

__Konfigurationsseite__:

Innerhalb der Konfiguratorliste werden alle im Netzwerk verfügbaren Geräte aufgeführt.
Man kann pro Gerät eine Instanzen anlegen und auch wieder löschen.
Legt man eine entsprechende Zielkategorie fest, werden neu zu erstellende Instanzen unterhalb dieser Kategorie angelegt.

_Aktionsbereich:_

Name                    | Beschreibung
----------------------- | ---------------------------------
Geräte                  | Konfigurationsliste zum Verwalten der entsprechenden Geräte-Instanzen

### 5. Statusvariablen

Es werden keine Statusvariablen angelegt.

### 6. Darstellungen

Es werden keine Darstellungen oder Profile benötigt.

### 7. Visualisierung

Es ist keine weitere Steuerung oder gesonderte Darstellung integriert.

### 8. Befehlsreferenz

Das Modul bietet keine direkten Funktionsaufrufe.

### 9. Versionshistorie

v2.7.20260929

* _NEU_: Support von Blink Outdoor 2K+
* _NEU_: Namespaces eingeführt
* _NEU_: Versionierung vereinheitlicht
* _FIX_: Darstellungsparameter korrigiert
* _FIX_: Interne Bibliotheken erweitert und vereinheitlicht

v2.6.20260526

* _FIX_: Kleinere Übersetzungsfehler korrigiert

v2.5.20260526

* _NEU_: Support von Blink Sync Modul Core
* _FIX_: Fehlerhafte Erstellungs-Kette gefixt
* _FIX_: Kleinere Anpassungen in Bibliotheken

v2.4.20260428

* _NEU_: Liveview via eigenem NodeJS Service

v2.3.20260108

* _NEU_: Modulversion wird in Quellcodesektion angezeigt

v2.1.20251125

* _NEU_: Support für Blink Mini 2K+

v2.0.20251013

* _NEU_: Support für Blink Outdoor 4 Kamera
* _NEU_: Umstellung auf Strict-Modus (IPSModuleStrict)
* _NEU_: Umstellung auf globale einheitliche Versionsnummer
* _NEU_: Kompatibilität auf IPS 8.1 vereinheitlicht
* _FIX_: Interne Bibliotheken und Konfiguration überarbeitet und vereinheitlicht
* _FIX_: Ungenutzten Code entfernt
* _FIX_: Inline-Dokumentation komplett überarbeitet

v1.7.20240628

* _NEU_: Support für Mini 2 Kamera
* _NEU_: Support für Fllodlight Mount (Zubehör)
* _NEU_: Stromversorgungsart und Batterieladezustand hinzugefügt bzw. getrennt
* _FIX_: Fehler in Übersetzungen berichtigt

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

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
