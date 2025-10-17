# 🔄 Blink Home Sync Modul

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-8.1-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-2.0.20251013-orange.svg?style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/BlinkHomeSystem/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/Wilkware/BlinkHomeSystem/actions)

IP-Symcon Modul für die Steuerung aller aktiven Kameras im gleichen Netzwerk.

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

Derzeit kann über das Modul nur die Aufzeichnung von Bewegungsereignissen für alle aktivierten Kameras im Netzwerk gesteuert werden.
Es ist derzeit noch nicht absehbar, welchen Funktionsumfang das Modul endgültig umfasst.

### 2. Voraussetzungen

* IP-Symcon ab Version 8.1

### 3. Installation

* Über den Module Store das 'Blink Home System'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen  
`https://github.com/Wilkware/BlinkHomeSystem` oder `git://github.com/Wilkware/BlinkHomeSystem.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das _'Blink Home Sync Modul'_-Modul unter dem Hersteller _'Amazon'_ aufgeführt.
* Über den _'Blink Home Configurator'_ kann eine einfache Installation vorgenommen werden  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

* Wie man die Meldungen von Bewegungen bzw. Alarmen via Amazon Alexa einstellt ist im [Forum](https://community.symcon.de/t/modul-blink-home-system/127808/197?u=pitti) beschrieben.

__Konfigurationsseite__:

Einstellungsbereich:

> 📳 Modulinformationen ...

Name           | Beschreibung
-------------- | ------------------
Gerätetyp      | Typbezeichnung (Sync Modul)
Gerätemodell   | Modellbezeichnung (Model 1 oder 2)
Geräte-ID      | Interne Gerätenummer
Netwerk-ID     | Interne Netwerknummer

> 🙌 Bewegungsereignissen ...

Name                     | Beschreibung
------------------------ | ------------------
Variable zum manuellen Aktivieren bzw. Deaktivieren der Bewegungsaufzeichnung erstellen? | Schalter für Aktivieren bzw. Deaktivieren der Bewegungsaufzeichnung für das gesamte Netzwerk
Zeitplan                 | Zeitplan zum Starten und Stoppen von Aufnahmen
ZEITPLAN HINZUFÜGEN      | Es wird ein Wochenplan mit 2 Zuständen (Aktiv & Inaktiv) angelegt und in den Einstellung hinterlegt.
Aktualisierungsintervall | Abfrageintervall des Aktivierungszustandes (0 = AUS)

> 📼 Aufzeichnungen ...

Name           | Beschreibung
-------------- | ------------------
Speicherort    | Kategorie (Ordner) wo die Aufnahmen (Clips) abgelegt werden sollen
Speicherlimit  | Maximale Anzahl an zu speichernden Aufnahmen (max. letzten 25 Aufnahmen)
Nur In-Memory-Cache verwenden (keine Speicherung auf Platte)? | Schalter für Speichermodus
Downloadmodus  | Von welchem Medium sollen die Aufnahmen abgeholt werden (Cloudspeicher, lokaler USB-Speicher oder Beide)


> 🚨 Alarmeinstellungen ...

Name           | Beschreibung
-------------- | ------------------
Anlegen einer Variabel zum Anzeigen einer erfassten Bewegung! | Legt einen Schalter für Alarm (EIN/AUS) an
Erstelle eine Variable, um die Kamera mit der letzte erkannten Bewegung zu speichern! | Legt ein Variable zum erfassen der Kamera wo die letzte Bewegung staffand an
Kamerazuordnung | Zuordnung der Kameras zu einer virtuellen ID (Umweg über Dimmwert eines Lichtes)
Gleichzeitiges Ausführen eines Skriptes | Hinterlegung eines Skriptes das bei Bewegungserkennung aufgerufen wird (IPS_RunScriptEX). Der Zeitstempel (Unix timestamp) wird im Array als 'TIMESTAMP' übergeben. Die ID des ausführenden Moduls wird in 'MODUL' mitgegeben. Die letze Bewegung wird als Text in 'MOTION' und die allgemeine Alarmmeldung als Bool in 'ALERT' übergeben. Ob 'MOTION' oder 'ALERT' mitgegeben wird hängt von der geschaltenen Variable ab. Beides gleichzeitig wird nicht übergeben!

_Aktionsbereich:_

Aktion              | Beschreibung
------------------- | ------------------
NETZWERK            | Ausgabe der Netwerkinformationen.
SYNC MODUL          | Ausgabe der Modulinformationen.
SPEICHERSTATUS      | Ausgabe der Speicherinformationen.

> 🛟 Entwicklungs- und Debuginformationen ...

Aktion         | Beschreibung
-------------- | ------------------
STARTEN        | Schalter für direktes scharf Stellen der Aufnahme
STOPPEN        | Schalter zum direkten Stoppen von Aufnahmen
EVENTS         | Versucht Aufnahmen von der Cloud herunterzuladen (Abo notwendig)
CLIPS          | Versucht Aufnahmen vom lokalen USB-Medium herunterzuladen (USB Stick am Modul notwendig)
ALARM          | Simuliert eine eingehende Alarmmeldung
BEWEGUNG       | Simuliert eine Bewegung mit zufälliger Kamera-ID (zwischen 10 und 100)

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Ident               | Name                  | Typ     | Profil          | Beschreibung
------------------- | --------------------- | ------- | --------------- | -------------------
circuit_snapshot    | Zeitplan Aufnahmen    | event   |                 | Wochenplan für Bewegungsmeldungen
recording           | Aufzeichnung          | boolean | ~Switch         | An/Aus-Schalter für Aufzeichnungen
alert               | Alarm                 | boolean | ~Switch         | An/Aus-Schalter für Alarmmeldungen
download            | Herunterladen         | integer | BHS.Download    | Variable zum Herunterladen von Videoclips

#### Profile

Folgendes Profil wird angelegt:

Name           | Typ       | Beschreibung
-------------- | --------- | ----------------
BHS.Download   | Integer   | Download Profil (1: '►')
BHS.Cameras    | Integer   | Kamera Namen (max. 10 Stück), Profil wird dynamisch erzeugt

### 6. Visualisierung

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

v2.0.20251013

* _NEU_: Support für Blink Outdoor 4
* _NEU_: Umstellung auf Strict-Modus (IPSModuleStrict)
* _NEU_: Umstellung auf globale einheitliche Versionsnummer
* _NEU_: Kompatibilität auf IPS 8.1 vereinheitlicht
* _FIX_: Abholen der Aufzeichnungen nochmal verbessert
* _FIX_: Interne Bibliotheken und Konfiguration überarbeitet und vereinheitlicht
* _FIX_: Inline-Dokumentation komplett überarbeitet

v1.9.20241029

* _NEU_: Alarmeinstellungen wurden um die Möglichkeit erweitert, die Kamera mit der letzten registrierten Bewegung zu speichern
* _FIX_: Dokumentation korriegiert und überarbeitet

v1.8.20241024

* _FIX_: Umstellung der internen Verarbeitung von _utf8_encode_ auf _bin2hex_

v1.6.20240606

* _FIX_: Interne Bibliotheken überarbeitet und vereinheitlicht
* _FIX_: Dokumentation überarbeitet

v1.5.20231013

* _NEU_: Konfigurationsformular komplett überarbeitet
* _NEU_: Synchronisierung des Aufnahmestatus
* _NEU_: Support für Alarmmeldungen über Amazon Alexa
* _NEU_: Ausführen eines Skriptes bei Alarmmeldung
* _NEU_: Herunterladen von Bewegungsaufzeichnungen (Cloud & Lokal)
* _NEU_: Speicherung von Video-Clips als Medien-Objekt (mp4)
* _NEU_: Support für lokale USB Speicher
* _FIX_: Übersetzungen ausgebaut bzw. vervollständigt
* _FIX_: Blink API Layer erweitert, aktualisiert und dokumentiert
* _FIX_: Debug- bzw. Fehlermeldungen erweitert
* _FIX_: Style-Checks aktualisiert
* _FIX_: Interne Bibliotheken überarbeitet und vereinheitlicht
* _FIX_: Dokumentation überarbeitet

v1.1.20220130

* _NEU_: Zeitplan für Aufnahmenaktivierung hinzugefügt
* _FIX_: Funktionen Network() und SyncModul() nur für internen Gebrauch verändert

v1.0.20220110

* _NEU_: Initialversion

## Danksagung

Ich möchte mich für die Unterstützung bei der Entwicklung dieses Moduls bedanken bei ...

* _HarmonyFan_ : für die geniale Idee mit den Dimmwerten bei den Alarmeinstellungen und Alexa
* _richimaint_. _da8ter_, _djtark_ : und viel Andere für das generelle Testen und Melden von Bugs

## Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der IP-Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
