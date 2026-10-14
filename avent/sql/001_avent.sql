-- ============================================================================
-- Calendrier de l'avent — migration 001
-- Compatible MySQL 8/9 et MariaDB 10.x (même script en local et chez LWS).
--
-- À exécuter UNE SEULE FOIS dans phpMyAdmin, sur la base du site de pronos.
-- Les tables existantes du site de pronos ne sont pas modifiées, sauf l'ajout
-- de la colonne `is_admin` sur `utilisateurs`.
-- ============================================================================

-- 1. Administrateurs : colonne dédiée (le compte « admin » existant est promu).
--    (Si cette ligne échoue avec « Duplicate column name », la colonne existe déjà : continuer.)
ALTER TABLE `utilisateurs` ADD COLUMN `is_admin` TINYINT(1) NOT NULL DEFAULT 0;
UPDATE `utilisateurs` SET `is_admin` = 1 WHERE `pseudo` = 'admin';

-- 2. Réglages globaux
CREATE TABLE IF NOT EXISTS `avent_reglages` (
  `cle`    VARCHAR(50)  NOT NULL,
  `valeur` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `avent_reglages` (`cle`, `valeur`) VALUES
  ('debut',            '2026-12-01'),  -- date de la case n°1
  ('coef_boss',        '2'),           -- multiplicateur des points les jours boss
  ('essais_max',       '2'),           -- parties par joueur et par case (meilleur score gardé)
  ('rattrapage_jours', '7');           -- une case reste jouable du jour J au jour J+6 (7 jours)

-- 3. Les 24 jours : jeu choisi, contenu du jeu, histoire, révélation
CREATE TABLE IF NOT EXISTS `avent_jours` (
  `jour`             TINYINT UNSIGNED NOT NULL,
  `titre`            VARCHAR(120)     NOT NULL DEFAULT '',
  `intro`            TEXT,
  `jeu`              VARCHAR(50)      DEFAULT NULL,
  `config`           LONGTEXT,
  `est_boss`         TINYINT(1)       NOT NULL DEFAULT 0,
  `duree_max`        SMALLINT UNSIGNED NOT NULL DEFAULT 120,
  `illustration`     VARCHAR(255)     DEFAULT NULL,
  `revelation_texte` TEXT,
  `revelation_image` VARCHAR(255)     DEFAULT NULL,
  PRIMARY KEY (`jour`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Histoire « Mystère au Gîte des Flocons » pré-remplie. Jours boss : 5, 6, 12, 13, 19, 20, 24.
INSERT IGNORE INTO `avent_jours` (`jour`, `titre`, `intro`, `est_boss`) VALUES
  (1,  'Les cadeaux ont disparu',      'Le soir de la fête, la salle des cadeaux est vide. Une seule trace dans la neige. Barnabé, le saint-bernard du gîte, renifle l''air : l''enquête commence.', 0),
  (2,  'La salle aux cadeaux',         'Fouille de la salle aux cadeaux : une empreinte étrange et une odeur de cannelle flottent encore.', 0),
  (3,  'Gaston, maître fondeur',       'Premier suspect : Gaston, le maître fondeur. Son alibi est bancal, et il ne cesse de remuer sa fondue.', 0),
  (4,  'Le registre déchiré',          'Le registre du gîte a plusieurs pages arrachées. Qui voulait cacher quoi ?', 0),
  (5,  'L''alibi de Gaston',           'Interrogatoire de Gaston. Son alibi va-t-il tenir face à vos questions ?', 1),
  (6,  'Poursuite dans la neige',      'Des traces mènent hors du gîte. Poursuite dans la neige jusqu''à la cabane du yéti !', 1),
  (7,  'Raymond le yéti',              'Raymond, le yéti du voisinage, est timide et mal réputé. Mais est-il vraiment coupable ?', 0),
  (8,  'La note codée',                'Une note codée a été glissée sous la porte du gîte. Quelqu''un cherche à vous parler.', 0),
  (9,  'Rendez-vous sous le téléphérique', 'Une fois déchiffrée, la note donne rendez-vous à minuit sous le téléphérique.', 0),
  (10, 'Pimprenelle la lutine',        'Deuxième suspecte : Pimprenelle, la lutine, jalouse de la popularité du Père Noël.', 0),
  (11, 'Un trou de deux heures',       'Vérification de l''alibi de Pimprenelle : il manque deux heures dans son emploi du temps.', 0),
  (12, 'L''atelier secret',            'Fouille de l''atelier secret de Pimprenelle. Que cache-t-elle derrière cette porte ?', 1),
  (13, 'Descente nocturne',            'Descente nocturne en téléphérique, dans le noir. Vous y trouvez une pelle.', 1),
  (14, 'Les comptes du gîte',          'Les comptes de Mme Edelweiss, la gérante, sont étrangement tendus ces derniers temps.', 0),
  (15, 'Bernard et les chocolats',     'Bernard, l''ours insomniaque, a mangé tous les chocolats. Mais pas les cadeaux.', 0),
  (16, 'Les guirlandes disparaissent', 'Rebondissement : les guirlandes du gîte disparaissent à leur tour !', 0),
  (17, 'La piste de cannelle',         'Barnabé flaire une piste de cannelle qui descend jusqu''au sous-sol.', 0),
  (18, 'Le coffre du gîte',            'Au sous-sol, vous découvrez un coffre secret. Il est verrouillé.', 0),
  (19, 'Le grenier barricadé',         'Le grenier est barricadé de l''intérieur. Il faut en franchir les défenses.', 1),
  (20, 'Le piège du faux cadeau',      'Pour attirer la voleuse, vous préparez un piège : un faux cadeau posé en évidence.', 1),
  (21, 'Les lunettes de ski',          'Prise au piège, la voleuse s''enfuit et laisse tomber une paire de lunettes de ski.', 0),
  (22, 'Reconstitution',               'Reconstitution des faits : à qui appartiennent ces lunettes ?', 0),
  (23, 'La veille du dénouement',      'Veille du dénouement : il ne reste que trois suspects. Chaque indice compte.', 0),
  (24, 'Le dénouement',                'Le grand soir est arrivé. Il est temps de démasquer le coupable et de retrouver les cadeaux.', 1);

-- 4. Parties jouées
--    essai = 1, 2… pour les vraies parties ; NULL pour les parties de test (administrateurs).
--    La contrainte unique empêche deux parties simultanées sur le même essai.
CREATE TABLE IF NOT EXISTS `avent_parties` (
  `id`       INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `pseudo`   VARCHAR(50)      NOT NULL,
  `jour`     TINYINT UNSIGNED NOT NULL,
  `essai`    TINYINT UNSIGNED DEFAULT NULL,
  `test`     TINYINT(1)       NOT NULL DEFAULT 0,
  `statut`   VARCHAR(12)      NOT NULL DEFAULT 'en_cours',  -- en_cours | termine | abandon
  `debut`    DATETIME(3)      NOT NULL,
  `fin`      DATETIME(3)      DEFAULT NULL,
  `duree_ms` INT UNSIGNED     DEFAULT NULL,
  `score`    TINYINT UNSIGNED NOT NULL DEFAULT 0,             -- 0 à 100, avant coefficient
  `points`   INT UNSIGNED     NOT NULL DEFAULT 0,             -- après coefficient jour boss
  `reussite` TINYINT(1)       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_essai` (`pseudo`, `jour`, `essai`),
  KEY `idx_jour` (`jour`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Joueurs exclus du calendrier (sans toucher à leur compte du site de pronos)
CREATE TABLE IF NOT EXISTS `avent_exclus` (
  `pseudo` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`pseudo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
