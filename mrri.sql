-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3308
-- Généré le : ven. 09 oct. 2026 à 13:16
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `mrri`
--

-- --------------------------------------------------------

--
-- Structure de la table `actualites`
--

DROP TABLE IF EXISTS `actualites`;
CREATE TABLE IF NOT EXISTS `actualites` (
  `id_actu` int NOT NULL AUTO_INCREMENT,
  `contenu` text COLLATE utf8mb4_unicode_ci,
  `titre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_publication` date DEFAULT NULL,
  `id_structure` int DEFAULT NULL,
  PRIMARY KEY (`id_actu`),
  KEY `fk_actualites_structure` (`id_structure`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `actualites_photos`
--

DROP TABLE IF EXISTS `actualites_photos`;
CREATE TABLE IF NOT EXISTS `actualites_photos` (
  `id_actulites_photos` int NOT NULL AUTO_INCREMENT,
  `Photos_actu_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_actualites` int DEFAULT NULL,
  `Position` int DEFAULT NULL,
  PRIMARY KEY (`id_actulites_photos`),
  KEY `fk_actualites_photos` (`id_actualites`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id_cat` int NOT NULL AUTO_INCREMENT,
  `nom_cat` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int DEFAULT NULL,
  PRIMARY KEY (`id_cat`),
  UNIQUE KEY `nom_cat` (`nom_cat`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id_cat`, `nom_cat`, `parent_id`) VALUES
(1, 'INSTITUTIONS CONSTITUTIONNELLES PARLEMENTAIRES', NULL),
(2, 'INSTITUTIONS CONSTITUTIONNELLES NON PARLEMENTAIRES', NULL),
(3, 'INSTITUTIONS À CARACTÈRE JURIDICTIONNEL', 2),
(4, 'INSTITUTIONS DE CONSEIL', 2),
(5, 'Autorité Administrative Indépendante', 2),
(6, 'Autorités chargées de la Protection et des Libertés', 5),
(7, 'Autorités de Conseils', 5),
(8, 'Autorités à caractère Economique et Financier', 5),
(10, 'Autorités de Régulation', 5);

-- --------------------------------------------------------

--
-- Structure de la table `consultations`
--

DROP TABLE IF EXISTS `consultations`;
CREATE TABLE IF NOT EXISTS `consultations` (
  `id_consultation` int NOT NULL AUTO_INCREMENT,
  `id_structure` int DEFAULT NULL,
  `id_createur` int DEFAULT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci,
  `date_ouverture` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_fermeture` datetime DEFAULT NULL,
  `statut` enum('ouverte','fermee') COLLATE utf8mb4_unicode_ci DEFAULT 'ouverte',
  PRIMARY KEY (`id_consultation`),
  KEY `fk_consultations_structure` (`id_structure`),
  KEY `fk_consultations_createur` (`id_createur`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `contributions`
--

DROP TABLE IF EXISTS `contributions`;
CREATE TABLE IF NOT EXISTS `contributions` (
  `id_contribution` int NOT NULL AUTO_INCREMENT,
  `id_consultation` int NOT NULL,
  `email_contributeur` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('en_attente','validee','rejetee') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `date_contribution` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_contribution`),
  KEY `fk_contribution_consultation` (`id_consultation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demandes`
--

DROP TABLE IF EXISTS `demandes`;
CREATE TABLE IF NOT EXISTS `demandes` (
  `id_demande` int NOT NULL AUTO_INCREMENT,
  `titre_theme` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contexte` text COLLATE utf8mb4_unicode_ci,
  `problematique` text COLLATE utf8mb4_unicode_ci,
  `objectifs` text COLLATE utf8mb4_unicode_ci,
  `utilisateurs_concernes` text COLLATE utf8mb4_unicode_ci,
  `contraintes` text COLLATE utf8mb4_unicode_ci,
  `delai_souhaite` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_point_focal` int NOT NULL,
  `statut` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `reponse_agent` text COLLATE utf8mb4_unicode_ci,
  `id_agent_traitant` int DEFAULT NULL,
  `date_soumission` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_reponse` datetime DEFAULT NULL,
  PRIMARY KEY (`id_demande`),
  KEY `fk_demandes_point_focal` (`id_point_focal`),
  KEY `fk_demandes_agent` (`id_agent_traitant`)
) ;

--
-- Déchargement des données de la table `demandes`
--

INSERT INTO `demandes` (`id_demande`, `titre_theme`, `contexte`, `problematique`, `objectifs`, `utilisateurs_concernes`, `contraintes`, `delai_souhaite`, `id_point_focal`, `statut`, `reponse_agent`, `id_agent_traitant`, `date_soumission`, `date_reponse`) VALUES
(1, 'Plateforme de centralisation et de consultation des informations des structures sous tutelles', 'besoin de se renseigner sur les infos d\'une structure', 'Comment concevoir et mettre en place une plateforme permettant de centraliser, organiser et faciliter la consultation des informations relatives aux structures sous tutelle du MRRI, tout en garantissant la fiabilité, la mise à jour et la sécurité des données ?', 'faciliter la consultation des infors sur lesstructurespermettre au citoyen de consulter les informations d\'une structure', 'les agents du ministère , les points focaux, les citoyen', 'la base de données non complète', '2 mois', 4, 'en_cours', 'courage', 2, '2026-09-11 03:56:57', '2026-09-11 03:57:32'),
(2, 'plateforme de visionage', 'prbelme', 'aucun', 'test', 'ictoyen', 'aucun', '2', 4, 'en_cours', '', 2, '2026-09-11 08:04:43', '2026-09-15 08:37:04'),
(3, 'Plateforme de centralisation et de consultation des informations des structures sous tutelles', 'd', 'comment centraliser ces infos', 'pouvoir consulter ses infos de manière fiable', 'citoyen & agent interne', 'base de données', '2 mois', 4, 'en_attente', NULL, NULL, '2026-09-22 16:23:44', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `demandes_structures`
--

DROP TABLE IF EXISTS `demandes_structures`;
CREATE TABLE IF NOT EXISTS `demandes_structures` (
  `id_demande_structure` int NOT NULL AUTO_INCREMENT,
  `id_demande` int NOT NULL,
  `id_structure` int NOT NULL,
  PRIMARY KEY (`id_demande_structure`),
  UNIQUE KEY `unique_demande_structure` (`id_demande`,`id_structure`),
  KEY `fk_ds_structure` (`id_structure`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `demandes_structures`
--

INSERT INTO `demandes_structures` (`id_demande_structure`, `id_demande`, `id_structure`) VALUES
(13, 1, 1),
(18, 1, 2),
(14, 1, 3),
(9, 1, 4),
(16, 1, 5),
(15, 1, 6),
(10, 1, 7),
(6, 1, 8),
(8, 1, 9),
(19, 1, 10),
(17, 1, 11),
(11, 1, 12),
(7, 1, 13),
(12, 1, 14),
(4, 1, 15),
(1, 1, 16),
(3, 1, 17),
(2, 1, 18),
(5, 1, 19),
(21, 2, 10),
(20, 2, 19),
(22, 3, 10);

-- --------------------------------------------------------

--
-- Structure de la table `documents`
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
  `id_document` int NOT NULL AUTO_INCREMENT,
  `doc_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom_affichage` varchar(225) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_public` tinyint(1) DEFAULT NULL,
  `id_structure` int DEFAULT NULL,
  PRIMARY KEY (`id_document`),
  KEY `fk_documents_structure` (`id_structure`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `documents`
--

INSERT INTO `documents` (`id_document`, `doc_url`, `nom_affichage`, `est_public`, `id_structure`) VALUES
(3, '6aa133d285e31.pdf', 'test2 upload', 0, 10),
(4, '6aa13c3f5ed6d.docx', 'test3 upload', 0, 10),
(5, '6aa14a8790eb6.docx', 'test3 upload', 0, 10);

-- --------------------------------------------------------

--
-- Structure de la table `structure`
--

DROP TABLE IF EXISTS `structure`;
CREATE TABLE IF NOT EXISTS `structure` (
  `id_struc` int NOT NULL AUTO_INCREMENT,
  `nom_struc` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mission` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `directeur_general` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_cat` int NOT NULL,
  `sigle` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_responsable` int DEFAULT NULL,
  PRIMARY KEY (`id_struc`),
  KEY `fk_structure_categorie` (`id_cat`),
  KEY `fk_structure_responsable` (`id_responsable`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `structure`
--

INSERT INTO `structure` (`id_struc`, `nom_struc`, `adresse`, `telephone`, `email`, `mission`, `site_web`, `date_creation`, `directeur_general`, `id_cat`, `sigle`, `id_responsable`) VALUES
(1, 'Cour Constitutionnelle', 'Boulevard de l’indépendance', NULL, NULL, NULL, NULL, NULL, 'Dieudonné ABA’A OWONO', 1, NULL, NULL),
(2, 'Sénat ', 'Palais OMAR BONGO ON', NULL, NULL, NULL, NULL, NULL, 'Paulette MISSAMBO', 1, NULL, NULL),
(3, 'Cour Constitutionnelle', 'Boulevard de l’indépendance', NULL, NULL, NULL, NULL, NULL, 'Dieudonné ABA’A OWONO', 3, NULL, NULL),
(4, 'Conseil d’État', 'Avenue Jean AVENO DA', NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL),
(5, 'Cour des Comptes', 'ACAE-route Owendo BP', NULL, NULL, NULL, NULL, NULL, 'Alain Christian IYANGUI', 3, NULL, NULL),
(6, 'Cour de Cassation', 'Avenue Jean AVENO DA', '+241 011 72 17 00', NULL, NULL, NULL, NULL, 'Julienne Olga NZAMBA', 3, NULL, NULL),
(7, 'Conseil Économique, Social et Environnemental (CESE)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 'CESE', NULL),
(8, 'Autorité pour la Protection des Données Personnelles et de la Vie Privée (APDPVP)', 'Haut de GueGue en face de l\'entrée de service Hôtel Radison Blu', ' +241 01443134', 'pierrotjokiassama@gmail.com', 'Informer les citoyens sur les droits que leur reconnait la loi et les responsables de traitement informatique et sanctionner lorsque l’APDPVP a constaté un manquement.s, sur leurs obligations. Veiller à la mise en oeuvre de tout traitement de données personnelles et des atteintes à la vie privée. Contrôler tout traitement in', 'https://www.apdpvp.ga/', NULL, '', 2, 'APDPVP', 5),
(9, 'Commission Nationale des Droits de l’Homme (CNDH)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'CNDH', NULL),
(10, 'Haute Autorité de la Communication (HAC)', 'Citée ALHMBRA', '+241 77549962', 'pierrotjokiassama@gmail.com', '', '', NULL, 'Germain NGOYO MOUSSSAVOU', 2, 'HAC', 4),
(11, 'Médiateur de la République (MR)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'MR', NULL),
(12, 'Conseil National de la Démocratie et de la Participation Citoyenne (CNDPC)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'CND', NULL),
(13, 'Commission Nationale de Lutte contre l’Enrichissement Illicite (CNCLEI)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'CNCLEI', NULL),
(14, 'Contrôle Général d’État (CGE)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'CGE', NULL),
(15, 'Agence Nationale d’Investigation Financière (ANIF)', 'B.P : 189 Libreville - Gabon', '(+241) 11 74 53 64 /', '', 'elle est chargée :  - de recueillir, d’analyser, d’enrichir et d’exploiter tout renseignement propre à établir l’origine ou la destination des sommes ou la nature des opérations ayant fait l’objet d’une déclaration de soupçon ou d’une saisine par le Parquet;  - de recevoir également toutes autres informations utiles nécessaires à l’accomplissement de sa mission, notamment, celles communiquées par les autorités de contrôle ainsi que les officiers de police judiciaire;  - de demander la communication, par les assujettis ainsi que par toute personne physique ou morale, d’informations détenues par eux et susceptibles de permettre d’enrichir les déclarations de soupçon;   - d’effectuer ou faire réaliser des études périodiques sur l’évolution des techniques utilisées aux fins de blanchiment des capitaux et du financement du terrorisme au niveau du territoire national;  - d’animer et de coordonner, en tant que de besoin, au niveau national et international, les moyens d’investigation dont dis', 'https://anif.ga/', NULL, '', 2, 'ANIF', 6),
(16, 'Agence de Régulation de l’Eau potable et de l’Énergie électrique (ARSEE)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'ARSEE', NULL),
(17, 'Agence de Régulation des Transports Ferroviaires (ARTF)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'ARTF', NULL),
(18, 'Agence de Régulation des Communications Électroniques et des Postes (ARCEP)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'ARCEP', NULL),
(19, 'Autorité de Régulation des Marchés Publics (ARMP)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'ARMP', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `structure_photos`
--

DROP TABLE IF EXISTS `structure_photos`;
CREATE TABLE IF NOT EXISTS `structure_photos` (
  `id_photos` int NOT NULL AUTO_INCREMENT,
  `Photos_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Position` int DEFAULT NULL,
  `id_structure` int DEFAULT NULL,
  PRIMARY KEY (`id_photos`),
  KEY `fk_structure_photos` (`id_structure`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','agent_ministere','point_focal') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `username`, `password`, `role`) VALUES
(1, 'Pierre', '$2y$10$jIVXNw8cHOFP17z2/FtFweZyltUYF0FyXhiZ2T.5xkRuNPRFo9rEK', 'super_admin'),
(2, 'faustin', '$2y$10$jLtarqR1fKG.Yk8eq/Tzzu60CFBiT8wY9WnwU4kSgBpBaNTjnwvg2', 'agent_ministere'),
(4, 'jokiel', '$2y$10$2.5TzdJg5E/Ux0SFCRX2CeJfFs3/it3CjA9p0MAMwHi3PXWU1Cv3m', 'point_focal'),
(5, 'jokiel1', '$2y$10$XBXt8TuNKi8vkWkmUvJAHewWLY1oaoO5YU7ARrMYiDI4RMVM56arC', 'point_focal'),
(6, 'jokiel2', '$2y$10$c9L.nNIAtbdjmueFJYqpBu6W0toGkTPxSm8gHnbIZOXeB/j5CVz8e', 'point_focal');

-- --------------------------------------------------------

--
-- Structure de la table `votes`
--

DROP TABLE IF EXISTS `votes`;
CREATE TABLE IF NOT EXISTS `votes` (
  `id_vote` int NOT NULL AUTO_INCREMENT,
  `id_contribution` int NOT NULL,
  `email_votant` varchar(191) NOT NULL,
  `valeur_vote` enum('pour','contre') NOT NULL,
  `date_vote` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_vote`),
  UNIQUE KEY `unique_vote` (`id_contribution`,`email_votant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `actualites`
--
ALTER TABLE `actualites`
  ADD CONSTRAINT `fk_actualites_structure` FOREIGN KEY (`id_structure`) REFERENCES `structure` (`id_struc`);

--
-- Contraintes pour la table `actualites_photos`
--
ALTER TABLE `actualites_photos`
  ADD CONSTRAINT `fk_actualites_photos` FOREIGN KEY (`id_actualites`) REFERENCES `actualites` (`id_actu`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id_cat`),
  ADD CONSTRAINT `fk_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id_cat`);

--
-- Contraintes pour la table `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `fk_consultations_createur` FOREIGN KEY (`id_createur`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_consultations_structure` FOREIGN KEY (`id_structure`) REFERENCES `structure` (`id_struc`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `contributions`
--
ALTER TABLE `contributions`
  ADD CONSTRAINT `fk_contribution_consultation` FOREIGN KEY (`id_consultation`) REFERENCES `consultations` (`id_consultation`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demandes`
--
ALTER TABLE `demandes`
  ADD CONSTRAINT `fk_demandes_agent` FOREIGN KEY (`id_agent_traitant`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `fk_demandes_point_focal` FOREIGN KEY (`id_point_focal`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `demandes_structures`
--
ALTER TABLE `demandes_structures`
  ADD CONSTRAINT `fk_ds_demande` FOREIGN KEY (`id_demande`) REFERENCES `demandes` (`id_demande`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ds_structure` FOREIGN KEY (`id_structure`) REFERENCES `structure` (`id_struc`);

--
-- Contraintes pour la table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_documents_structure` FOREIGN KEY (`id_structure`) REFERENCES `structure` (`id_struc`);

--
-- Contraintes pour la table `structure`
--
ALTER TABLE `structure`
  ADD CONSTRAINT `fk_structure_categorie` FOREIGN KEY (`id_cat`) REFERENCES `categories` (`id_cat`),
  ADD CONSTRAINT `fk_structure_responsable` FOREIGN KEY (`id_responsable`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `structure_photos`
--
ALTER TABLE `structure_photos`
  ADD CONSTRAINT `fk_structure_photos` FOREIGN KEY (`id_structure`) REFERENCES `structure` (`id_struc`);

--
-- Contraintes pour la table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `fk_vote_contribution` FOREIGN KEY (`id_contribution`) REFERENCES `contributions` (`id_contribution`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
