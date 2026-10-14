-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mar. 02 juin 2026 à 13:26
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
-- Structure de la table `joueurs`
--

CREATE TABLE `joueurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `pays` varchar(5) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `joueurs`
--

INSERT INTO `joueurs` (`id`, `nom`, `pays`) VALUES
(1, 'Kylian Mbappé', 'FRA'),
(2, 'Antoine Griezmann', 'FRA'),
(3, 'Ousmane Dembélé', 'FRA'),
(4, 'Marcus Thuram', 'FRA'),
(5, 'Randal Kolo Muani', 'FRA'),
(6, 'Lamine Yamal', 'ESP'),
(7, 'Nico Williams', 'ESP'),
(8, 'Álvaro Morata', 'ESP'),
(9, 'Pedri', 'ESP'),
(10, 'Mikel Oyarzabal', 'ESP'),
(11, 'Harry Kane', 'ENG'),
(12, 'Jude Bellingham', 'ENG'),
(13, 'Phil Foden', 'ENG'),
(14, 'Bukayo Saka', 'ENG'),
(15, 'Cole Palmer', 'ENG'),
(16, 'Florian Wirtz', 'DEU'),
(17, 'Jamal Musiala', 'DEU'),
(18, 'Kai Havertz', 'DEU'),
(19, 'Leroy Sané', 'DEU'),
(20, 'Niclas Füllkrug', 'DEU'),
(21, 'Cristiano Ronaldo', 'PRT'),
(22, 'Rafael Leão', 'PRT'),
(23, 'Bernardo Silva', 'PRT'),
(24, 'Bruno Fernandes', 'PRT'),
(25, 'João Félix', 'PRT'),
(26, 'Cody Gakpo', 'NLD'),
(27, 'Memphis Depay', 'NLD'),
(28, 'Donyell Malen', 'NLD'),
(29, 'Wout Weghorst', 'NLD'),
(30, 'Xavi Simons', 'NLD'),
(31, 'Romelu Lukaku', 'BEL'),
(32, 'Kevin De Bruyne', 'BEL'),
(33, 'Lois Openda', 'BEL'),
(34, 'Leandro Trossard', 'BEL'),
(35, 'Jeremy Doku', 'BEL'),
(36, 'Luka Modrić', 'HRV'),
(37, 'Andrej Kramarić', 'HRV'),
(38, 'Ivan Perišić', 'HRV'),
(39, 'Bruno Petković', 'HRV'),
(40, 'Marcel Sabitzer', 'AUT'),
(41, 'Christoph Baumgartner', 'AUT'),
(42, 'Michael Gregoritsch', 'AUT'),
(43, 'Marko Arnautović', 'AUT'),
(44, 'Breel Embolo', 'CHE'),
(45, 'Granit Xhaka', 'CHE'),
(46, 'Ruben Vargas', 'CHE'),
(47, 'Xherdan Shaqiri', 'CHE'),
(48, 'Lyndon Dykes', 'SCO'),
(49, 'Scott McTominay', 'SCO'),
(50, 'Che Adams', 'SCO'),
(51, 'Ryan Christie', 'SCO'),
(52, 'Erling Haaland', 'NOR'),
(53, 'Alexander Sørloth', 'NOR'),
(54, 'Martin Ødegaard', 'NOR'),
(55, 'Mohamed Elyounoussi', 'NOR'),
(56, 'Viktor Gyökeres', 'SWE'),
(57, 'Alexander Isak', 'SWE'),
(58, 'Dejan Kulusevski', 'SWE'),
(59, 'Emil Forsberg', 'SWE'),
(60, 'Edin Džeko', 'BIH'),
(61, 'Ermedin Demirović', 'BIH'),
(62, 'Amer Gojak', 'BIH'),
(63, 'Patrik Schick', 'CZE'),
(64, 'Tomáš Souček', 'CZE'),
(65, 'Adam Hložek', 'CZE'),
(66, 'Vinícius Jr.', 'BRA'),
(67, 'Rodrygo', 'BRA'),
(68, 'Endrick', 'BRA'),
(69, 'Gabriel Martinelli', 'BRA'),
(70, 'Raphinha', 'BRA'),
(71, 'Lionel Messi', 'ARG'),
(72, 'Julián Álvarez', 'ARG'),
(73, 'Lautaro Martínez', 'ARG'),
(74, 'Ángel Di María', 'ARG'),
(75, 'Paulo Dybala', 'ARG'),
(76, 'Darwin Núñez', 'URY'),
(77, 'Luis Suárez', 'URY'),
(78, 'Rodrigo Bentancur', 'URY'),
(79, 'Facundo Torres', 'URY'),
(80, 'Enner Valencia', 'ECU'),
(81, 'Moisés Caicedo', 'ECU'),
(82, 'Gonzalo Plata', 'ECU'),
(83, 'Miguel Almirón', 'PRY'),
(84, 'Antonio Sanabria', 'PRY'),
(85, 'Julio Enciso', 'PRY'),
(86, 'Luis Díaz', 'COL'),
(87, 'Jhon Durán', 'COL'),
(88, 'James Rodríguez', 'COL'),
(89, 'Cucho Hernández', 'COL'),
(90, 'Christian Pulisic', 'USA'),
(91, 'Folarin Balogun', 'USA'),
(92, 'Gio Reyna', 'USA'),
(93, 'Ricardo Pepi', 'USA'),
(94, 'Timothy Weah', 'USA'),
(95, 'Hirving Lozano', 'MEX'),
(96, 'Henry Martín', 'MEX'),
(97, 'Alexis Vega', 'MEX'),
(98, 'Raúl Jiménez', 'MEX'),
(99, 'Santiago Giménez', 'MEX'),
(100, 'Alphonso Davies', 'CAN'),
(101, 'Jonathan David', 'CAN'),
(102, 'Cyle Larin', 'CAN'),
(103, 'Tajon Buchanan', 'CAN'),
(104, 'Rolando Blackburn', 'PAN'),
(105, 'Cecilio Waterman', 'PAN'),
(106, 'Édgar Bárcenas', 'PAN'),
(107, 'Frantzdy Pierrot', 'HTI'),
(108, 'Derrick Etienne Jr.', 'HTI'),
(109, 'Achraf Hakimi', 'MAR'),
(110, 'Youssef En-Nesyri', 'MAR'),
(111, 'Hakim Ziyech', 'MAR'),
(112, 'Sofiane Boufal', 'MAR'),
(113, 'Azzedine Ounahi', 'MAR'),
(114, 'Sadio Mané', 'SEN'),
(115, 'Ismaïla Sarr', 'SEN'),
(116, 'Boulaye Dia', 'SEN'),
(117, 'Nicolas Jackson', 'SEN'),
(118, 'Sébastien Haller', 'CIV'),
(119, 'Franck Kessié', 'CIV'),
(120, 'Simon Adingra', 'CIV'),
(121, 'Jonathan Bamba', 'CIV'),
(122, 'Jordan Ayew', 'GHA'),
(123, 'Antoine Semenyo', 'GHA'),
(124, 'Mohammed Kudus', 'GHA'),
(125, 'Inaki Williams', 'GHA'),
(126, 'Riyad Mahrez', 'DZA'),
(127, 'Islam Slimani', 'DZA'),
(128, 'Youcef Atal', 'DZA'),
(129, 'Houssem Aouar', 'DZA'),
(130, 'Mohamed Salah', 'EGY'),
(131, 'Mostafa Mohamed', 'EGY'),
(132, 'Omar Marmoush', 'EGY'),
(133, 'Trezeguet', 'EGY'),
(134, 'Percy Tau', 'ZAF'),
(135, 'Lyle Foster', 'ZAF'),
(136, 'Themba Zwane', 'ZAF'),
(137, 'Youssef Msakni', 'TUN'),
(138, 'Wahbi Khazri', 'TUN'),
(139, 'Seifeddine Jaziri', 'TUN'),
(140, 'Cédric Bakambu', 'COD'),
(141, 'Yannick Bolasie', 'COD'),
(142, 'Arthur Masuaku', 'COD'),
(143, 'Ryan Mendes', 'CPV'),
(144, 'Garry Rodrigues', 'CPV'),
(145, 'Kaoru Mitoma', 'JPN'),
(146, 'Takumi Minamino', 'JPN'),
(147, 'Daichi Kamada', 'JPN'),
(148, 'Ritsu Doan', 'JPN'),
(149, 'Ayase Ueda', 'JPN'),
(150, 'Son Heung-min', 'KOR'),
(151, 'Hwang Hee-chan', 'KOR'),
(152, 'Lee Jae-sung', 'KOR'),
(153, 'Cho Gue-sung', 'KOR'),
(154, 'Salem Al-Dawsari', 'SAU'),
(155, 'Firas Al-Buraikan', 'SAU'),
(156, 'Mohammed Al-Qasim', 'SAU'),
(157, 'Mehdi Taremi', 'IRN'),
(158, 'Sardar Azmoun', 'IRN'),
(159, 'Allahyar Sayyadmanesh', 'IRN'),
(160, 'Aymen Hussein', 'IRQ'),
(161, 'Amjed Attwan', 'IRQ'),
(162, 'Ali Adnan', 'IRQ'),
(163, 'Eldor Shomurodov', 'UZB'),
(164, 'Jasur Yakhshiboev', 'UZB'),
(165, 'Abbosbek Fayzullaev', 'UZB'),
(166, 'Musa Al-Taamari', 'JOR'),
(167, 'Hamza Al-Dardour', 'JOR'),
(168, 'Akram Afif', 'QAT'),
(169, 'Almoez Ali', 'QAT'),
(170, 'Hassan Al-Haydos', 'QAT'),
(171, 'Mitchell Duke', 'AUS'),
(172, 'Jamie Maclaren', 'AUS'),
(173, 'Martin Boyle', 'AUS'),
(174, 'Craig Goodwin', 'AUS'),
(175, 'Chris Wood', 'NZL'),
(176, 'Marko Stamenic', 'NZL'),
(177, 'Liberato Cacace', 'NZL'),
(178, 'Cuco Martina', 'CUW'),
(179, 'Leandro Bacuna', 'CUW'),
(180, 'Jurgen Locadia', 'CUW');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `joueurs`
--
ALTER TABLE `joueurs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `joueurs`
--
ALTER TABLE `joueurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
