# 🛡️ Blink Home System

[![Version](https://img.shields.io/badge/Symcon-PHP--Bibliothek-purple.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-8.1-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Licence](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

IP-Symcon Modulbibliothek für die Einbindung und Verwaltung des drahtlosen HD-Heimüberwachungs- und Alarmsystem von Blink.

Folgende Module beinhaltet diese Bibliothek:

- __Blink Home Client__ ([Dokumentation](Blink%20Home%20Client))  
Dieses Modul bildet die zentrale Kommunikation mit den Blink Servern ab.

- __Blink Home Configurator__ ([Dokumentation](Blink%20Home%20Configurator))  
Konfigurators für alle im Netzwerk befindlichen Bilnk Geräte.

- __Blink Home Sync Modul__ ([Dokumentation](Blink%20Home%20Sync%20Modul))  
Mit diesem Modul können alle aktivierten Kameras im Netzwerk gesteuert werden.

- __Blink Home Device__ ([Dokumentation](Blink%20Home%20Device))  
Modul welches ein Endgerät (Kamera, Türklingel o.Ä.) repräsentiert.

- __Blink Home Accessory__ ([Dokumentation](Blink%20Home%20Accessory))  
Modul welches ein Zubehör (Flutlich, Halterung) repräsentiert.

## 📜 Historie

- 2026-04-28: v2.4 Liveview via eigenem NodeJS WebSocket Service
- 2026-01-08: v2.3 Neue Authentifizierung, Umstellung auf Darstellungen
- 2025-12-24: v2.2 Fix für Kombatibilität 8.1 und 8.2
- 2025-11-25: v2.1 Support von Blink Mini 2K+, Flutlichtschaltung angepasst
- 2025-10-13: v2.0 Neue Authentifizierung, Support von Outdoor 4, 8.1 Kombatibilität, Visualisierung und Liveview
- 2024-10-29: v1.9 Alarmeinstellungen um 'Letzte Bewegung' erweitert 
- 2024-10-24: v1.8 Zeitversatz bei Neustart von IPS, Automatischer Reset Kommando-Stack, Aufnahme eine Clips auslösen
- 2024-06-28: v1.7 Support für Blink Zubehör (Flutlichthalterung) und Mini 2 Kamera, Anzeige Energieversorgung und Batterielandung
- 2024-06-06: v1.6 Support von Symcon v7 und neuer Blink Indoor Kamera, 6.4 Kombatibilität, Downloads optimiert, neue Entwicklungsoption
- 2023-10-13: v1.5 Bewegungserkennung für Blink Mini, Herunterladen von Video-Clips (Cloud & Lokaler USB Speicher)
- 2022-08-15: v1.4 API für Blink Doorbells angeasst
- 2022-06-20: v1.3 Anpassungen für Blink Doorbells, Login überarbeitet, Erweitertes Logging, Konfigurator verbessert
- 2022-02-14: v1.2 Anpassungen für Blink Mini, Kennwortvalidierung und Overlay, Korrektur Typo
- 2022-01-30: v1.1 Bugfixing, Blink Mini Support, Aufnahmeaktivierung über Zeitplan, Erweitertes Overlay
- 2022-01-10: v1.0 Initialversion

## 👨‍💻 Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der IP-Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## 💰 Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen der Bibliothek würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## ©️ Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
