# ProtokollCleaner

Der Automatismus soll mit Hilfe eines Cronjobs die internen Protokolle, vom internen Teil bereinigt, in den öffentlichen Bereich eines Wiki's kopieren. Der Automatismus sollte durch das Laden einer Website ausgeführt werden können.

clone git

```
    git clone https://github.com/Ref-IT/ProtokollCleaner.git
    git submodule update --init --recursive
```

Einstellungen können in **config.php** und **/framework/config/config.protocol.php** angepasst werden.

Es müssen noch Cronjobs angelegt werden, die bestimmte URLs regelmäßig aufrufen.
Diese können unter *BASE_URL/cron* eingesehen werden. Die Login-Credentials kann man in der **config.php** anpassen.
