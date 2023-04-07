-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Dic 22, 2022 alle 15:03
-- Versione del server: 10.3.32-MariaDB
-- Versione PHP: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `zpmt`
--
CREATE DATABASE IF NOT EXISTS `zpmt` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `zpmt`;

-- --------------------------------------------------------

--
-- Struttura della tabella `alerts`
--

CREATE TABLE `alerts` (
  `id` int(100) NOT NULL,
  `printer_id` varchar(20) NOT NULL,
  `alert_type` varchar(50) NOT NULL,
  `alert_message` varchar(50) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(500) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) NOT NULL,
  `title` varchar(50) NOT NULL,
  `content` varchar(1000) NOT NULL,
  `checked` int(1) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `paper`
--

CREATE TABLE `paper` (
  `id` int(10) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(500) NOT NULL,
  `type` varchar(50) NOT NULL,
  `width` int(10) NOT NULL,
  `height` int(10) NOT NULL,
  `labels_per_roll` int(10) NOT NULL,
  `rolls_quantity` int(10) NOT NULL,
  `total_labels` int(20) DEFAULT NULL,
  `current_labels` int(20) DEFAULT NULL,
  `threshold` int(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `printers`
--

CREATE TABLE `printers` (
  `id` int(11) NOT NULL,
  `sn` varchar(100) NOT NULL,
  `ip` varchar(30) NOT NULL,
  `model` varchar(250) NOT NULL,
  `resolution` int(11) NOT NULL,
  `name` varchar(500) NOT NULL,
  `online` tinyint(4) NOT NULL,
  `groups` varchar(500) DEFAULT NULL,
  `paper` varchar(500) DEFAULT NULL,
  `ribbon` varchar(500) DEFAULT NULL,
  `ribbon_starting_point` int(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `resources`
--

CREATE TABLE `resources` (
  `id` int(10) NOT NULL,
  `name` varchar(500) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `type` varchar(50) NOT NULL,
  `filename` varchar(500) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `ribbon`
--

CREATE TABLE `ribbon` (
  `id` int(10) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(500) NOT NULL,
  `width` int(10) NOT NULL,
  `roll_length` int(10) NOT NULL,
  `rolls_quantity` int(10) NOT NULL,
  `total_rolls` int(11) DEFAULT NULL,
  `current_length` int(20) DEFAULT NULL,
  `threshold` int(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task_type` varchar(50) NOT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `target_ip` varchar(15) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `completed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `paper`
--
ALTER TABLE `paper`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `printers`
--
ALTER TABLE `printers`
  ADD PRIMARY KEY (`sn`),
  ADD KEY `id` (`id`);

--
-- Indici per le tabelle `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `ribbon`
--
ALTER TABLE `ribbon`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `paper`
--
ALTER TABLE `paper`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `printers`
--
ALTER TABLE `printers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `ribbon`
--
ALTER TABLE `ribbon`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
