-- phpMyAdmin SQL Dump
-- version 4.2.2
-- http://www.phpmyadmin.net
--
-- Počítač: wh26.farma.gigaserver.cz
-- Vytvořeno: Úte 15. led 2019, 14:34
-- Verze serveru: 5.5.59
-- Verze PHP: 5.3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databáze: `pfservis_cz_hosys`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `hosys_rozpis`
--

CREATE TABLE IF NOT EXISTS `hosys_rozpis` (
  `hosys_rozpis_id` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `hosys_soutez_id` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `datum_od` datetime DEFAULT NULL,
  `datum_do` datetime DEFAULT NULL,
  `status_row` varchar(20) COLLATE utf8_czech_ci DEFAULT NULL,
  `den_title` varchar(60) COLLATE utf8_czech_ci DEFAULT NULL,
  `den` varchar(10) COLLATE utf8_czech_ci DEFAULT NULL,
  `datum_title` varchar(60) COLLATE utf8_czech_ci DEFAULT NULL,
  `datum` varchar(20) COLLATE utf8_czech_ci DEFAULT NULL,
  `cas_title` varchar(60) COLLATE utf8_czech_ci DEFAULT NULL,
  `cas` varchar(20) COLLATE utf8_czech_ci DEFAULT NULL,
  `stadion_title` varchar(150) COLLATE utf8_czech_ci DEFAULT NULL,
  `stadion` varchar(10) COLLATE utf8_czech_ci DEFAULT NULL,
  `soutez_title` varchar(80) COLLATE utf8_czech_ci DEFAULT NULL,
  `soutez` varchar(15) COLLATE utf8_czech_ci DEFAULT NULL,
  `cislo_title` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `cislo` varchar(10) COLLATE utf8_czech_ci DEFAULT NULL,
  `domaci_title` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `domaci` varchar(80) COLLATE utf8_czech_ci DEFAULT NULL,
  `domaci_zkr_title` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `domaci_zkr` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `hoste` varchar(80) COLLATE utf8_czech_ci DEFAULT NULL,
  `hoste_title` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `hoste_zkr_title` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `hoste_zkr` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `status` varchar(60) COLLATE utf8_czech_ci DEFAULT NULL,
  `zmena` tinyint(1) DEFAULT NULL,
  `vlozeno` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `hosys_rozpis_temp`
--

CREATE TABLE IF NOT EXISTS `hosys_rozpis_temp` (
  `hosys_rozpis_id` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `hosys_soutez_id` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `datum_od` datetime DEFAULT NULL,
  `datum_do` datetime DEFAULT NULL,
  `status_row` varchar(20) COLLATE utf8_czech_ci DEFAULT NULL,
  `den_title` varchar(60) COLLATE utf8_czech_ci DEFAULT NULL,
  `den` varchar(10) COLLATE utf8_czech_ci DEFAULT NULL,
  `datum_title` varchar(60) COLLATE utf8_czech_ci DEFAULT NULL,
  `datum` varchar(20) COLLATE utf8_czech_ci DEFAULT NULL,
  `cas_title` varchar(60) COLLATE utf8_czech_ci DEFAULT NULL,
  `cas` varchar(20) COLLATE utf8_czech_ci DEFAULT NULL,
  `stadion_title` varchar(150) COLLATE utf8_czech_ci DEFAULT NULL,
  `stadion` varchar(10) COLLATE utf8_czech_ci DEFAULT NULL,
  `soutez_title` varchar(80) COLLATE utf8_czech_ci DEFAULT NULL,
  `soutez` varchar(15) COLLATE utf8_czech_ci DEFAULT NULL,
  `cislo_title` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `cislo` varchar(10) COLLATE utf8_czech_ci DEFAULT NULL,
  `domaci_title` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `domaci` varchar(80) COLLATE utf8_czech_ci DEFAULT NULL,
  `domaci_zkr_title` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `domaci_zkr` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `hoste` varchar(80) COLLATE utf8_czech_ci DEFAULT NULL,
  `hoste_title` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `hoste_zkr_title` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `hoste_zkr` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `status` varchar(60) COLLATE utf8_czech_ci DEFAULT NULL,
  `zmena` tinyint(1) DEFAULT NULL,
  `vlozeno` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `hosys_sezona`
--

CREATE TABLE IF NOT EXISTS `hosys_sezona` (
`hosys_sezona_id` int(11) NOT NULL,
  `nazev` varchar(15) COLLATE utf8_czech_ci NOT NULL,
  `zacatek` date NOT NULL,
  `konec` date NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `hosys_soutez`
--

CREATE TABLE IF NOT EXISTS `hosys_soutez` (
  `hosys_soutez_id` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `uroven` int(11) NOT NULL,
  `nazev` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `poradi` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `hosys_soutez_tab`
--

CREATE TABLE IF NOT EXISTS `hosys_soutez_tab` (
`hosys_soutez_tab_id` int(50) NOT NULL,
  `hosys_soutez_id` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `hosys_sezona_id` int(11) NOT NULL,
  `html_tabulka` text COLLATE utf8_czech_ci NOT NULL,
  `html_soutez` text COLLATE utf8_czech_ci NOT NULL,
  `zmeneno` datetime NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1162 ;

--
-- Klíče pro exportované tabulky
--

--
-- Klíče pro tabulku `hosys_rozpis`
--
ALTER TABLE `hosys_rozpis`
 ADD PRIMARY KEY (`hosys_rozpis_id`), ADD KEY `hosys_soutez_id` (`hosys_soutez_id`), ADD KEY `datum_od` (`datum_od`);

--
-- Klíče pro tabulku `hosys_rozpis_temp`
--
ALTER TABLE `hosys_rozpis_temp`
 ADD PRIMARY KEY (`hosys_rozpis_id`), ADD KEY `datum_od` (`datum_od`);

--
-- Klíče pro tabulku `hosys_sezona`
--
ALTER TABLE `hosys_sezona`
 ADD PRIMARY KEY (`hosys_sezona_id`), ADD UNIQUE KEY `AK1_hosys_sezona_zacatek_konec` (`zacatek`,`konec`), ADD UNIQUE KEY `AK1_hosys_sezona_nazev` (`nazev`);

--
-- Klíče pro tabulku `hosys_soutez`
--
ALTER TABLE `hosys_soutez`
 ADD PRIMARY KEY (`hosys_soutez_id`);

--
-- Klíče pro tabulku `hosys_soutez_tab`
--
ALTER TABLE `hosys_soutez_tab`
 ADD PRIMARY KEY (`hosys_soutez_tab_id`), ADD UNIQUE KEY `hosys_soutez_id_2` (`hosys_soutez_id`), ADD KEY `hosys_soutez_id` (`hosys_soutez_id`), ADD KEY `hosys_sezona_id` (`hosys_sezona_id`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `hosys_sezona`
--
ALTER TABLE `hosys_sezona`
MODIFY `hosys_sezona_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pro tabulku `hosys_soutez_tab`
--
ALTER TABLE `hosys_soutez_tab`
MODIFY `hosys_soutez_tab_id` int(50) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1162;
--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `hosys_rozpis`
--
ALTER TABLE `hosys_rozpis`
ADD CONSTRAINT `fk_hosys_soutez` FOREIGN KEY (`hosys_soutez_id`) REFERENCES `hosys_soutez` (`hosys_soutez_id`);

--
-- Omezení pro tabulku `hosys_soutez_tab`
--
ALTER TABLE `hosys_soutez_tab`
ADD CONSTRAINT `hosys_soutez_tab_ibfk_1` FOREIGN KEY (`hosys_soutez_id`) REFERENCES `hosys_soutez` (`hosys_soutez_id`),
ADD CONSTRAINT `hosys_soutez_tab_ibfk_2` FOREIGN KEY (`hosys_sezona_id`) REFERENCES `hosys_sezona` (`hosys_sezona_id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
