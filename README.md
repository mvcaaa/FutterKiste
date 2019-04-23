# Food management system for ships.
- Version 0.1a
- Original auhtor:(all the credits) Martin Küttner - segeln@fmode.de https://martin-kuettner.de 
- Optimization, semi-automated translation: Andrey Astashov mvc.aaa@gmail.com
- License: GNU General Public License
 
## Purpose: 
- various foods catalog: categories, expiry date and some boat-specific info like storage location
- simple search
- multiple sorting options: by actual/target quantity, shelf life, storage location, type, name
  
## Motivation:
- can be displayed on any display type
- no JavaScript or ActiveX
- clear user interface
   
## History(kept not translated): 
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
__Very important note:__ 
To work properly the Raspberry server (RPI) needs a correctly set time. The RPI has no RTC (real-time clock). 

So, you have four options:
1. you give the RPI a RTC (external hardware module with a battery)
2. you can use an ntp service (needs internet connection)   
3. manually set the clock on each RPI start
4. you get the time from a GPS receiver via the RMC string  (that's how I did it)

__Very important note nummer zwei__: This software designed to run on a local network on a ship. Please dont run it on a server on the Internet. I give no guarantee for its security. The software is neither explosion proof nor dDOS safe.

### Hardwatre requirements:
- any Rasberry PI (so no RPI 3+ :-) ) 
### Software
- MySQL Server
- PHP
- Webserver (Apache2, Lighttp, Nginx)
- optional. MySQL web console like `phpmyadmin`

There are many instructions on the Internet how to install all of this on RPI - please use google.

### Step-by-step instructions:
1. Create MySQL user, database and import tables. In MySQL Server (terminal or phpmyadmin) (Please replace the `xxx` with some random password):
```sql
GRANT USAGE ON *.* TO 'futter'@'localhost' IDENTIFIED BY 'xxx';
GRANT ALL PRIVILEGES ON `futter%`.* TO 'futter'@'localhost';
CREATE DATABASE IF NOT EXISTS `futter`;
```
2. import schema file `struktur.sql` using console `mysql -u futter -p xxx < struktur.sql` or `phpmyadmin`. (replace the `xxx` with previously generated password)
3. fix `futter.php` file: set `$MySqlPwd` variable (line `82`): replace `xxx` with previously generated MySQL password.
4. upload  `futter.php` to the web accessible folder of RPI and point your browser to `http://xxx.xxx.xxx.xxx/futter.php`, where `xxx.xxx.xxx.xxx` - IP address of your RPI

have fun! 

Martin Küttner, 03/2019

Andrey Astashov, Helsinki, 04/2019
