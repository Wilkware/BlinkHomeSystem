# Blink Home Accessory

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.4-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.0.20240628-orange.svg?style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/BlinkHomeSystem/style.yml?branch=main&label=CheckStyle&style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem/actions)

Mit diesem Modul können Sie spezifische Funktionen des Zubehörs nutzen und steuern.

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

Blink bietet verschiedene Zubehöre für ihre Produkte an. Soweit sie ansteuerbar sind bzw. eigene Funktionalitäten liefern werden sie über dieses Modul abgebildet.  
Es ist derzeit noch nicht absehbar, welchen Funktionsumfang das Modul endgültig umfasst.

### 2. Voraussetzungen

* IP-Symcon ab Version 6.4

### 3. Installation

* Über den Module Store das 'Blink Home System'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen  
`https://github.com/Wilkware/BlinkHomeSystem` oder `git://github.com/Wilkware/BlinkHomeSystem.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das _'Blink Home Accessory'_-Modul unter dem Hersteller _'Amazon'_ aufgeführt.
* Über den _'Blink Home Configurator'_ kann eine einfache Installation vorgenommen werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

_Einstellungsbereich:_

> Zubehörinformationen ...

Name           | Beschreibung
-------------- | ------------------
Gerätetyp      | Typbezeichnung (Kamera)
Gerätemodell   | Modellbezeichnung
Geräte-ID      | Interne Gerätenummer (6-stellig)
Netwerk-ID     | Interne Netwerknummer (6-stellig)
Ziel-ID        | Gerätenummer des verbundenen Endgerätes (Kamera)

_Aktionsbereich:_

Aktion              | Beschreibung
------------------- | ------------------
AN                  | Schaltet Flutlicht an (Blink Floodlight Mount)
AUS                 | Schaltet Flutlicht aus (Blink Floodlight Mount)

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Ident               | Name               | Typ     | Profil     | Beschreibung
------------------- | ------------------ | ------- | ---------- | -------------------
switch_light        | Lichtschalter      | boolean | ~Switch    | Variable zum an- und ausschalten des Flutlichtes

#### Profile

Es werden keine zusätzlichen Profile benötigt.

### 6. Visualisierung

Man kann die Statusvariablen direkt im WF verlinken.

### 7. PHP-Befehlsreferenz

Ein direkter Aufruf von öffentlichen Funktionen ist nicht notwendig!

### 8. Versionshistorie

v1.0.20240630

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
