<?php
 /* Nahrungsmittel-Managementsystem für Schiffe.
  *
  * Version 0.1a 
  * Autor: Martin Küttner - https://martin-kuettner.de
  * Lizenz: GNU General Public License
  * 
  * Zweck der Software: 
  *   - Registrierung verschiedener Lebensmittel incl. Haltbarkeitsdatum und Lagerort
  *   - einfaches Interface zum Wiederfinden der Lebensmittel
  *   - Sortierung nach Ist/Sollmenge, Haltbarkeit, Lagerort, Typ, Name
  *  
  * Motivation:
  *   - auf allen Displaytypen darstellbar
  *   - kein Java oder ActiveX
  *   - übersichtliche Nutzeroberfläche
  *   
  * Geschichte: 
  *   Für unsere Auszeit haben wir uns über die Lagerhaltung Gedanken gemacht. 
  *   Viele nutzen dafür eine Exel Tabelle, was meiner Meinung zu viele Nachteile hat.
  *   Diese wären: 
  *     - man braucht immer eine PC
  *     - umständliche Sortierung und Suchfunktion
  *     - schwer portierbar auf mobile Geräte (z.B. beim Einkauf)
  *     - plattformgebunden - man benötigt immer eine Zusatzsoftware Excel, LibreOffice etc.
  *   Da wir auf dem Schiff sowieso schon einen Raspberry in Betrieb haben, 
  *   auf dem ein Web- und SQL Server läuft, bot sich diese Lösung mehr als an.
  *   
  * Installation:
  * 
  *   Sehr wichtiger Hinweis:
  *     Damit das Ganze richtig funktioniert benötigt der Raspberry eine korrekt eingestellte Uhrzeit.
  *     Nun ist das Problem: Der Raspberry hat keine RTC (Real-Time-Clock).
  *     Man hat also 4 Optionen:
  *       - man schenkt dem RPI eine RTC (externes Modul mit Batterie)
  *       - falls der RPI Internetzugang hat nutzt man einen ntp-Dienst
  *       - man stellt die Uhr nach jedem Start manuell 
  *       - man holt sie die Uhrzeit über den RMC String von einem GPS Empfänger
  *         (so mache ich das)
  *
  *   benötigt wird: 
  *     - irgend ein Rasberry PI ;) (also kein RPI 3+)
  *     - mySQL Server
  *     - Webserver (Apache2 oder Lighttp)
  *     - ggf. SQL Adminpanel wie phpmyadmin
  *   Auf die Installation dieser Dienste gehe ich an dieser Stelle nicht ein, 
  *   dazu gibt es genug Anleitungen im Internet
  *   
  *   Im SQL Server (Terminal oder phpmyadmin):
  *   (Bitte das xxx durch ein Passwort ersetzen)

     GRANT USAGE ON *.* TO 'futter'@'localhost' IDENTIFIED BY 'xxx' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;
     CREATE DATABASE IF NOT EXISTS `futter`;
     GRANT ALL PRIVILEGES ON `futter`.* TO 'futter'@'localhost';
     GRANT ALL PRIVILEGES ON `futter\_%`.* TO 'futter'@'localhost';

  *   
  *   Die Struktur kann man am besten über die Datei struktur.sql erzeugen 
  *   (entweder mit "mysql -u futter -p futter < struktur.sql" im Shell einspielen,
  *   oder via des SQL Knopfes im phpmyadmin mittels Copy & Paste in der Datenbank "futter" ausführen)
  *   
  *   Zum Schluß noch in dieser Datei die "xxx" bei "$MySqlPwd" entsprechend einstellen 
  *   das Ganze anschließend in den www Ordner vom Webserver laden.
  *   Gibt man dann http://x.x.x.x (x.x.x.x = IP des Raspberrys) ein, sollte eine Seite 
  *   mit "It works!" erscheinen (=Webserver läuft).
  *   
  *   Über den Link http://x.x.x.x/futter.php sollte das Register aufrufbar sein.
  *
  *   Anregungen, Kritik, Danksagungen, Geld, Ruhm, Ehre ;) bitte an segeln@fmode.de
  *   
  *   viel Spaß!
  *   Martin Küttner 03/2019
  *  
  *  Hinweis: 
  *   Falls die Software auf einem Server im Internet betrieben werden soll gebe ich
  *   keine Garantie für sie Sicherheit. Die Software ist weder Exploidgeprüft noch
  *   dDOS-sicher. Als Einsatzzweck ist ein lokales Netzwerk auf einem Schiff angedacht.
 */

  $MySqlHost = '127.0.0.1';
  $MySqlUser = 'futter';
  $MySqlPwd  = 'xxx';
  $MySqlLink = mysql_connect($MySqlHost, $MySqlUser, $MySqlPwd);
  if (!$MySqlLink) {
    echo "MySQL connection failed ...<br />" . mysql_error() . "<br />";
  }
  $MySqlDb = mysql_select_db('futter', $MySqlLink);
  if (!$MySqlDb) {
    echo "MySQL failed to select Futter ...<br />" . mysql_error() . "<br />";
  }


  //übergabe aus dem header
  $Site = 0; //startseite
  if (isset($_GET['site'])) { 
    $Site = $_GET['site'];
  }
  $art = 0; 
  if (isset($_GET['art'])) { 
    $art = $_GET['art'];
  }
  $edit = 0; 
  if (isset($_GET['edit'])) { 
    $edit = $_GET['edit'];
  }
  $index = 0; 
  if (isset($_GET['index'])) { 
    $index = $_GET['index'];
  }
  $cat = 1; 
  if (isset($_GET['cat'])) { 
    $cat = $_GET['cat'];
  }
  $dir = 2;
  $sort_dir = "ASC"; 
  if (isset($_GET['dir'])) { 
    $sort_dir = ($_GET['dir'] == 1) ? "DESC" : "ASC";
    $dir = ($_GET['dir'] == 1) ? 2 : 1;
  }
  $sort = 1; 
  if (isset($_GET['sort'])) { 
    $sort = $_GET['sort'];
  }
  $wert = array(); 
  if (isset($_POST['wert'])) { 
    $wert = $_POST['wert'];
  }

  $kategorie = array(); 
  if (isset($wert['kategorie'])) { 
    $kategorie = $wert['kategorie'];
    $cat = $kategorie;
  } else if (isset($_GET['cat'])) { 
    $kategorie = $_GET['cat'];
  } else {
    $kategorie = 0;
  }

  $textcolor = "#001a00";
  $backcolor = "#ffffff";

  $SettingsLink = "<a href=\"./futter.php?site=1&cat=".$cat."\">Einstellungen<br /></a>";
  $MainSiteLink = "<a href=\"./futter.php?site=0&cat=".$cat."\">Zurück<br /></a>";


  if ($wert['refresh'] == 1) {
    $DoReload = "<meta http-equiv=\"refresh\" content=\"0; url=./futter.php?site=1&cat=".$cat."\" />\n";
  }
  else if ($wert['refresh'] == 2) {
    $DoReload = "<meta http-equiv=\"refresh\" content=\"0; url=./futter.php?site=0&cat=".$cat."\" />\n";
  }
  else {
    $DoReload = "";
  }

  echo "<html>\n";
    echo "<head>\n";
      echo "<meta charset=\"utf-8\">\n";
      echo $DoReload;
      echo "<title>Futterkiste --> Seite: ".$Site."</title>\n";
    echo "</head>\n";
    echo "<body bgcolor=\"".$backcolor."\">\n";
      echo "<font color=\"$textcolor\">\n";

      switch ($Site) {
        case 0:
          echo $SettingsLink."<br />\n";


          $sql = 'SELECT * FROM kategorie WHERE zeigen = 1 ORDER BY nummer ASC;';
          $result = mysql_query($sql);
          if (!$result) {
            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
          }
          echo "<form method=\"post\">\n";
          echo "<fieldset style=\"border-color:".$textcolor.";\">\n";
          echo "<legend>Kategorie Auswahl</legend>\n";
          echo "<select name=\"wert[kategorie]\" onchange=\"submit()\">\n";
          $act_kat = "Alle Kategorien";
          if ($kategorie == 0) {
              echo "<option value=0 selected>Alle Kategorien</option>\n";
          } else {
              echo "<option value=0>Alle Kategorien</option>\n";
          }
          while ($row = mysql_fetch_assoc($result)) {
            if ($row['nummer'] == $kategorie) {
              $selected = " selected";
              $act_kat = utf8_encode($row['name']);
            } else {
              $selected = "";
            }
            if (!empty(utf8_encode($row['beschreibung']))) {
              echo "<option value=" . $row['nummer'] . $selected . ">" . utf8_encode($row['name']) . " - (".utf8_encode($row['beschreibung']).")" . "</option>\n";
            } else {
              echo "<option value=" . $row['nummer'] . $selected . ">" . utf8_encode($row['name']) . "</option>\n";
            }
          }
          echo "</select>\n"; 
          echo "</fieldset>\n";
          echo "</form>\n";
          mysql_free_result($result);

          $sql = 'SELECT * FROM ort WHERE zeigen = 1 ORDER BY nummer ASC;';
          $result = mysql_query($sql);
          if (!$result) {
            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
          }
          $ort_ary = array();
          while ($row = mysql_fetch_assoc($result)) {
            $ort_ary[$row['nummer']] = $row['ort'];
          }
          mysql_free_result($result);

          $sql = 'SELECT * FROM verpackungsart WHERE zeigen = 1 ORDER BY nummer ASC;';
          $result = mysql_query($sql);
          if (!$result) {
            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
          }
          $verpackungsart_ary = array();
          while ($row = mysql_fetch_assoc($result)) {
            $verpackungsart_ary[$row['nummer']] = utf8_encode($row['name']);
          }
          mysql_free_result($result);

          $sql = 'SELECT * FROM verpackungsmenge WHERE zeigen = 1 ORDER BY nummer ASC;';
          $result = mysql_query($sql);
          if (!$result) {
            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
          }
          $verpackungsmenge_ary = array();
          while ($row = mysql_fetch_assoc($result)) {
            $verpackungsmenge_ary[$row['nummer']] = utf8_encode($row['name']);
          }
          mysql_free_result($result);


          switch ($sort) {
            case 1:
              $sql = ($kategorie > 0) ? 'SELECT * FROM artikel WHERE kategorie = ' . $kategorie . ' ORDER BY UPPER(name) '.$sort_dir.';' : 'SELECT * FROM artikel ORDER BY UPPER(name) '.$sort_dir.';';
              break;
            case 2:
              $sql = ($kategorie > 0) ? 'SELECT * FROM artikel WHERE kategorie = ' . $kategorie . ' ORDER BY UPPER(beschreibung) '.$sort_dir.';' : 'SELECT * FROM artikel ORDER BY UPPER(beschreibung) '.$sort_dir.';';
              break;
            case 3:
              $sql = ($kategorie > 0) ? 'SELECT * FROM artikel WHERE kategorie = ' . $kategorie . ' ORDER BY menge_prozent '.$sort_dir.';' : 'SELECT * FROM artikel ORDER BY menge_prozent '.$sort_dir.';';
              break;
            case 4:
              $sql = ($kategorie > 0) ? 'SELECT * FROM artikel WHERE kategorie = ' . $kategorie . ' ORDER BY mhd '.$sort_dir.';' : 'SELECT * FROM artikel ORDER BY mhd '.$sort_dir.';';
              break;
            case 5:
              $sql = ($kategorie > 0) ? 'SELECT * FROM artikel WHERE kategorie = ' . $kategorie . ' ORDER BY eingelagert '.$sort_dir.';' : 'SELECT * FROM artikel ORDER BY eingelagert '.$sort_dir.';';
              break;
            case 6:
              $sql = ($kategorie > 0) ? 'SELECT * FROM artikel INNER JOIN ort ON artikel.ort = ort.nummer WHERE kategorie = ' . $kategorie . ' ORDER BY artikel.ort '.$sort_dir.';' : 'SELECT * FROM artikel INNER JOIN ort ON artikel.ort = ort.nummer ORDER BY artikel.ort '.$sort_dir.';';
              break;
            default:
              $sql = ($kategorie > 0) ? 'SELECT * FROM artikel WHERE kategorie = ' . $kategorie . ' ORDER BY UPPER(name) '.$sort_dir.';' : 'SELECT * FROM artikel ORDER BY UPPER(name) '.$sort_dir.';';
              break;
          }
          $result = mysql_query($sql);
          if (!$result) {
            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
          }

          echo "<fieldset style=\"border-color:".$textcolor.";\">\n";
          echo "<legend>aktuelle Kategorie:<b> ".$act_kat."</b></legend>\n";

          echo "<table border=\"1\" style=\"width:100%;\">\n";
          echo "<tr>\n";
            echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\"><a href=\"./futter.php?site=0&sort=1&dir=".$dir."&cat=".$cat."\">Name</a></td>\n";
            echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Edit</td>\n";
            echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\"><a href=\"./futter.php?site=0&sort=2&dir=".$dir."&cat=".$cat."\">Beschreibung</a></td>\n";
            echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\"><a href=\"./futter.php?site=0&sort=3&dir=".$dir."&cat=".$cat."\">Bestand</a></td>\n";
            echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\"><a href=\"./futter.php?site=0&sort=4&dir=".$dir."&cat=".$cat."\">noch Haltbar</a></td>\n";
            echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\"><a href=\"./futter.php?site=0&sort=5&dir=".$dir."&cat=".$cat."\">Eingelagert</a></td>\n";
            echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\"><a href=\"./futter.php?site=0&sort=6&dir=".$dir."&cat=".$cat."\">Ort</a></td>\n";
          echo "</tr>\n";

          while ($row = mysql_fetch_assoc($result)) {
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center;\"><a href=./futter.php?site=4&cat=".$cat."&index=".$row['nummer'].">".utf8_encode($row['name'])."</a></td>\n";
              echo "<td style=\"color:$textcolor; text-align:center;\"><a href=./futter.php?site=3&edit=1&cat=".$cat."&index=".$row['nummer'].">&#9998;</a></td>\n";
              echo "<td style=\"color:$textcolor; text-align:center;\">".utf8_encode($row['beschreibung'])."</td>\n";
              if ($row['menge'] != 0) {
              echo "<td style=\"color:$textcolor; text-align:left; width:40%;\"><meter style=\"background: ".$backcolor."; width:100%;\" max=".$row['menge_voll']." min=0 value=".$row['menge']." high=".($row['menge_voll'] * 0.6)." low=".($row['menge_voll'] * 0.3)." optimum=".$row['menge_voll']."></meter> <br />
                    <font size=\"2\">(".$row['menge']." von ".$row['menge_voll']." ".$verpackungsart_ary[$row['einheit']]." à ".$row['einzelgewicht']." ".$verpackungsmenge_ary[$row['mengeneinheit']].") </font></td>\n";
              } else {
              echo "<td style=\"background-color:#ff0000; color:$textcolor; text-align:left; width:40%;\"> <br />
                    <font size=\"2\">(".$row['menge']." von ".$row['menge_voll']." ".$verpackungsart_ary[$row['einheit']]." à ".$row['einzelgewicht']." ".$verpackungsmenge_ary[$row['mengeneinheit']].") </font></td>\n";
              }
              $mhd_days = round(($row['mhd'] - time()) / 86400,0);
              if ($mhd_days > 7) {
                $fieldcolor = "background-color:#33cc33;";
              } else if (($mhd_days <= 7) && ($mhd_days > 1)) {
                $fieldcolor = "font-weight: bold; background-color:#ffd11a;";
              } else {
                $fieldcolor = "font-weight: bold; background-color:#ff6666;";
              }
              echo "<td style=\"color:$textcolor; text-align:center; ".$fieldcolor."\">".$mhd_days." T</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center;\">".(date("j.n.Y",$row['eingelagert']))."</td>\n";
              if ($sort != 6) {
                echo "<td style=\"color:$textcolor; text-align:center;\">".$ort_ary[$row['ort']]."</td>\n";
              } else {
                echo "<td style=\"color:$textcolor; text-align:center;\">".$row['ort']."</td>\n";
              }
            echo "</tr>\n";
          }
          mysql_free_result($result);


          echo "</table>\n";
          echo "</fieldset>\n";
          echo "<a href=\"./futter.php?site=3&edit=0&cat=" . $kategorie . "\">Artikel hinzufügen</a><br />\n";

          break;
        case 1: 
          echo $MainSiteLink."<br />\n";

          $sql = 'SELECT * FROM kategorie';
          $result = mysql_query($sql);
          if (!$result) {
            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
          }

          echo "<fieldset style=\"border-color:".$textcolor.";\">\n";
          echo "<legend>Kategorie Einstellungen</legend>\n";

          echo "<table border=\"1\" style=\"width:100%;\">\n";
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Name</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Beschreibung</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Ändern</td>\n";
            echo "</tr>\n";

            while ($row = mysql_fetch_assoc($result)) {
              echo "<tr>\n";
                echo "<td style=\"color:$textcolor; text-align:center;\">".utf8_encode($row['name'])."</td>\n";
                echo "<td style=\"color:$textcolor; text-align:center;\">".utf8_encode($row['beschreibung'])."</td>\n";
                $zeigen = ($row['zeigen'] == TRUE) ? "ja" : "nein";
                echo "<td style=\"color:$textcolor; text-align:center;\">". $zeigen ."</td>\n";
                $link = "<a href=\"./futter.php?site=2&art=1&edit=1&cat=".$cat."&index=".$row['nummer']."\">ändern</a>";
                echo "<td style=\"color:$textcolor; text-align:center;\">". $link ."</td>\n";
              echo "</tr>\n";
            }
            mysql_free_result($result);
          echo "</table>\n";
          echo "<a href=\"./futter.php?site=2&art=1&edit=0&cat=".$cat."\">Kategorie hinzufügen</a>\n";
          echo "</fieldset>\n";

          $sql = 'SELECT * FROM ort';
          $result = mysql_query($sql);
          if (!$result) {
            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
          }
          echo "<fieldset style=\"border-color:".$textcolor.";\">\n";
          echo "<legend>Lagerort Einstellungen</legend>\n";

          echo "<table border=\"1\" style=\"width:100%;\">\n";
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Ort</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Beschreibung</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Ändern</td>\n";
            echo "</tr>\n";

            while ($row = mysql_fetch_assoc($result)) {
              echo "<tr>\n";
                echo "<td style=\"color:$textcolor; text-align:center;\">".utf8_encode($row['ort'])."</td>\n";
                echo "<td style=\"color:$textcolor; text-align:center;\">".utf8_encode($row['beschreibung'])."</td>\n";
                $zeigen = ($row['zeigen'] == TRUE) ? "ja" : "nein";
                echo "<td style=\"color:$textcolor; text-align:center;\">". $zeigen ."</td>\n";
                $link = "<a href=\"./futter.php?site=2&art=2&edit=1&cat=".$cat."&index=".$row['nummer']."\">ändern</a>";
                echo "<td style=\"color:$textcolor; text-align:center;\">". $link ."</td>\n";
              echo "</tr>\n";
            }
            mysql_free_result($result);
          echo "</table>\n";
          echo "<a href=\"./futter.php?site=2&art=2&edit=0&cat=".$cat."\">Lagerort hinzufügen</a>\n";
          echo "</fieldset>\n";

          $sql = 'SELECT * FROM sprache';
          $result = mysql_query($sql);
          if (!$result) {
            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
          }

          echo "<fieldset style=\"border-color:".$textcolor.";\">\n";
          echo "<legend>Sprachen Einstellungen</legend>\n";

          echo "<table border=\"1\" style=\"width:100%;\">\n";
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sprache</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Ändern</td>\n";
            echo "</tr>\n";

            while ($row = mysql_fetch_assoc($result)) {
              echo "<tr>\n";
                echo "<td style=\"color:$textcolor; text-align:center;\">".utf8_encode($row['name'])."</td>\n";
                $zeigen = ($row['zeigen'] == TRUE) ? "ja" : "nein";
                echo "<td style=\"color:$textcolor; text-align:center;\">". $zeigen ."</td>\n";
                $link = "<a href=\"./futter.php?site=2&art=3&edit=1&cat=".$cat."&index=".$row['nummer']."\">ändern</a>";
                echo "<td style=\"color:$textcolor; text-align:center;\">". $link ."</td>\n";
                echo "</tr>\n";
            }
            mysql_free_result($result);
          echo "</table>\n";
          echo "<a href=\"./futter.php?site=2&art=3&edit=0&cat=".$cat."\">Sprache hinzufügen</a>\n";
          echo "</fieldset>\n";

          $sql = 'SELECT * FROM verpackungsart';
          $result = mysql_query($sql);
          if (!$result) {
            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
          }

          echo "<fieldset style=\"border-color:".$textcolor.";\">\n";
          echo "<legend>Verpackungsart Einstellungen</legend>\n";

          echo "<table border=\"1\" style=\"width:100%;\">\n";
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsart</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Ändern</td>\n";
            echo "</tr>\n";

            while ($row = mysql_fetch_assoc($result)) {
              echo "<tr>\n";
                echo "<td style=\"color:$textcolor; text-align:center;\">".utf8_encode($row['name'])."</td>\n";
                $zeigen = ($row['zeigen'] == TRUE) ? "ja" : "nein";
                echo "<td style=\"color:$textcolor; text-align:center;\">". $zeigen ."</td>\n";
                $link = "<a href=\"./futter.php?site=2&art=4&edit=1&cat=".$cat."&index=".$row['nummer']."\">ändern</a>";
                echo "<td style=\"color:$textcolor; text-align:center;\">". $link ."</td>\n";
                echo "</tr>\n";
            }
            mysql_free_result($result);
          echo "</table>\n";
          echo "<a href=\"./futter.php?site=2&art=4&edit=0&cat=".$cat."\">Verpackungsart hinzufügen</a>\n";
          echo "</fieldset>\n";

          $sql = 'SELECT * FROM verpackungsmenge';
          $result = mysql_query($sql);
          if (!$result) {
            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
          }

          echo "<fieldset style=\"border-color:".$textcolor.";\">\n";
          echo "<legend>Verpackungsmenge Einstellungen</legend>\n";

          echo "<table border=\"1\" style=\"width:100%;\">\n";
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsmenge</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Ändern</td>\n";
            echo "</tr>\n";

            while ($row = mysql_fetch_assoc($result)) {
              echo "<tr>\n";
                echo "<td style=\"color:$textcolor; text-align:center;\">".utf8_encode($row['name'])."</td>\n";
                $zeigen = ($row['zeigen'] == TRUE) ? "ja" : "nein";
                echo "<td style=\"color:$textcolor; text-align:center;\">". $zeigen ."</td>\n";
                $link = "<a href=\"./futter.php?site=2&art=5&edit=1&cat=".$cat."&index=".$row['nummer']."\">ändern</a>";
                echo "<td style=\"color:$textcolor; text-align:center;\">". $link ."</td>\n";
                echo "</tr>\n";
            }
            mysql_free_result($result);
          echo "</table>\n";
          echo "<a href=\"./futter.php?site=2&art=5&edit=0&cat=".$cat."\">Verpackungsmenge hinzufügen</a>\n";
          echo "</fieldset>\n";
          break;
        case 2:

          if (!empty($wert)) {
            $zeigen = (strcmp($wert['zeigen'],'on') == 0) ? 1 : 0;
            $wert['beschreibung'] = mysql_escape_string($wert['beschreibung']);
            $wert['name'] = mysql_escape_string($wert['name']);
            $wert['ort'] = mysql_escape_string($wert['ort']);
            switch ($art) {
              case 1:
                if (!empty($wert['name'])) {
                  switch ($edit) {
                    case 0:
                      $sql = "INSERT INTO kategorie (nummer, name, beschreibung, zeigen) VALUES (NULL, '".utf8_decode($wert['name'])."','".utf8_decode($wert['beschreibung'])."','".$zeigen."');";
                      break;
                    case 1:
                      $sql = "UPDATE kategorie SET name = '".utf8_decode($wert['name'])."', beschreibung = '".utf8_decode($wert['beschreibung'])."', zeigen = '".$zeigen."' WHERE nummer = ".$index.";";
                      break;
                    default:
                      break;
                  }
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                }
                break;
              case 2:
                if (!empty($wert['ort'])) {
                  switch ($edit) {
                    case 0:
                      $sql = "INSERT INTO ort (nummer, ort, beschreibung, zeigen) VALUES (NULL, '".utf8_decode($wert['ort'])."','".utf8_decode($wert['beschreibung'])."','".$zeigen."');";
                      break;
                    case 1:
                      $sql = "UPDATE ort SET ort = '".utf8_decode($wert['ort'])."', beschreibung = '".utf8_decode($wert['beschreibung'])."', zeigen = '".$zeigen."' WHERE nummer = ".$index.";";
                      break;
                    default:
                      break;
                  }
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                }
                break;
              case 3:
                if (!empty($wert['name'])) {
                  switch ($edit) {
                    case 0:
                    $sql = "INSERT INTO sprache (nummer, name, zeigen) VALUES (NULL, '".utf8_decode($wert['name'])."','".$zeigen."');";
                     break;
                    case 1:
                      $sql = "UPDATE sprache SET name = '".utf8_decode($wert['name'])."', zeigen = '".$zeigen."' WHERE nummer = ".$index.";";
                      break;
                    default:
                      break;
                  }
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                }
                break;
              case 4:
                if (!empty($wert['name'])) {
                  switch ($edit) {
                    case 0:
                    $sql = "INSERT INTO verpackungsart (nummer, name, zeigen) VALUES (NULL, '".utf8_decode($wert['name'])."','".$zeigen."');";
                     break;
                    case 1:
                      $sql = "UPDATE verpackungsart SET name = '".utf8_decode($wert['name'])."', zeigen = '".$zeigen."' WHERE nummer = ".$index.";";
                      break;
                    default:
                      break;
                  }
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                }
                break;
              case 5:
                if (!empty($wert['name'])) {
                  switch ($edit) {
                    case 0:
                    $sql = "INSERT INTO verpackungsmenge (nummer, name, zeigen) VALUES (NULL, '".utf8_decode($wert['name'])."','".$zeigen."');";
                     break;
                    case 1:
                      $sql = "UPDATE verpackungsmenge SET name = '".utf8_decode($wert['name'])."', zeigen = '".$zeigen."' WHERE nummer = ".$index.";";
                      break;
                    default:
                      break;
                  }
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                }
                break;
              default:
                break;
            }
          }

          echo "<a href=\"./futter.php?site=1&cat=".$cat."\">Zurück</a>\n";

          echo "<form method=\"post\">\n";
          echo "<fieldset style=\"border-color:".$textcolor.";\">\n";

          switch ($edit) {
            case 0:
             switch ($art) {
                case 1:
                  echo "<legend>Kategorie hinzufügen</legend>\n";
                  echo "<table border=\"1\" style=\"width:100%;\">\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Name</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[name]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Beschreibung</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[beschreibung]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<input name=\"wert[zeigen]\" type=\"checkbox\" checked>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "</table>\n";
                  break;
                case 2:
                  echo "<legend>Ort hinzufügen</legend>\n";
                  echo "<table border=\"1\" style=\"width:100%;\">\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Ort</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[ort]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Beschreibung</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[beschreibung]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<input name=\"wert[zeigen]\" type=\"checkbox\" checked>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "</table>\n";
                  break;
                case 3:
                  echo "<legend>Sprache hinzufügen</legend>\n";
                  echo "<table border=\"1\" style=\"width:100%;\">\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sprache</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[name]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<input name=\"wert[zeigen]\" type=\"checkbox\" checked>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "</table>\n";
                  break;
                case 4:
                  echo "<legend>Verpackungsart hinzufügen</legend>\n";
                  echo "<table border=\"1\" style=\"width:100%;\">\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsart</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[name]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<input name=\"wert[zeigen]\" type=\"checkbox\" checked>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "</table>\n";
                  break;
                case 5:
                  echo "<legend>Verpackungsmenge hinzufügen</legend>\n";
                  echo "<table border=\"1\" style=\"width:100%;\">\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsmenge</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[name]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<input name=\"wert[zeigen]\" type=\"checkbox\" checked>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "</table>\n";
                  break;
                default:
                  break;
              }
              break;
            case 1:
              switch ($art) {
                case 1:
                  $sql = 'SELECT * FROM kategorie WHERE nummer='. $index;
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  $row = mysql_fetch_assoc($result);
                  mysql_free_result($result);

                  echo "<legend>Kategorie ändern</legend>\n";
                  echo "<table border=\"1\" style=\"width:100%;\">\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Name</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[name]\" size=\"100%\" type=\"text\" value=\"" . utf8_encode($row['name']) . "\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Beschreibung</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[beschreibung]\" size=\"100%\" type=\"text\" value=\"" . utf8_encode($row['beschreibung']) . "\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    $checked = ($row['zeigen'] == true) ? "checked" : "";
                    echo "<input name=\"wert[zeigen]\" type=\"checkbox\" " . $checked . ">\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "</table>\n";
                  break;
                case 2:
                  $sql = 'SELECT * FROM ort WHERE nummer='. $index;
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  $row = mysql_fetch_assoc($result);
                  mysql_free_result($result);

                  echo "<legend>Orte ändern</legend>\n";
                  echo "<table border=\"1\" style=\"width:100%;\">\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Ort</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[ort]\" size=\"100%\" type=\"text\" value=\"" . utf8_encode($row['ort']) . "\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Beschreibung</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[beschreibung]\" size=\"100%\" type=\"text\" value=\"" . utf8_encode($row['beschreibung']) . "\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    $checked = ($row['zeigen'] == true) ? "checked" : "";
                    echo "<input name=\"wert[zeigen]\" type=\"checkbox\" " . $checked . ">\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "</table>\n";
                  break;
                case 3:
                  $sql = 'SELECT * FROM sprache WHERE nummer='. $index;

                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  $row = mysql_fetch_assoc($result);
                  mysql_free_result($result);

                  echo "<legend>Sprachen ändern</legend>\n";
                  echo "<table border=\"1\" style=\"width:100%;\">\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sprache</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[name]\" size=\"100%\" type=\"text\" value=\"" . utf8_encode($row['name']) . "\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    $checked = ($row['zeigen'] == true) ? "checked" : "";
                    echo "<input name=\"wert[zeigen]\" type=\"checkbox\" " . $checked . ">\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "</table>\n";
                  break;
                case 4:
                  $sql = 'SELECT * FROM verpackungsart WHERE nummer='. $index;

                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  $row = mysql_fetch_assoc($result);
                  mysql_free_result($result);

                  echo "<legend>Verpackungsart ändern</legend>\n";
                  echo "<table border=\"1\" style=\"width:100%;\">\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsart</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[name]\" size=\"100%\" type=\"text\" value=\"" . utf8_encode($row['name']) . "\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    $checked = ($row['zeigen'] == true) ? "checked" : "";
                    echo "<input name=\"wert[zeigen]\" type=\"checkbox\" " . $checked . ">\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "</table>\n";
                  break;
                case 5:
                  $sql = 'SELECT * FROM verpackungsmenge WHERE nummer='. $index;

                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  $row = mysql_fetch_assoc($result);
                  mysql_free_result($result);

                  echo "<legend>Verpackungsmenge ändern</legend>\n";
                  echo "<table border=\"1\" style=\"width:100%;\">\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsmenge</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<label><input name=\"wert[name]\" size=\"100%\" type=\"text\" value=\"" . utf8_encode($row['name']) . "\"></label>\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Sichtbar</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    $checked = ($row['zeigen'] == true) ? "checked" : "";
                    echo "<input name=\"wert[zeigen]\" type=\"checkbox\" " . $checked . ">\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                  echo "</table>\n";
                  break;
                default:
                  break;
              }
              break;
            default:
              break;
          }
          echo "<input name=\"wert[refresh]\" type=\"test\" value=\"1\" hidden>\n";
          echo "</fieldset>\n";
          echo "<p><button>Speichern</button></p>";
          echo "</form>\n";
          break;
          case 3:
            switch ($edit) {
              case 0:
                if (!empty($wert)) {
                  $wert['name'] = mysql_escape_string($wert['name']);
                  $wert['beschreibung'] = mysql_escape_string($wert['beschreibung']);
                  $wert['sprache_wort'] = mysql_escape_string($wert['sprache_wort']);
                  if (!empty($wert['name'])) {
                    switch ($edit) {
                      case 0:
                        $sql = "INSERT INTO artikel (nummer, name, beschreibung, eingelagert, mhd, ort, kategorie, einheit, einzelgewicht, mengeneinheit, menge, menge_voll,menge_prozent) 
                        VALUES 
                          (NULL, 
                          '".utf8_decode($wert['name'])."',
                          '".utf8_decode($wert['beschreibung'])."',
                          '".strtotime($wert['time_rein'])."',
                          '".strtotime($wert['time_verfallen'])."',
                          '".$wert['ort']."',
                          '".$wert['kategorie']."',
                          '".$wert['einheit']."',
                          '".str_replace(",",".",$wert['einzelgewicht'])."',
                          '".str_replace(",",".",$wert['mengeneinheit'])."',
                          '".str_replace(",",".",$wert['menge'])."',
                          '".str_replace(",",".",$wert['menge_full'])."',
                          '".str_replace(",",".",$wert['menge'])/str_replace(",",".",$wert['menge_full'])."'
                          );";
                          $result = mysql_query($sql);
                          if (!$result) {
                            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                          }
                          if (!empty($wert["sprache_wort"])) {
                            $sql = "INSERT INTO uebersetzung (nummer, artikel, sprache, wort) 
                            VALUES 
                              (NULL, 
                              '".mysql_insert_id()."',
                              '".$wert['sprache']."',
                              '".utf8_decode($wert['sprache_wort'])."'
                              );";
                            $result = mysql_query($sql);
                            if (!$result) {
                              echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                            }
                          }
                        break;
                      case 1:
                        $sql = "UPDATE kategorie SET name = '".utf8_decode($wert['name'])."', beschreibung = '".utf8_decode($wert['beschreibung'])."', zeigen = '".$zeigen."' WHERE nummer = ".$index.";";
                        break;
                      default:
                        break;
                    }
                  }
                }

                echo "<a href=\"./futter.php?site=0&cat=".$cat."\">Zurück</a>\n";
                echo "<form method=\"post\">\n";
                echo "<fieldset style=\"border-color:".$textcolor.";\">\n";
                echo "<legend>Artikel hinzufügen</legend>\n";


                echo "<table border=\"1\" style=\"width:100%;\">\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Name</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[name]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Beschreibung</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[beschreibung]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Datum Eingelagert</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[time_rein]\" size=\"100%\" type=\"text\" value=\"". date("j.n.Y") ."\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verfallsdatum</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[time_verfallen]\" size=\"100%\" type=\"text\" value=\"". date("j.n.Y",time() + 1209600) ."\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Kategorie</td>\n";
                  $sql = 'SELECT * FROM kategorie WHERE zeigen = 1 ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<select name=\"wert[kategorie]\">\n";
                     while ($row = mysql_fetch_assoc($result)) {
                      $selected = ($row['nummer'] == $cat) ? " selected" : "";
                      if (!empty(utf8_encode($row['beschreibung']))) {
                        echo "<option value=" . $row['nummer'] . $selected . ">" . utf8_encode($row['name']) . " - (".utf8_encode($row['beschreibung']).")" . "</option>\n";
                      } else {
                        echo "<option value=" . $row['nummer'] . $selected . ">" . utf8_encode($row['name']) . "</option>\n";
                      }
                    }
                    mysql_free_result($result);
                    echo "</select>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Ort</td>\n";
                  $sql = 'SELECT * FROM ort WHERE zeigen = 1 ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<select name=\"wert[ort]\">\n";
                     while ($row = mysql_fetch_assoc($result)) {
                      if (!empty($row['beschreibung'])) {
                        echo "<option value=" . $row['nummer'] . ">" . utf8_encode($row['ort']) . " - (".utf8_encode($row['beschreibung']).")" . "</option>\n";
                      } else {
                        echo "<option value=" . $row['nummer'] . ">" . utf8_encode($row['ort']) . "</option>\n";
                      }
                    }
                    mysql_free_result($result);
                    echo "</select>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";


                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsart</td>\n";
                  $sql = 'SELECT * FROM verpackungsart WHERE zeigen = 1 ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<select name=\"wert[einheit]\">\n";
                      while ($row = mysql_fetch_assoc($result)) {
                        echo "<option value=" . $row['nummer'] . ">" . utf8_encode($row['name']) . "</option>\n";
                      }
                      mysql_free_result($result);
                    echo "</select>\n";
                  echo "</td>\n";

                echo "</tr>\n";
                echo "<tr>\n";

                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsmenge</td>\n";
                  $sql = 'SELECT * FROM verpackungsmenge WHERE zeigen = 1 ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[einzelgewicht]\" size=\"15px\" type=\"text\" value=\"\"></label>\n";
                    echo "<select name=\"wert[mengeneinheit]\">\n";
                      while ($row = mysql_fetch_assoc($result)) {
                        echo "<option value=" . $row['nummer'] . ">" . utf8_encode($row['name']) . "</option>\n";
                      }
                      mysql_free_result($result);
                    echo "</select>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Menge, aktuell</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[menge]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Menge, 100%</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[menge_full]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Übersetzung</td>\n";
                  $sql = 'SELECT * FROM sprache WHERE zeigen = 1 ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<select name=\"wert[sprache]\">\n";
                     while ($row = mysql_fetch_assoc($result)) {
                        echo "<option value=" . $row['nummer'] . ">" . $row['name'] . "</option>\n";
                    }
                    mysql_free_result($result);
                    echo "</select>\n";
                    echo "<label><input name=\"wert[sprache_wort]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "</table>\n";
                echo "<input name=\"wert[refresh]\" type=\"test\" value=\"2\" hidden>\n";
                echo "</fieldset>\n";
                echo "<p><button>Speichern</button></p>";
                echo "</form>\n";

                break;
              case 1:

                if (!empty($wert)) {
                  if ((strcmp($wert['delete1'],"on") == 0) && (strcmp($wert['delete2'],"on") == 0)) {
                    $sql = 'DELETE FROM artikel WHERE nummer = '.$index.';';
                    $result = mysql_query($sql);
                    if (!$result) {
                      echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                    }
                    $sql = 'DELETE FROM uebersetzung WHERE artikel = '.$index.';';
                    $result = mysql_query($sql);
                    if (!$result) {
                      echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                    }
                  }



                  $wert['name'] = mysql_escape_string($wert['name']);
                  $wert['beschreibung'] = mysql_escape_string($wert['beschreibung']);
                  if (!empty($wert['name'])) {

                    $sql = "UPDATE artikel SET 
                      name          = '".utf8_decode($wert['name'])."',
                      beschreibung  = '".utf8_decode($wert['beschreibung'])."',
                      eingelagert   = '".strtotime($wert['time_rein'])."',
                      mhd           = '".strtotime($wert['time_verfallen'])."',
                      ort           = '".$wert['ort']."',
                      kategorie     = '".$wert['kategorie']."',
                      einheit       = '".$wert['einheit']."',
                      einzelgewicht = '".str_replace(",",".",$wert['einzelgewicht'])."',
                      mengeneinheit = '".str_replace(",",".",$wert['mengeneinheit'])."',
                      menge         = '".str_replace(",",".",$wert['menge'])."',
                      menge_voll    = '".str_replace(",",".",$wert['menge_full'])."',
                      menge_prozent    = '".str_replace(",",".",$wert['menge'])/str_replace(",",".",$wert['menge_full'])."'
                    WHERE nummer    = ".$index.";";

                      $result = mysql_query($sql);

                    if (!$result) {
                      echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                    }
                    $i = 0;
                    while (isset($wert['sprache_nummer_'.$i])) {
                      $wert['sprache_wort_'.$i] = mysql_escape_string($wert['sprache_wort_'.$i]);

                      $sql = "SELECT * FROM uebersetzung WHERE artikel = ".$index." AND nummer = ".$wert['sprache_nummer_'.$i].";";

                      $result = mysql_query($sql);
                      if (!$result) {
                        echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                      }
                      $row = mysql_fetch_assoc($result);
                      mysql_free_result($result);


                      if (!empty($row['nummer'])) {
                        if (!empty($wert['sprache_wort_'.$i])) {
                          $sql = "UPDATE uebersetzung SET 
                            wort = '".utf8_decode($wert['sprache_wort_'.$i])."',
                            sprache = '".$wert['sprache_'.$i]."'
                          WHERE nummer = ".$wert['sprache_nummer_'.$i].";";
                        } else {
                          $sql = "DELETE FROM uebersetzung WHERE nummer = ".$wert['sprache_nummer_'.$i].";";
                        }
                      } else if (!empty($wert['sprache_wort_'.$i])) {
                        $sql = "INSERT INTO uebersetzung (nummer, artikel, sprache, wort)
                          VALUES
                            (NULL,
                              '".$index."',
                              '".$wert['sprache_'.$i]."',
                              '".utf8_decode($wert['sprache_wort_'.$i])."'
                            );";
                      }
                      $result = mysql_query($sql);
                      if (!$result) {
                        echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                      }
                      mysql_free_result($result);

                      $i++;
                      }


                  }
                }


                $sql = 'SELECT * FROM artikel WHERE nummer='. $index;

                $result = mysql_query($sql);
                if (!$result) {
                  echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                }
                $row = mysql_fetch_assoc($result);
                mysql_free_result($result);
                echo "<a href=\"./futter.php?site=0&cat=".$cat."\">Zurück</a>\n";
                echo "<form method=\"post\">\n";
                echo "<fieldset style=\"border-color:".$textcolor.";\">\n";
                echo "<legend>Artikel ändern</legend>\n";


                echo "<table border=\"1\" style=\"width:100%;\">\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Name</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[name]\" size=\"100%\" type=\"text\" value=\"".utf8_encode($row['name'])."\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Beschreibung</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[beschreibung]\" size=\"100%\" type=\"text\" value=\"".utf8_encode($row['beschreibung'])."\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Datum Eingelagert</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[time_rein]\" size=\"100%\" type=\"text\" value=\"". date("j.n.Y",$row['eingelagert']) ."\"></label>\n";
                 echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verfallsdatum</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[time_verfallen]\" size=\"100%\" type=\"text\" value=\"". date("j.n.Y",$row['mhd']) ."\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Kategorie</td>\n";
                  $sql = 'SELECT * FROM kategorie WHERE zeigen = 1 ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<select name=\"wert[kategorie]\">\n";
                     while ($row2 = mysql_fetch_assoc($result)) {
                      $selected = ($row2['nummer'] == $row['kategorie']) ? " selected" : "";
                      if (!empty(utf8_encode($row2['beschreibung']))) {
                        echo "<option value=" . $row2['nummer'] . $selected . ">" . utf8_encode($row2['name']) . " - (".utf8_encode($row2['beschreibung']).")" . "</option>\n";
                      } else {
                        echo "<option value=" . $row2['nummer'] . $selected . ">" . utf8_encode($row2['name']) . "</option>\n";
                      }
                    }
                    mysql_free_result($result);
                    echo "</select>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Ort</td>\n";
                  $sql = 'SELECT * FROM ort WHERE zeigen = 1 ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<select name=\"wert[ort]\">\n";
                     while ($row2 = mysql_fetch_assoc($result)) {
                      $selected = ($row2['nummer'] == $row['ort']) ? " selected" : "";
                      if (!empty(utf8_encode($row2['beschreibung']))) {
                        echo "<option value=" . $row2['nummer'] . $selected . ">" . utf8_encode($row2['ort']) . " - (".utf8_encode($row2['beschreibung']).")" . "</option>\n";
                      } else {
                        echo "<option value=" . $row2['nummer'] . $selected . ">" . utf8_encode($row2['ort']) . "</option>\n";
                      }
                    }
                    mysql_free_result($result);
                    echo "</select>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";

                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsart</td>\n";
                  $sql = 'SELECT * FROM verpackungsart WHERE zeigen = 1 ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<select name=\"wert[einheit]\">\n";
                     while ($row2 = mysql_fetch_assoc($result)) {
                      $selected = ($row2['nummer'] == $row['einheit']) ? " selected" : "";
                        echo "<option value=" . $row2['nummer'] . $selected . ">" . utf8_encode($row2['name']) . "</option>\n";
                      }
                    mysql_free_result($result);
                    echo "</select>\n";
                  echo "</td>\n";

                echo "</tr>\n";
                echo "<tr>\n";

                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsmenge</td>\n";
                  $sql = 'SELECT * FROM verpackungsmenge WHERE zeigen = 1 ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[einzelgewicht]\" size=\"15px\" type=\"text\" value=\"".$row['einzelgewicht']."\"></label>\n";
                    echo "<select name=\"wert[mengeneinheit]\">\n";
                     while ($row2 = mysql_fetch_assoc($result)) {
                      $selected = ($row2['nummer'] == $row['mengeneinheit']) ? " selected" : "";
                        echo "<option value=" . $row2['nummer'] . $selected . ">" . utf8_encode($row2['name']) . "</option>\n";
                      }
                    mysql_free_result($result);
                    echo "</select>\n";
                  echo "</td>\n";

                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Menge, aktuell</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[menge]\" size=\"100%\" type=\"text\" value=\"".$row['menge']."\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Menge, 100%</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo "<label><input name=\"wert[menge_full]\" size=\"100%\" type=\"text\" value=\"".$row['menge_voll']."\"></label>\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Übersetzung</td>\n";
                  $sql = 'SELECT * FROM sprache WHERE zeigen = 1 ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  $i = 0;
                  $sprachen = array();
                  $sprachen_index = array();
                  while ($row = mysql_fetch_assoc($result)) {
                    $sprachen[$i] = utf8_encode($row['name']);
                    $sprachen_index[$i] = $row['nummer'];
                    $i++;
                  }
                  mysql_free_result($result);
                  $sql = 'SELECT * FROM uebersetzung WHERE artikel = '.$index.' ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  $trans_wort = array();
                  $trans_index = array();
                  $i = 0;
                  while ($row = mysql_fetch_assoc($result)) {
                    $trans_wort[$i] = utf8_encode($row['wort']);
                    $trans_index[$i] = $row['sprache'];
                    $trans_nummer[$i] = $row['nummer'];
                    $i++;
                  }
                  mysql_free_result($result);
                  $i = 0;
                  while ($i < sizeof($trans_index)) {

                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                      echo "<select name=\"wert[sprache_".$i."]\">\n";

                        $k = 0;
                        while ($k < sizeof($sprachen_index)) {
                          if ($trans_index[$i] == $sprachen_index[$k]) {
                           $selected = " selected";
                            $found = $i;
                          } else {
                            $selected = "";
                          }
                          echo "<option value=" . $sprachen_index[$k] . $selected . ">" . $sprachen[$k] . "</option>\n";
                          $k++;
                        }

                        echo "</select>\n";
                      echo "<label><input name=\"wert[sprache_wort_".$i."]\" size=\"100%\" type=\"text\" value=\"".$trans_wort[$found]."\"></label>\n";
                      echo "<input name=\"wert[sprache_nummer_".$i."]\" type=\"test\" value=\"".$trans_nummer[$i]."\" hidden>\n";
                    echo "</td>\n";
                    $i++;
                    if ($i < sizeof($trans_index)) {
                      echo "</tr>\n";
                      echo "<tr>\n";
                        echo "<td>\n";
                        echo "</td>\n";
                    }
                  }

                  echo "</tr>\n";

                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Neu</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                      echo "<select name=\"wert[sprache_".$i."]\">\n";

                        $k = 0;
                        while ($k < sizeof($sprachen_index)) {
                          echo "<option value=" . $sprachen_index[$k] . ">" . $sprachen[$k] . "</option>\n";
                          $k++;
                        }

                       echo "</select>\n";
                     echo "<label><input name=\"wert[sprache_wort_".$i."]\" size=\"100%\" type=\"text\" value=\"\"></label>\n";
                     echo "<input name=\"wert[sprache_nummer_".$i."]\" type=\"test\" value=\"0\" hidden>\n";
                   echo "</td>\n";

                echo "</tr>\n";
                  echo "<tr>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Löschen?</td>\n";
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo "<input name=\"wert[delete1]\" type=\"checkbox\" unchecked> beide Boxen bestätigen <input name=\"wert[delete2]\" type=\"checkbox\" unchecked> (Eintrag wird ohne Rückfrage gelöscht!)\n";
                    echo "</td>\n";
                  echo "</tr>\n";
                echo "</table>\n";
                echo "<input name=\"wert[refresh]\" type=\"test\" value=\"2\" hidden>\n";
                echo "</fieldset>\n";
                echo "<p><button>Speichern</button></p>";
                echo "</form>\n";
                break;
              default:
                break;
              }

            break;

        case 4:
          $sql = 'SELECT * FROM artikel WHERE nummer='. $index;
          $result = mysql_query($sql);
          if (!$result) {
            echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
          }
          $row = mysql_fetch_assoc($result);
          mysql_free_result($result);
          echo "<a href=\"./futter.php?site=0&cat=".$cat."\">Zurück</a>\n";
          echo "<fieldset style=\"border-color:".$textcolor.";\">\n";
          echo "<legend>Artikel Info</legend>\n";


          echo "<table border=\"1\" style=\"width:100%;\">\n";
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Name</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
              echo utf8_encode($row['name'])."\n";
            echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Beschreibung</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
              echo utf8_encode($row['beschreibung'])."\n";
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Datum Eingelagert</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
              echo date("j.n.Y",$row['eingelagert'])."\n";
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verfallsdatum</td>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
              echo date("j.n.Y",$row['mhd'])."\n";
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Kategorie</td>\n";
              $sql = 'SELECT * FROM kategorie WHERE zeigen = 1 ORDER BY nummer ASC';
              $result = mysql_query($sql);
              if (!$result) {
                echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
              }
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                while ($row2 = mysql_fetch_assoc($result)) {
                  if ($row2['nummer'] == $row['kategorie']) {
                    if (!empty(utf8_encode($row2['beschreibung']))) {
                      echo utf8_encode($row2['name'])." - (".utf8_encode($row2['beschreibung']).")\n";
                    } else {
                      echo utf8_encode($row2['name']) . "\n";
                    }
                    break;
                  }
                }
                mysql_free_result($result);
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Ort</td>\n";
              $sql = 'SELECT * FROM ort WHERE zeigen = 1 ORDER BY nummer ASC';
              $result = mysql_query($sql);
              if (!$result) {
                echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
              }
              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                while ($row2 = mysql_fetch_assoc($result)) {
                  if ($row2['nummer'] == $row['ort']) {
                    if (!empty(utf8_encode($row2['beschreibung']))) {
                      echo utf8_encode($row2['ort']) . " - (".utf8_encode($row2['beschreibung']).")\n";
                    } else {
                      echo utf8_encode($row2['ort']) . "\n";
                    }
                    break;
                  }
                }
                mysql_free_result($result);
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";

              echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsart</td>\n";
                $sql = 'SELECT * FROM verpackungsart WHERE zeigen = 1 ORDER BY nummer ASC';
                $result = mysql_query($sql);
                if (!$result) {
                  echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                }
                echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  while ($row2 = mysql_fetch_assoc($result)) {
                    if ($row2['nummer'] == $row['einheit']) {
                      echo utf8_encode($row2['name']) . "\n";
                      break;
                    }
                  }
                  mysql_free_result($result);
                echo "</td>\n";

                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Verpackungsmenge</td>\n";
                    $sql = 'SELECT * FROM verpackungsmenge WHERE zeigen = 1 ORDER BY nummer ASC';
                    $result = mysql_query($sql);
                    if (!$result) {
                      echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                    }
                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                    echo $row['einzelgewicht']." ";
                      while ($row2 = mysql_fetch_assoc($result)) {
                        if ($row2['nummer'] == $row['mengeneinheit']) {
                          echo utf8_encode($row2['name']) . "\n";
                          break;
                        }
                      }
                      mysql_free_result($result);
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Menge, aktuell</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo $row['menge']."\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Menge, 100%</td>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                  echo $row['menge_voll']."\n";
                  echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">Übersetzung</td>\n";
                  $sql = 'SELECT * FROM sprache WHERE zeigen = 1 ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  $i = 0;
                  $sprachen = array();
                  $sprachen_index = array();
                  while ($row = mysql_fetch_assoc($result)) {
                    $sprachen[$i] = utf8_encode($row['name']);
                    $sprachen_index[$i] = $row['nummer'];
                    $i++;
                  }
                  mysql_free_result($result);
                  $sql = 'SELECT * FROM uebersetzung WHERE artikel = '.$index.' ORDER BY nummer ASC';
                  $result = mysql_query($sql);
                  if (!$result) {
                    echo "MySQL query failed! ...<br />Query: " . $sql . "<br />" . mysql_error() . "<br />";
                  }
                  $trans_wort = array();
                  $trans_index = array();
                  $i = 0;
                  while ($row = mysql_fetch_assoc($result)) {
                    $trans_wort[$i] = utf8_encode($row['wort']);
                    $trans_index[$i] = $row['sprache'];
                    $trans_nummer[$i] = $row['nummer'];
                    $i++;
                  }
                  mysql_free_result($result);
                  $i = 0;
                  while ($i < sizeof($trans_index)) {

                    echo "<td style=\"color:$textcolor; text-align:center; font-weight: bold;\">\n";
                      $k = 0;
                      while ($k < sizeof($sprachen_index)) {
                        if ($trans_index[$i] == $sprachen_index[$k]) {
                          echo $sprachen[$k] . "\n";
                          $found = $i;
                          break;
                        }
                        $k++;
                      }
                      echo ": ".$trans_wort[$found]."\n";
                    echo "</td>\n";
                    $i++;
                    if ($i < sizeof($trans_index)) {
                      echo "</tr>\n";
                      echo "<tr>\n";
                        echo "<td>\n";
                        echo "</td>\n";
                    }
                  }

                  echo "</tr>\n";

                echo "</table>\n";
                echo "</fieldset>\n";
                echo "<a href=./futter.php?site=3&edit=1&cat=".$cat."&index=".$index.">Artikel ändern</a><br />\n";
                break;
          break;
        default:
          break;
      }
      echo "</font>\n";
    echo "</body>\n";
  echo "</html>\n";
  mysql_close($MySqlLink);
?>

