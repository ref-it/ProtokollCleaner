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
```

2. die git submodule wurden gegen composer ausgetauscht

```
    composer install
```

3. Einspielen des DB Modells mit Hilfe von MySQL Workbench

4. Anpassen der Konfigurationsdatei config.sample.php

## Installierte Instanzen

- https://helfer.stura.tu-ilmenau.de/protocolhelper/

## Externe Bibliotheken

- php
  - phpMailer
  - guzzle
  - defuse-crypto

- js/css
  - serviceWorker
  - screenfull
  - push.js
  - jQuery
  - jQueryUI
  - jQuery-DateFormat
  - jQueryUI-widget-combobox
  - dropzone
  - bootstrap 
  - codeMirror
  - iLitePhoto

## nginx config

To prevent access to filestorage location, make nginx config look something like this.
(Apache will handle this via htaccess and rewrite engine.)

```
location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~* ^/files/get/filestorage/(.+){
        try_files $uri /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
```

