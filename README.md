# Nahrungsmittel-Managementsystem für Schiffe.
Version 0.1a
Autor: Martin Küttner - https://martin-kuettner.de
Lizenz: GNU General Public License
 
## Zweck der Software: 
- Registrierung verschiedener Lebensmittel incl. Haltbarkeitsdatum und Lagerort
- einfaches Interface zum Wiederfinden der Lebensmittel
- Sortierung nach Ist/Sollmenge, Haltbarkeit, Lagerort, Typ, Name
  
## Motivation:
- auf allen Displaytypen darstellbar
- kein Java oder ActiveX
- übersichtliche Nutzeroberfläche
   
## Geschichte: 
Für unsere Auszeit haben wir uns über die Lagerhaltung Gedanken gemacht. 
Viele nutzen dafür eine Exel Tabelle, was meiner Meinung zu viele Nachteile hat.
Diese wären: 
- man braucht immer eine PC
- umständliche Sortierung und Suchfunktion
- schwer portierbar auf mobile Geräte (z.B. beim Einkauf)
- plattformgebunden - man benötigt immer eine Zusatzsoftware Excel, LibreOffice etc.

Da wir auf dem Schiff sowieso schon einen Raspberry in Betrieb haben, 
auf dem ein Web- und SQL Server läuft, bot sich diese Lösung mehr als an.
   
## Installation:
 
Sehr wichtiger Hinweis:
Damit das Ganze richtig funktioniert benötigt der Raspberry eine korrekt eingestellte Uhrzeit.
Nun ist das Problem: Der Raspberry hat keine RTC (Real-Time-Clock).
Man hat also 4 Optionen:
- man schenkt dem RPI eine RTC (externes Modul mit Batterie)
- falls der RPI Internetzugang hat nutzt man einen ntp-Dienst
- man stellt die Uhr nach jedem Start manuell 
- man holt sie die Uhrzeit über den RMC String von einem GPS Empfänger
    (so mache ich das)

benötigt wird: 
- irgend ein Rasberry PI ;) (also kein RPI 3+)
- mySQL Server
- Webserver (Apache2 oder Lighttp)
- ggf. SQL Adminpanel wie phpmyadmin
Auf die Installation dieser Dienste gehe ich an dieser Stelle nicht ein, 
dazu gibt es genug Anleitungen im Internet
   
Im SQL Server (Terminal oder phpmyadmin):
(Bitte das xxx durch ein Passwort ersetzen)

GRANT USAGE ON *.* TO 'futter'@'localhost' IDENTIFIED BY 'xxx' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;
CREATE DATABASE IF NOT EXISTS `futter`;
GRANT ALL PRIVILEGES ON `futter`.* TO 'futter'@'localhost';
GRANT ALL PRIVILEGES ON `futter\_%`.* TO 'futter'@'localhost';

   
Die Struktur kann man am besten über die Datei struktur.sql erzeugen 
(entweder mit "mysql -u futter -p futter < struktur.sql" im Shell einspielen,
oder via des SQL Knopfes im phpmyadmin mittels Copy & Paste in der Datenbank "futter" ausführen)
   
Zum Schluß noch in dieser Datei die "xxx" bei "$MySqlPwd" entsprechend einstellen 
das Ganze anschließend in den www Ordner vom Webserver laden.
Gibt man dann http://x.x.x.x (x.x.x.x = IP des Raspberrys) ein, sollte eine Seite 
mit "It works!" erscheinen (=Webserver läuft).
   
Über den Link http://x.x.x.x/futter.php sollte das Register aufrufbar sein.

Anregungen, Kritik, Danksagungen, Geld, Ruhm, Ehre ;) bitte an segeln@fmode.de

viel Spaß!
Martin Küttner 03/2019

Hinweis: 
Falls die Software auf einem Server im Internet betrieben werden soll gebe ich
keine Garantie für sie Sicherheit. Die Software ist weder Exploidgeprüft noch
dDOS-sicher. Als Einsatzzweck ist ein lokales Netzwerk auf einem Schiff angedacht.
