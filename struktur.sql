SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

USE futter;

CREATE TABLE IF NOT EXISTS `artikel` (
`nummer` int(11) NOT NULL,
  `name` text CHARACTER SET latin1 COLLATE latin1_german2_ci,
  `beschreibung` text CHARACTER SET latin1 COLLATE latin1_german2_ci,
  `eingelagert` int(11) NOT NULL,
  `mhd` int(11) NOT NULL,
  `ort` int(11) DEFAULT NULL,
  `kategorie` int(11) DEFAULT NULL,
  `einheit` int(11) NOT NULL,
  `einzelgewicht` text COLLATE utf8_bin,
  `mengeneinheit` double NOT NULL,
  `menge` double DEFAULT NULL,
  `menge_voll` double DEFAULT NULL,
  `menge_prozent` double DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `kategorie` (
`nummer` int(11) NOT NULL,
  `name` text CHARACTER SET latin1 COLLATE latin1_german2_ci,
  `beschreibung` text CHARACTER SET latin1 COLLATE latin1_german2_ci,
  `zeigen` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `ort` (
`nummer` int(11) NOT NULL,
  `ort` text CHARACTER SET latin1 COLLATE latin1_german2_ci,
  `beschreibung` text CHARACTER SET latin1 COLLATE latin1_german2_ci,
  `zeigen` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `sprache` (
`nummer` int(11) NOT NULL,
  `name` text CHARACTER SET latin1 COLLATE latin1_german2_ci,
  `zeigen` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `uebersetzung` (
`nummer` int(11) NOT NULL,
  `artikel` int(11) DEFAULT NULL,
  `sprache` int(11) NOT NULL,
  `wort` text CHARACTER SET latin1 COLLATE latin1_german2_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `verpackungsart` (
`nummer` int(11) NOT NULL,
  `name` text CHARACTER SET latin1 COLLATE latin1_german2_ci NOT NULL,
  `zeigen` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `verpackungsmenge` (
`nummer` int(11) NOT NULL,
  `name` text CHARACTER SET latin1 COLLATE latin1_german2_ci NOT NULL,
  `zeigen` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


ALTER TABLE `artikel`
 ADD PRIMARY KEY (`nummer`), ADD UNIQUE KEY `index` (`nummer`);

ALTER TABLE `kategorie`
 ADD PRIMARY KEY (`nummer`), ADD UNIQUE KEY `Index` (`nummer`);

ALTER TABLE `ort`
 ADD PRIMARY KEY (`nummer`), ADD UNIQUE KEY `index` (`nummer`);

ALTER TABLE `sprache`
 ADD PRIMARY KEY (`nummer`);

ALTER TABLE `uebersetzung`
 ADD PRIMARY KEY (`nummer`);

ALTER TABLE `verpackungsart`
 ADD PRIMARY KEY (`nummer`);

ALTER TABLE `verpackungsmenge`
 ADD PRIMARY KEY (`nummer`);

ALTER TABLE `artikel`
MODIFY `nummer` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

ALTER TABLE `kategorie`
MODIFY `nummer` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

ALTER TABLE `ort`
MODIFY `nummer` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

ALTER TABLE `sprache`
MODIFY `nummer` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

ALTER TABLE `uebersetzung`
MODIFY `nummer` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

ALTER TABLE `verpackungsart`
MODIFY `nummer` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

ALTER TABLE `verpackungsmenge`
MODIFY `nummer` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
