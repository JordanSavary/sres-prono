-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : jeu. 02 avr. 2026 à 15:19
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

-- --------------------------------------------------------

--
-- Structure de la table `pays`
--

CREATE TABLE `pays` (
  `nom` varchar(50) NOT NULL,
  `id` varchar(5) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `pays`
--

INSERT INTO `pays` (`nom`, `id`) VALUES
('Albanie', 'ALB'),
('Andorre', 'AND'),
('Autriche', 'AUT'),
('Biélorussie', 'BLR'),
('Belgique', 'BEL'),
('Bosnie-Herzégovine', 'BIH'),
('Bulgarie', 'BGR'),
('Croatie', 'HRV'),
('Chypre', 'CYP'),
('Tchéquie', 'CZE'),
('Danemark', 'DEN'),
('Estonie', 'EST'),
('Finlande', 'FIN'),
('France', 'FRA'),
('Allemagne', 'DEU'),
('Grèce', 'GRC'),
('Hongrie', 'HUN'),
('Islande', 'ISL'),
('Irlande', 'IRL'),
('Italie', 'ITA'),
('Lettonie', 'LVA'),
('Liechtenstein', 'LIE'),
('Lituanie', 'LTU'),
('Luxembourg', 'LUX'),
('Malte', 'MLT'),
('Moldavie', 'MDA'),
('Monaco', 'MCO'),
('Monténégro', 'MNE'),
('Pays-Bas', 'NLD'),
('Macédoine du Nord', 'MKD'),
('Norvège', 'NOR'),
('Pologne', 'POL'),
('Portugal', 'POR'),
('Roumanie', 'ROU'),
('Pays de Galles', 'WAL'),
('Saint-Marin', 'SMR'),
('Serbie', 'SRB'),
('Slovaquie', 'SVK'),
('Slovénie', 'SVN'),
('Espagne', 'ESP'),
('Suède', 'SWE'),
('Suisse', 'CHE'),
('Ukraine', 'UKR'),
('Royaume-Uni', 'GBR'),
('Vatican', 'VAT'),
('Angleterre', 'ENG'),
('Inconnu', 'XXX'),
('Afrique du Sud', 'ZAF'),
('Mexique', 'MEX'),
('Canada', 'CAN'),
('Paraguay', 'PRY'),
('USA', 'USA'),
('Qatar', 'QAT');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `pays`
--
ALTER TABLE `pays`
  ADD PRIMARY KEY (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
