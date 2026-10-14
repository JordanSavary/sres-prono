-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mar. 05 mai 2026 à 12:41
-- Version du serveur : 10.11.16-MariaDB-deb12
-- Version de PHP : 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `sresp2611990`
--

--
-- Déchargement des données de la table `matchs`
--

INSERT INTO `matchs` (`id`, `competition_id`, `equipe_a`, `equipe_b`, `date_match`, `score_a`, `score_b`, `phase`, `cote_a`, `cote_nul`, `cote_b`, `forme_a`, `forme_b`) VALUES
(1, 1, 'MEX', 'ZAF', '2026-06-11 21:00:00', NULL, NULL, 'Groupe A', 1.44, 3.95, 5.75, 'WWWDD', 'LWLDL'),
(2, 1, 'KOR', 'CZE', '2026-06-12 04:00:00', NULL, NULL, 'Groupe A', 2.58, 3.00, 2.38, 'WWWLL', 'LWWWW'),
(3, 1, 'CAN', 'BIH', '2026-06-12 21:00:00', NULL, NULL, 'Groupe B', 1.72, 3.47, 3.90, 'DWWDD', 'WWDWW'),
(4, 1, 'USA', 'PRY', '2026-06-13 03:00:00', NULL, NULL, 'Groupe D', 1.92, 3.30, 3.38, 'WWWLL', 'LLWWL'),
(5, 1, 'QAT', 'CHE', '2026-06-13 21:00:00', NULL, NULL, 'Groupe B', 8.75, 5.10, 1.23, 'WLLDL', 'DWDLD'),
(6, 1, 'BRA', 'MAR', '2026-06-14 00:00:00', NULL, NULL, 'Groupe C', 1.51, 3.80, 5.10, 'LWDLW', 'WWLDW'),
(7, 1, 'HTI', 'SCO', '2026-06-14 03:00:00', NULL, NULL, 'Groupe C', 6.40, 4.35, 1.36, 'LWWLD', 'WLWLL'),
(8, 1, 'AUS', 'TUR', '2026-06-14 06:00:00', NULL, NULL, 'Groupe D', 4.00, 3.40, 1.70, 'LLLWW', 'WWDWW'),
(9, 1, 'DEU', 'CUW', '2026-06-14 19:00:00', NULL, NULL, 'Groupe E', 1.01, 11.25, 25.00, 'WWWWW', 'DWDLL'),
(10, 1, 'NLD', 'JPN', '2026-06-14 22:00:00', NULL, NULL, 'Groupe F', 1.92, 3.38, 3.30, 'WDWWD', 'WWWWW'),
(31, 1, 'CIV', 'ECU', '2026-06-15 01:00:00', NULL, NULL, 'Groupe E', 3.07, 2.77, 2.22, 'WWLWW', 'DDWDD'),
(30, 1, 'SWE', 'TUN', '2026-06-15 04:00:00', NULL, NULL, 'Groupe F', 1.86, 3.15, 3.78, 'LLDWW', 'LDLWD'),
(29, 1, 'ESP', 'CPV', '2026-06-15 18:00:00', NULL, NULL, 'Groupe H', 1.86, 3.15, 3.78, 'WWDWD', 'WLLLW'),
(28, 1, 'BEL', 'EGY', '2026-06-15 21:00:00', NULL, NULL, 'Groupe G', 1.57, 3.75, 4.65, 'WDWWD', 'WLLWD'),
(27, 1, 'SAU', 'URY', '2026-06-16 00:00:00', NULL, NULL, 'Groupe H', 5.60, 3.93, 1.45, 'WLDLL', 'WDLDD'),
(26, 1, 'IRN', 'NZL', '2026-06-16 03:00:00', NULL, NULL, 'Groupe G', 1.67, 3.45, 4.30, 'LWLLW', 'DLLLW'),
(25, 1, 'FRA', 'SEN', '2026-06-16 21:00:00', NULL, NULL, 'Groupe I', 1.39, 4.15, 6.25, 'DWWWW', 'WWWWW'),
(24, 1, 'IRQ', 'NOR', '2026-06-17 00:00:00', NULL, NULL, 'Groupe I', 9.25, 5.25, 1.21, 'WWLLW', 'DWWLD'),
(23, 1, 'ARG', 'DZA', '2026-06-17 03:00:00', NULL, NULL, 'Groupe J', 1.36, 4.20, 6.75, 'WWWWW', 'WWLWD'),
(22, 1, 'AUT', 'JOR', '2026-06-17 06:00:00', NULL, NULL, 'Groupe J', 1.29, 4.75, 7.50, 'LWDWW', 'WWLDD'),
(21, 1, 'PRT', 'COD', '2026-06-17 19:00:00', NULL, NULL, 'Groupe K', 1.24, 5.00, 8.50, 'DLWDW', 'WDWLW'),
(20, 1, 'ENG', 'HRV', '2026-06-17 22:00:00', NULL, NULL, 'Groupe L', 1.61, 3.63, 4.50, 'WWWDL', 'WWWWL'),
(19, 1, 'GHA', 'PAN', '2026-06-18 01:00:00', NULL, NULL, 'Groupe L', 1.94, 3.30, 3.32, 'WLLLL', 'WDLDW'),
(18, 1, 'UZB', 'COL', '2026-06-18 04:00:00', NULL, NULL, 'Groupe K', 6.75, 4.30, 1.36, 'LWWWW', 'DWWLL'),
(17, 1, 'CZE', 'ZAF', '2026-06-18 18:00:00', NULL, NULL, 'Groupe A', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(16, 1, 'CHE', 'BIH', '2026-06-18 21:00:00', NULL, NULL, 'Groupe B', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(15, 1, 'CAN', 'QAT', '2026-06-19 00:00:00', NULL, NULL, 'Groupe B', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(14, 1, 'MEX', 'KOR', '2026-06-19 03:00:00', NULL, NULL, 'Groupe A', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(13, 1, 'USA', 'AUS', '2026-06-19 21:00:00', NULL, NULL, 'Groupe D', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(12, 1, 'SCO', 'MAR', '2026-06-20 00:00:00', NULL, NULL, 'Groupe C', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(11, 1, 'BRA', 'HTI', '2026-06-20 03:00:00', NULL, NULL, 'Groupe C', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(32, 1, 'TUR', 'PRY', '2026-06-20 06:00:00', NULL, NULL, 'Groupe D', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(33, 1, 'NLD', 'SWE', '2026-06-20 19:00:00', NULL, NULL, 'Groupe F', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(34, 1, 'DEU', 'CIV', '2026-06-20 21:00:00', NULL, NULL, 'Groupe E', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(35, 1, 'ECU', 'CUW', '2026-06-21 02:00:00', NULL, NULL, 'Groupe E', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(36, 1, 'TUN', 'JPN', '2026-06-21 06:00:00', NULL, NULL, 'Groupe F', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(37, 1, 'ESP', 'SAU', '2026-06-21 18:00:00', NULL, NULL, 'Groupe H', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(38, 1, 'BEL', 'IRN', '2026-06-21 21:00:00', NULL, NULL, 'Groupe G', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(39, 1, 'URY', 'CPV', '2026-06-22 00:00:00', NULL, NULL, 'Groupe H', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(40, 1, 'NZL', 'EGY', '2026-06-22 03:00:00', NULL, NULL, 'Groupe G', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(41, 1, 'ARG', 'AUT', '2026-06-22 19:00:00', NULL, NULL, 'Groupe J', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(42, 1, 'FRA', 'IRQ', '2026-06-22 23:00:00', NULL, NULL, 'Groupe I', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(43, 1, 'NOR', 'SEN', '2026-06-23 02:00:00', NULL, NULL, 'Groupe I', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(44, 1, 'JOR', 'DZA', '2026-06-23 05:00:00', NULL, NULL, 'Groupe J', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(45, 1, 'PRT', 'UZB', '2026-06-23 19:00:00', NULL, NULL, 'Groupe K', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(46, 1, 'ENG', 'GHA', '2026-06-23 22:00:00', NULL, NULL, 'Groupe L', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(47, 1, 'PAN', 'HRV', '2026-06-24 01:00:00', NULL, NULL, 'Groupe L', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(48, 1, 'COL', 'COD', '2026-06-24 04:00:00', NULL, NULL, 'Groupe K', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(49, 1, 'CHE', 'CAN', '2026-06-24 21:00:00', NULL, NULL, 'Groupe B', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(50, 1, 'BIH', 'QAT', '2026-06-24 21:00:00', NULL, NULL, 'Groupe B', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(51, 1, 'MAR', 'HTI', '2026-06-25 00:00:00', NULL, NULL, 'Groupe C', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(52, 1, 'SCO', 'BRA', '2026-06-25 00:00:00', NULL, NULL, 'Groupe C', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(53, 1, 'ZAF', 'KOR', '2026-06-25 03:00:00', NULL, NULL, 'Groupe A', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(54, 1, 'CZE', 'MEX', '2026-06-25 03:00:00', NULL, NULL, 'Groupe A', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(55, 1, 'CUW', 'CIV', '2026-06-25 22:00:00', NULL, NULL, 'Groupe E', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(56, 1, 'ECU', 'DEU', '2026-06-25 22:00:00', NULL, NULL, 'Groupe E', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(57, 1, 'TUN', 'NLD', '2026-06-26 01:00:00', NULL, NULL, 'Groupe F', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(58, 1, 'JPN', 'SWE', '2026-06-26 01:00:00', NULL, NULL, 'Groupe F', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(59, 1, 'TUR', 'USA', '2026-06-26 04:00:00', NULL, NULL, 'Groupe D', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(60, 1, 'PRY', 'AUS', '2026-06-26 04:00:00', NULL, NULL, 'Groupe D', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(61, 1, 'NOR', 'FRA', '2026-06-26 21:00:00', NULL, NULL, 'Groupe I', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(62, 1, 'SEN', 'IRQ', '2026-06-26 21:00:00', NULL, NULL, 'Groupe I', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(63, 1, 'CPV', 'SAU', '2026-06-27 02:00:00', NULL, NULL, 'Groupe H', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(64, 1, 'URY', 'ESP', '2026-06-27 02:00:00', NULL, NULL, 'Groupe H', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(65, 1, 'NZL', 'BEL', '2026-06-27 05:00:00', NULL, NULL, 'Groupe G', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(66, 1, 'EGY', 'IRN', '2026-06-27 05:00:00', NULL, NULL, 'Groupe G', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(67, 1, 'PAN', 'ENG', '2026-06-27 23:00:00', NULL, NULL, 'Groupe L', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(68, 1, 'HRV', 'GHA', '2026-06-27 23:00:00', NULL, NULL, 'Groupe L', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(69, 1, 'COL', 'PRT', '2026-06-28 01:30:00', NULL, NULL, 'Groupe K', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(70, 1, 'COD', 'UZB', '2026-06-28 01:30:00', NULL, NULL, 'Groupe K', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(71, 1, 'DZA', 'AUT', '2026-06-28 04:00:00', NULL, NULL, 'Groupe J', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(72, 1, 'JOR', 'ARG', '2026-06-28 04:00:00', NULL, NULL, 'Groupe J', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(73, 1, 'XXX', 'XXX', '2026-06-28 21:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(74, 1, 'XXX', 'XXX', '2026-06-29 19:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(75, 1, 'XXX', 'XXX', '2026-06-29 22:30:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(76, 1, 'XXX', 'XXX', '2026-06-30 03:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(77, 1, 'XXX', 'XXX', '2026-06-30 19:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(78, 1, 'XXX', 'XXX', '2026-06-30 23:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(79, 1, 'XXX', 'XXX', '2026-07-01 03:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(80, 1, 'XXX', 'XXX', '2026-07-01 18:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(81, 1, 'XXX', 'XXX', '2026-07-01 22:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(82, 1, 'XXX', 'XXX', '2026-07-02 02:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(83, 1, 'XXX', 'XXX', '2026-07-02 21:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(84, 1, 'XXX', 'XXX', '2026-07-03 01:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(85, 1, 'XXX', 'XXX', '2026-07-03 05:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(86, 1, 'XXX', 'XXX', '2026-07-03 20:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(87, 1, 'XXX', 'XXX', '2026-07-04 00:00:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(88, 1, 'XXX', 'XXX', '2026-07-04 03:30:00', NULL, NULL, '16e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(89, 1, 'XXX', 'XXX', '2026-07-04 19:00:00', NULL, NULL, '8e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(90, 1, 'XXX', 'XXX', '2026-07-04 23:00:00', NULL, NULL, '8e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(91, 1, 'XXX', 'XXX', '2026-07-05 22:00:00', NULL, NULL, '8e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(92, 1, 'XXX', 'XXX', '2026-07-06 02:00:00', NULL, NULL, '8e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(93, 1, 'XXX', 'XXX', '2026-07-06 21:00:00', NULL, NULL, '8e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(94, 1, 'XXX', 'XXX', '2026-07-07 02:00:00', NULL, NULL, '8e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(95, 1, 'XXX', 'XXX', '2026-07-07 18:00:00', NULL, NULL, '8e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(96, 1, 'XXX', 'XXX', '2026-07-07 22:00:00', NULL, NULL, '8e de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(97, 1, 'XXX', 'XXX', '2026-07-09 22:00:00', NULL, NULL, 'Quarts de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(98, 1, 'XXX', 'XXX', '2026-07-10 21:00:00', NULL, NULL, 'Quarts de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(99, 1, 'XXX', 'XXX', '2026-07-11 23:00:00', NULL, NULL, 'Quarts de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(100, 1, 'XXX', 'XXX', '2026-07-12 03:00:00', NULL, NULL, 'Quarts de finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(101, 1, 'XXX', 'XXX', '2026-07-14 21:00:00', NULL, NULL, 'Demi-finales', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(102, 1, 'XXX', 'XXX', '2026-07-15 21:00:00', NULL, NULL, 'Demi-finales', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(103, 1, 'XXX', 'XXX', '2026-07-18 23:00:00', NULL, NULL, '3e place', NULL, NULL, NULL, 'DDDDD', 'DDDDD'),
(104, 1, 'XXX', 'XXX', '2026-07-19 21:00:00', NULL, NULL, 'Finale', NULL, NULL, NULL, 'DDDDD', 'DDDDD');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
