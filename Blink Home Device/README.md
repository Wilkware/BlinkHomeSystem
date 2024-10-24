# Blink Home Device

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.4-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.8.20241024-orange.svg?style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/BlinkHomeSystem/style.yml?branch=main&label=CheckStyle&style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem/actions)

Ermöglicht die Kommunikation mit einem Blink Endgerät, derzeit vornehmlich Kameras.

## Inhaltverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#user-content-4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#user-content-5-statusvariablen-und-profile)
6. [Visualisierung](#user-content-6-visualisierung)
7. [PHP-Befehlsreferenz](#user-content-7-php-befehlsreferenz)
8. [Versionshistorie](#user-content-8-versionshistorie)

### 1. Funktionsumfang

Derzeit kann über das Modul nur eien Momentaufnahme (Snapshot) aktiviert und angezeigt werden.  
Es ist derzeit noch nicht absehbar, welchen Funktionsumfang das Modul endgültig umfasst.

### 2. Voraussetzungen

* IP-Symcon ab Version 6.4

### 3. Installation

* Über den Module Store das 'Blink Home System'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen  
`https://github.com/Wilkware/BlinkHomeSystem` oder `git://github.com/Wilkware/BlinkHomeSystem.git`

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
Aktualisierungsintervall | Zeit zwischen 2 Aufnahmen (Standard 60 Minuten), 0 deaktiviert die Aufnahmen. ACHTUNG: zu kurzes Intervall geht auf die Lebensdauer der Batterie!
Zeitplan                 | Zeitraum in dem Aufnahmen im angeegebenen Intervall erfolgen sollen.

> Erweiterte Einstellungen  ...

Name           | Beschreibung
-------------- | ------------------
Anlegen einer Variabel zur Auslösung einer Momentaufnahme der aktuellen Ansicht der Kamera! | Variable für's Webfront zum Auslösen einer Aufnahme
Erstellen einer Variable zur Anzeige des Ladezustands der Batterie! | Variable für's Webfront zum Anzeigen des Ladezustandes

_Aktionsbereich:_

Aktion              | Beschreibung
------------------- | ------------------
ZEITPLAN HINZUFÜGEN | Es wird ein Wochenplan mit 2 Zuständen (Aktiv & Inaktiv) angelegt und in den Einstellung hinterlegt.
SNAPSHOT            | Löst eine Momentaufnahme(Snapshot) aus.
LIVEVIEW            | Anzeige der LiveView Anfrageantwort
SIGNALE             | Anzeige von verschiedenen Signalen (WiFi usw.)
ZURÜCKSETZEN        | Reset des Kommando-Stacks um Kommunikation wieder zu synchronisieren.

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Ident               | Name               | Typ     | Profil     | Beschreibung
------------------- | ------------------ | ------- | ---------- | -------------------
circuit_snapshot    | Zeitplan Snapshot  | event   |            | Wochenplan für Momentaufnahmen
thumbnail           | Bild               | media   |            | Medienobject zum Speichern der Aufnahme
motion_detection    | Bewegungserkennung | boolean | ~Switch    | Variable zum an- und ausschalten der Bewegungserkennung
snapshot            | Auslöser           | integer | BHS.Update | Variable zum Auslösen einer Momentaufnahme

#### Profile

Folgendes Profil wird angelegt:

Name           | Typ       | Beschreibung
-------------- | --------- | ----------------
BHS.Update     | Integer   | Auslöser Profil (1: '►')
BHS.Battery    | Integer   | Batterieladezustandsanzeige (0 ... 3)

### 6. Visualisierung

Man kann die Statusvariablen direkt im WF verlinken.

### 7. PHP-Befehlsreferenz

Ein direkter Aufruf von öffentlichen Funktionen ist nicht notwendig!

### 8. Versionshistorie

v1.8.20241024

* _NEU_: Auslösen einer Direktaufnahme (Clip aufnehmen)
* _NEU_: Neue Option zum automatischen Zurücksetzen des Kommando-Stacks
* _NEU_: Zufälliger Zeit-Offset bei Neustart (un damit auch bei Konfigurationsänderungen)
* _FIX_: Konfigurationsformular vereinheitlicht

v1.7.20240628

* _NEU_: Support für Blink Mini 2
* _NEU_: Support für Anzeige des Ladezustandes von batteriebetriebenen Geräten
* _NEU_: Neue Entwickleroption (Signale) um einige Sensordaten anzuzeigen
* _FIX_: Anzeige-Popup ausgetauscht für fehlerfreie Auflistung

v1.6.20240606

* _NEU_: Support für Blink Indoor Kamera (3rd Gen)
* _NEU_: Ausgabe ins Log wenn Kamera nicht mehr auf Kommandos reagiert (Zombies)
* _NEU_: Neue Entwickleroption (Zurücksetzen) um Kommunikation mit Kameras wieder zu synchronisieren
* _FIX_: Interne Bibliotheken überarbeitet und vereinheitlicht
* _FIX_: Dokumentation überarbeitet

v1.5.20231013

* _NEU_: Bewegungserkennung jetzt auch für Blink Mini verfügbar
* _FIX_: Übersetzungen ausgebaut bzw. vervollständigt
* _FIX_: Blink API Layer erweitert, aktualisiert und neu dokumentiert
* _FIX_: Style-Checks aktualisiert
* _FIX_: Interne Bibliotheken überarbeitet und vereinheitlicht
* _FIX_: Dokumentation überarbeitet

v1.4.20220815

* _FIX_: Anpassungen für Blink Doorbells
* _FIX_: Logging verbessert

v1.3.20220620

* _NEU_: Unterstütztung für Blink Doorbells
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
