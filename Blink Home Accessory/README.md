# 🔦 Blink Home Accessory

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-8.1-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-2.7.20260929-orange.svg?style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/BlinkHomeSystem/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem/actions)

Mit diesem Modul können Sie spezifische Funktionen des Zubehörs nutzen und steuern.

## Inhaltverzeichnis

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

Blink bietet verschiedene Zubehöre für ihre Produkte an. Soweit sie ansteuerbar sind bzw. eigene Funktionalitäten liefern werden sie über dieses Modul abgebildet.  
Es ist derzeit noch nicht absehbar, welchen Funktionsumfang das Modul endgültig umfasst.

### 2. Voraussetzungen

* Symcon ab Version 8.1

### 3. Installation

* Über den Modul Store die Bibliothek _Blink Home System_ installieren.
* Alternativ über das Modul Control folgende URL hinzufügen.  
`https://github.com/Wilkware/BlinkHomeSystem` oder `git://github.com/Wilkware/BlinkHomeSystem.git`

### 4. Einrichtung

* Unter 'Instanz hinzufügen' ist das _Blink Home Zubehör_-Modul unter dem Hersteller 'Amazon' aufgeführt.
* Über den _Blink Home Konfigurator_ kann eine einfache Installation vorgenommen werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

_Einstellungsbereich:_

> 📳 Zubehörinformationen ...

Name           | Beschreibung
-------------- | ------------------
Gerätetyp      | Typbezeichnung (Kamera)
Gerätemodell   | Modellbezeichnung
Geräte-ID      | Interne Gerätenummer (6-stellig)
Netzwerk-ID    | Interne Netzwerknummer (6-stellig)
Ziel-ID        | Gerätenummer des verbundenen Endgerätes (Kamera)

_Aktionsbereich:_

> 🔦 Schalten des Flutlichtes ...

Aktion              | Beschreibung
------------------- | ------------------
AN                  | Schaltet Flutlicht an (Blink Floodlight Mount)
AUS                 | Schaltet Flutlicht aus (Blink Floodlight Mount)

### 5. Statusvariablen

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                 | Typ     | Beschreibung
-------------------- | ------- | ------------------------------
Lichtschalter        | Boolean | Variable zum An- und Ausschalten des Flutlichtes
Batterie             | Integer | Variable zur Anzeige des Ladezustands

### 6. Darstellungen

Die Darstellungen werden direkt an den Statusvariablen hinterlegt, es werden keine Profile angelegt.

Variable             | Darstellung   | Werte
-------------------- | ------------- | ------------------------------
Lichtschalter        | Schalter      | An / Aus
Batterie             | Wertanzeige   | Unbekannt (0), Niedrig (1), Mittel (2), Gut (3)

### 7. Visualisierung

Man kann die Statusvariablen direkt in der Visualisierung verlinken.

### 8. Befehlsreferenz

Ein direkter Aufruf von öffentlichen Funktionen ist nicht notwendig!

### 9. Versionshistorie

v2.7.20260929

* _NEU_: Support von Blink Outdoor 2K+
* _NEU_: Namespaces eingeführt
* _NEU_: Versionierung vereinheitlicht
* _FIX_: Darstellungsparameter korrigiert
* _FIX_: Interne Bibliotheken erweitert und vereinheitlicht

v2.6.20260526

* _NEU_: Konfiguration vereinheitlicht
* _NEU_: Darstellungen werden jetzt lokalisiert

v2.5.20260526

* _FIX_: Kleinere Anpassungen in Bibliotheken

v2.4.20260428

* _NEU_: Liveview via eigenem NodeJS Service

v2.3.20260108

* _NEU_: Umstellung auf Darstellungen
* _NEU_: Modulversion wird in Quellcodesektion angezeigt
* _FIX_: Batterie-Variable kann jetzt wie bei Kameras aktiviert und deaktiviert werden

v2.1.20251125

* _NEU_: Umstellung der Flutlichschaltung

v2.0.20251013

* _NEU_: Support für Anzeige des Batterie-Ladezustandes
* _NEU_: Umstellung auf Strict-Modus (IPSModuleStrict)
* _NEU_: Umstellung auf globale einheitliche Versionsnummer
* _NEU_: Kompatibilität auf IPS 8.1 vereinheitlicht
* _FIX_: Interne Bibliotheken und Konfiguration überarbeitet und vereinheitlicht
* _FIX_: Inline-Dokumentation komplett überarbeitet

v1.0.20240630

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
