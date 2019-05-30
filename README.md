# ProtokollCleaner

## Features im Überblick

 - Anbindung ans SGIS
   - Anwesenheitsliste
 - Protokollkontrolle
 - Protokolle veröffentlichen
 - Protokollerinnerungen bei nicht kontrollierten, nicht abgestimmten oder nicht veröffentlichten Protokollen
 - Beschlussliste
   - automatische Wikiexport
   - durchsuchbar, filterbar, Drucklayout
     - Bsp: nach Jahren, Legislatur, Finanzbeschlüssen, Wahlen, ...
 - Todo Liste
   - Smartphone WebApp Unterstützung
 - Sitzungsplanung
   - E-Mail Einladung
   - Vortragen von Berichten
   - Tagesordnung
 - Skriptzugang für Cronjobs
 
## Zweck

Das Tool stellt eine einfache Möglichkeit dar, interne StuRa Protokolle auf grobe Fehler zu kontrollieren und diese im hochschulöffentlichen Teil des Gremienwikis zu veröffentlichen.

Des Weiteren gibt das Tool einen Überblick über Fixmes, Todos, DeleteMes und Dateianhängen von Protokollen und entfernt interne nicht öffentliche Teile beim Exportieren.

### Erweiterungen

Im Laufe der Entwicklung wurden viele Erweiterungen hinzugefügt.

Das Tool filtert die Protokolle auf Beschlüsse. Dadurch wird es möglich, die StuRa-Beschlussliste automatisch zu aktualisieren, die Beschlüsse nach Themen zu sortieren und durchsuchbar zu machen, sowie zu überprüfen, ob Protokolle in späteren StuRa-Sitzungen abgestimmt wurden.
Rückwirkend wurden alle im Wiki veröffentlichten Stura-Beschlüsse importiert. 

Mittlerweile ist auch die Sitzungsplanung über dieses Tool möglich. So kann man Themen/Tops einreichen, Referatsberichte vortragen und Dateianhänge bereitstellen. 
Ist eine konkrete Sitzung geplant, so werden Sitzungsleitung und Protokollverantwortliche über ihre Aufgaben informiert und rechtzeitig alle StuRäte per E-Mail mit einer Tagesordnung und Protokollhinweisen eingeladen. Die Sitzungsleitung kann in Vorbereitung der Sitzung eine aktuelle Anwesenheitsliste erstellen und drucken.

Die Tagesordnung wird hochschulöffentlich zur Verfügung gestellt.

## Installation

1. Repository in Webserver clonen:

clone git

```
    git clone https://github.com/Ref-IT/ProtokollCleaner.git
    git submodule update --init --recursive
```

2. Einspielen des DB Modells mit Hilfe von MySQL Workbench

3. Anpassen der Konfigurationsdatei config.sample.php

## Installierte Instanzen

- https://helfer.stura.tu-ilmenau.de/protocolhelper/

