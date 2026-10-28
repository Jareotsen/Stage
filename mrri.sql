-- =========================================================
-- Base MRRI : structure complète + données de référence
-- Généré le 28/10/2026 depuis la base locale (MySQL 8.4).
--
-- Contient TOUTES les tables de l'application.
-- Données incluses : catégories, structures, photos, actualités,
-- consultations, documents, comptes utilisateurs (mots de passe hachés).
-- Données volontairement exclues (données personnelles des citoyens) :
-- citoyens, verifications_citoyens, contributions, votes, tentatives_connexion → tables créées vides.
--
-- Import : sélectionner la base `mrri` dans phpMyAdmin puis importer ce fichier,
-- ou en ligne de commande : mysql -u root -P 3308 mrri < mrri.sql
-- Les fichiers du dossier migrations/ sont déjà inclus dans ce schéma.
-- =========================================================

-- MySQL dump 10.13  Distrib 8.4.7, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: MRRI
-- ------------------------------------------------------
-- Server version	8.4.7

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `actualites`
--

DROP TABLE IF EXISTS `actualites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `actualites` (
  `id_actu` int NOT NULL AUTO_INCREMENT,
  `contenu` text COLLATE utf8mb4_unicode_ci,
  `titre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_publication` date DEFAULT NULL,
  `id_structure` int DEFAULT NULL,
  PRIMARY KEY (`id_actu`),
  KEY `idx_actu_struct_date` (`id_structure`,`date_publication`),
  CONSTRAINT `fk_actualites_structure` FOREIGN KEY (`id_structure`) REFERENCES `structure` (`id_struc`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `actualites_photos`
--

DROP TABLE IF EXISTS `actualites_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `actualites_photos` (
  `id_actulites_photos` int NOT NULL AUTO_INCREMENT,
  `Photos_actu_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_actualites` int DEFAULT NULL,
  `Position` int DEFAULT NULL,
  PRIMARY KEY (`id_actulites_photos`),
  KEY `fk_actualites_photos` (`id_actualites`),
  CONSTRAINT `fk_actualites_photos` FOREIGN KEY (`id_actualites`) REFERENCES `actualites` (`id_actu`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id_cat` int NOT NULL AUTO_INCREMENT,
  `nom_cat` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int DEFAULT NULL,
  PRIMARY KEY (`id_cat`),
  UNIQUE KEY `nom_cat` (`nom_cat`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id_cat`),
  CONSTRAINT `fk_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id_cat`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `citoyens`
--

DROP TABLE IF EXISTS `citoyens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `citoyens` (
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `genre` enum('femme','homme') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tranche_age` enum('moins_18','18_24','25_34','35_44','45_54','55_plus') COLLATE utf8mb4_unicode_ci NOT NULL,
  `secteur_activite` enum('secteur_public','secteur_prive','independant','etudiant','sans_emploi','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `consentement_statistiques_le` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email_verifie_le` datetime DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `consultations`
--

DROP TABLE IF EXISTS `consultations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultations` (
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
  KEY `fk_consultations_createur` (`id_createur`),
  CONSTRAINT `fk_consultations_createur` FOREIGN KEY (`id_createur`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consultations_structure` FOREIGN KEY (`id_structure`) REFERENCES `structure` (`id_struc`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contributions`
--

DROP TABLE IF EXISTS `contributions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contributions` (
  `id_contribution` int NOT NULL AUTO_INCREMENT,
  `id_consultation` int NOT NULL,
  `email_contributeur` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('en_attente','validee','rejetee') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `date_contribution` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_moderateur` int DEFAULT NULL,
  `date_moderation` datetime DEFAULT NULL,
  PRIMARY KEY (`id_contribution`),
  KEY `fk_contrib_moderateur` (`id_moderateur`),
  KEY `idx_contrib_statut` (`id_consultation`,`statut`),
  CONSTRAINT `fk_contrib_moderateur` FOREIGN KEY (`id_moderateur`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_contribution_consultation` FOREIGN KEY (`id_consultation`) REFERENCES `consultations` (`id_consultation`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id_document` int NOT NULL AUTO_INCREMENT,
  `doc_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom_affichage` varchar(225) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_public` tinyint(1) DEFAULT NULL,
  `id_structure` int DEFAULT NULL,
  PRIMARY KEY (`id_document`),
  KEY `idx_doc_struct_public` (`id_structure`,`est_public`),
  CONSTRAINT `fk_documents_structure` FOREIGN KEY (`id_structure`) REFERENCES `structure` (`id_struc`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `structure`
--

DROP TABLE IF EXISTS `structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `structure` (
  `id_struc` int NOT NULL AUTO_INCREMENT,
  `nom_struc` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mission` text COLLATE utf8mb4_unicode_ci,
  `historique` text COLLATE utf8mb4_unicode_ci,
  `composition` text COLLATE utf8mb4_unicode_ci,
  `site_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `texte_fondateur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `directeur_general` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fonction_dirigeant` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dirigeant_depuis` date DEFAULT NULL,
  `photo_dirigeant` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_cat` int NOT NULL,
  `sigle` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_responsable` int DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (`id_struc`),
  KEY `fk_structure_categorie` (`id_cat`),
  KEY `idx_structure_nom` (`nom_struc`),
  KEY `idx_structure_responsable` (`id_responsable`),
  CONSTRAINT `fk_structure_categorie` FOREIGN KEY (`id_cat`) REFERENCES `categories` (`id_cat`),
  CONSTRAINT `fk_structure_responsable` FOREIGN KEY (`id_responsable`) REFERENCES `utilisateurs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `structure_photos`
--

DROP TABLE IF EXISTS `structure_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `structure_photos` (
  `id_photos` int NOT NULL AUTO_INCREMENT,
  `Photos_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Position` int DEFAULT NULL,
  `id_structure` int DEFAULT NULL,
  PRIMARY KEY (`id_photos`),
  KEY `fk_structure_photos` (`id_structure`),
  CONSTRAINT `fk_structure_photos` FOREIGN KEY (`id_structure`) REFERENCES `structure` (`id_struc`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `structure_sauvegarde`
--

DROP TABLE IF EXISTS `structure_sauvegarde`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `structure_sauvegarde` (
  `id_struc` int NOT NULL DEFAULT '0',
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
  `id_responsable` int DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tentatives_connexion`
--

DROP TABLE IF EXISTS `tentatives_connexion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tentatives_connexion` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tente_le` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tentatives_ip` (`ip`,`tente_le`),
  KEY `idx_tentatives_username` (`username`,`tente_le`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','agent_ministere','point_focal') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `verifications_citoyens`
--

DROP TABLE IF EXISTS `verifications_citoyens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verifications_citoyens` (
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `genre` enum('femme','homme') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tranche_age` enum('moins_18','18_24','25_34','35_44','45_54','55_plus') COLLATE utf8mb4_unicode_ci NOT NULL,
  `secteur_activite` enum('secteur_public','secteur_prive','independant','etudiant','sans_emploi','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expire_le` datetime NOT NULL,
  `envoye_le` datetime NOT NULL,
  `tentatives` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `votes` (
  `id_vote` int NOT NULL AUTO_INCREMENT,
  `id_contribution` int NOT NULL,
  `email_votant` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valeur_vote` enum('pour','contre') NOT NULL,
  `date_vote` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_vote`),
  UNIQUE KEY `unique_vote` (`id_contribution`,`email_votant`),
  CONSTRAINT `fk_vote_contribution` FOREIGN KEY (`id_contribution`) REFERENCES `contributions` (`id_contribution`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed

-- =========================================================
-- Données
-- =========================================================
-- MySQL dump 10.13  Distrib 8.4.7, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: MRRI
-- ------------------------------------------------------
-- Server version	8.4.7

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'INSTITUTIONS CONSTITUTIONNELLES PARLEMENTAIRES',NULL),(2,'INSTITUTIONS CONSTITUTIONNELLES NON PARLEMENTAIRES',NULL),(3,'INSTITUTIONS À CARACTÈRE JURIDICTIONNEL',2),(4,'INSTITUTIONS DE CONSEIL',2);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `utilisateurs`
--

LOCK TABLES `utilisateurs` WRITE;
/*!40000 ALTER TABLE `utilisateurs` DISABLE KEYS */;
INSERT INTO `utilisateurs` VALUES (1,'Pierre','$2y$10$jIVXNw8cHOFP17z2/FtFweZyltUYF0FyXhiZ2T.5xkRuNPRFo9rEK','super_admin'),(2,'faustin','$2y$10$jLtarqR1fKG.Yk8eq/Tzzu60CFBiT8wY9WnwU4kSgBpBaNTjnwvg2','agent_ministere'),(5,'jokiel1','$2y$10$XBXt8TuNKi8vkWkmUvJAHewWLY1oaoO5YU7ARrMYiDI4RMVM56arC','point_focal'),(6,'jokiel2','$2y$10$c9L.nNIAtbdjmueFJYqpBu6W0toGkTPxSm8gHnbIZOXeB/j5CVz8e','point_focal'),(11,'jokiel4','$2y$10$7iVVAFSJQfg.UyJnrJ2Nle6FZhefQVTo41OssG7c6eeDXvL4QHdgK','point_focal'),(12,'jokiel5','$2y$10$IqGzMXtFOlJyM/Foc5MnAeUM10zyG7bsi9TBbNm4yGJyxbkM4Xh.S','point_focal'),(13,'jokiel','$2y$10$jMiVSt/.GZjImwAs4K38b.Bn4npdHcOFcFExFCuPivIZ8TjB1BduC','point_focal');
/*!40000 ALTER TABLE `utilisateurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `structure`
--

LOCK TABLES `structure` WRITE;
/*!40000 ALTER TABLE `structure` DISABLE KEYS */;
INSERT INTO `structure` VALUES (1,'Cour Constitutionnelle','Boulevard de l’indépendance',NULL,NULL,'Juge de la constitutionnalité des lois et garante du respect de la Constitution. Elle juge le contentieux des élections nationales et veille à la régularité du fonctionnement des institutions.',NULL,NULL,NULL,NULL,NULL,'Dieudonné ABA’A OWONO',NULL,NULL,NULL,1,'CC',NULL,0.3968726,9.4398916),(2,'Sénat','Palais OMAR BONGO ON',NULL,NULL,'Chambre du Parlement qui participe au vote de la loi, contrôle l’action du Gouvernement et représente les collectivités territoriales.',NULL,NULL,NULL,NULL,NULL,'Paulette MISSAMBO',NULL,NULL,NULL,1,NULL,NULL,0.4084610,9.4428560),(3,'Cour Constitutionnelle','Boulevard de l’indépendance',NULL,NULL,'Juge de la constitutionnalité des lois et garante du respect de la Constitution. Elle juge le contentieux des élections nationales et veille à la régularité du fonctionnement des institutions.',NULL,NULL,NULL,NULL,NULL,'Dieudonné ABA’A OWONO',NULL,NULL,NULL,3,'CC',NULL,0.3968726,9.4398916),(4,'Conseil d’État','Avenue Jean AVENO DA',NULL,NULL,'Plus haute juridiction de l’ordre administratif : elle juge les litiges opposant les citoyens à l’administration et émet des avis juridiques sur les textes qui lui sont soumis.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,'CE',NULL,0.4891613,9.3869353),(5,'Cour des Comptes','ACAE-route Owendo BP',NULL,NULL,'Juridiction chargée du contrôle des finances publiques : elle juge les comptes des comptables publics, contrôle l’exécution des lois de finances et assiste le Parlement et le Gouvernement.',NULL,NULL,NULL,NULL,NULL,'Alain Christian IYANGUI',NULL,NULL,NULL,3,'CDC',NULL,0.3492620,9.4763110),(6,'Cour de Cassation','Avenue Jean AVENO DA','+241 011 72 17 00',NULL,'Plus haute juridiction de l’ordre judiciaire : elle veille à l’application uniforme du droit par les juridictions inférieures et juge les pourvois en cassation.',NULL,NULL,NULL,NULL,NULL,'Julienne Olga NZAMBA',NULL,NULL,NULL,3,'CCASS',NULL,0.4258140,9.4523600),(7,'Conseil Économique, Social et Environnemental (CESE)','Libreville, Gabon',NULL,NULL,'Assemblée consultative qui éclaire les pouvoirs publics par ses avis sur les questions économiques, sociales et environnementales.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,'CESE',NULL,0.4375530,9.4172160),(8,'Autorité pour la Protection des Données Personnelles et de la Vie Privée (APDPVP)','Haut de GueGue en face de l\'entrée de service Hôtel Radison Blu',' +241 01443134','pierrotjokiassama@gmail.com','Informer les citoyens sur les droits que leur reconnait la loi et les responsables de traitement informatique et sanctionner lorsque l’APDPVP a constaté un manquement.s, sur leurs obligations. Veiller à la mise en oeuvre de tout traitement de données personnelles et des atteintes à la vie privée. Contrôler tout traitement in',NULL,NULL,'https://www.apdpvp.ga/',NULL,NULL,'',NULL,NULL,NULL,2,'APDPVP',5,0.4299000,9.4202000),(9,'Commission Nationale des Droits de l’Homme (CNDH)','Libreville, Gabon',NULL,NULL,'Promouvoir et protéger les droits de l’homme : recevoir les plaintes, enquêter sur les violations, formuler des avis et recommandations aux pouvoirs publics et sensibiliser les citoyens.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'CNDH',NULL,0.4317310,9.4238050),(10,'Haute Autorité de la Communication (HAC)','Avenue du Général de Gaulle (ou Avenue de la Libération selon les services), B.P. 6437, Libreville, Gabon','+241 077 29 39 38','pierrotjokiassama@gmail.com','• Réguler la communication audiovisuelle, la presse écrite, le cinéma, le numérique et la publicité. • Veiller au respect de la liberté de la presse, de la démocratie, et de l\'accès à une information libre. • Assurer un traitement équitable des partis politiques et de la société civile dans les médias publics. • Contrôler les programmes, protéger l\'enfance et l\'adolescence, et veiller à la dignité humaine.',NULL,NULL,'',NULL,NULL,'Germain NGOYO MOUSSSAVOU',NULL,NULL,NULL,2,'HAC',13,0.3900000,9.4542000),(11,'Médiateur de la République (MR)','Libreville, Gabon',NULL,NULL,'Recevoir les réclamations des citoyens contre le fonctionnement des administrations publiques et favoriser un règlement amiable des différends.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'MR',NULL,0.4317310,9.4238050),(12,'Conseil National de la Démocratie et de la Participation Citoyenne (CNDPC)','Libreville, Gabon',NULL,NULL,'Promouvoir la démocratie pluraliste, le dialogue politique et la participation des citoyens à la vie publique.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'CND',NULL,0.4180960,9.4507520),(13,'Commission Nationale de Lutte contre l’Enrichissement Illicite (CNCLEI)','Libreville, Gabon',NULL,NULL,'Prévenir et combattre l’enrichissement illicite : recueillir et contrôler les déclarations de patrimoine, enquêter et saisir la justice en cas d’infraction.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'CNCLEI',12,0.3898150,9.4503550),(14,'Contrôle Général d’État (CGE)','Libreville, Gabon',NULL,NULL,'Organe supérieur de contrôle de l’administration : il réalise des audits, des inspections et des vérifications de la gestion des services de l’État.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'CGE',NULL,NULL,NULL),(15,'Agence Nationale d’Investigation Financière (ANIF)','B.P : 189 Libreville - Gabon','(+241) 11 74 53 64 /','','elle est chargée :  - de recueillir, d’analyser, d’enrichir et d’exploiter tout renseignement propre à établir l’origine ou la destination des sommes ou la nature des opérations ayant fait l’objet d’une déclaration de soupçon ou d’une saisine par le Parquet;  - de recevoir également toutes autres informations utiles nécessaires à l’accomplissement de sa mission, notamment, celles communiquées par les autorités de contrôle ainsi que les officiers de police judiciaire;  - de demander la communication, par les assujettis ainsi que par toute personne physique ou morale, d’informations détenues par eux et susceptibles de permettre d’enrichir les déclarations de soupçon;   - d’effectuer ou faire réaliser des études périodiques sur l’évolution des techniques utilisées aux fins de blanchiment des capitaux et du financement du terrorisme au niveau du territoire national;  - d’animer et de coordonner, en tant que de besoin, au niveau national et international, les moyens d’investigation dont dis',NULL,NULL,'https://anif.ga/',NULL,NULL,'',NULL,NULL,NULL,2,'ANIF',6,NULL,NULL),(16,'Agence de Régulation de l’Eau potable et de l’Énergie électrique (ARSEE)','Libreville, Gabon','','','Réguler le secteur de l’eau potable et de l’énergie électrique : veiller à la qualité du service, à l’équilibre économique du secteur, au respect des obligations des opérateurs et à la protection des usagers.',NULL,NULL,'',NULL,NULL,'Jean Cruz Lessagui','Secrétaire exécutif','2026-10-27',NULL,2,'ARSEE',11,0.4357000,9.4172000),(17,'Agence de Régulation des Transports Ferroviaires (ARTF)','Libreville, Gabon',NULL,NULL,'Réguler le transport ferroviaire : veiller au respect de la réglementation, à la sécurité, à la qualité du service et aux droits des usagers.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'ARTF',NULL,0.3390810,9.4768460),(18,'Agence de Régulation des Communications Électroniques et des Postes (ARCEP)','Libreville, Gabon','1331',NULL,'Autorité administrative indépendante chargée de la régulation, du contrôle et du suivi des opérateurs de communications électroniques et des postes. Elle encadre la concurrence entre opérateurs, protège les consommateurs et contrôle la qualité des réseaux et des services.',NULL,NULL,'https://www.arcep.ga/',NULL,NULL,NULL,NULL,NULL,NULL,2,'ARCEP',NULL,0.4319113,9.4198444),(19,'Autorité de Régulation des Marchés Publics (ARMP)','Libreville, Gabon',NULL,NULL,'Réguler le système des marchés publics : veiller au respect des règles et des procédures, réaliser des audits et contribuer au règlement des différends.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'ARMP',NULL,0.4080170,9.4392760);
/*!40000 ALTER TABLE `structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `structure_sauvegarde`
--

LOCK TABLES `structure_sauvegarde` WRITE;
/*!40000 ALTER TABLE `structure_sauvegarde` DISABLE KEYS */;
INSERT INTO `structure_sauvegarde` VALUES (1,'Cour Constitutionnelle','Boulevard de l’indépendance',NULL,NULL,NULL,NULL,NULL,'Dieudonné ABA’A OWONO',1,NULL,NULL),(2,'Sénat ','Palais OMAR BONGO ON',NULL,NULL,NULL,NULL,NULL,'Paulette MISSAMBO',1,NULL,NULL),(3,'Cour Constitutionnelle','Boulevard de l’indépendance',NULL,NULL,NULL,NULL,NULL,'Dieudonné ABA’A OWONO',3,NULL,NULL),(4,'Conseil d’État','Avenue Jean AVENO DA',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL),(5,'Cour des Comptes','ACAE-route Owendo BP',NULL,NULL,NULL,NULL,NULL,'Alain Christian IYANGUI',3,NULL,NULL),(6,'Cour de Cassation','Avenue Jean AVENO DA','+241 011 72 17 00',NULL,NULL,NULL,NULL,'Julienne Olga NZAMBA',3,NULL,NULL),(7,'Conseil Économique, Social et Environnemental (CESE)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,'CESE',NULL),(8,'Autorité pour la Protection des Données Personnelles et de la Vie Privée (APDPVP)','Haut de GueGue en face de l\'entrée de service Hôtel Radison Blu',' +241 01443134','pierrotjokiassama@gmail.com','Informer les citoyens sur les droits que leur reconnait la loi et les responsables de traitement informatique et sanctionner lorsque l’APDPVP a constaté un manquement.s, sur leurs obligations. Veiller à la mise en oeuvre de tout traitement de données personnelles et des atteintes à la vie privée. Contrôler tout traitement in','https://www.apdpvp.ga/',NULL,'',2,'APDPVP',5),(9,'Commission Nationale des Droits de l’Homme (CNDH)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'CNDH',NULL),(10,'Haute Autorité de la Communication (HAC)','Avenue du Général de Gaulle (ou Avenue de la Libération selon les services), B.P. 6437, Libreville, Gabon','+241 077 29 39 38','pierrotjokiassama@gmail.com','• Réguler la communication audiovisuelle, la presse écrite, le cinéma, le numérique et la publicité. • Veiller au respect de la liberté de la presse, de la démocratie, et de l\'accès à une information libre. • Assurer un traitement équitable des partis politiques et de la société civile dans les médias publics. • Contrôler les programmes, protéger l\'enfance et l\'adolescence, et veiller à la dignité humaine.','',NULL,'Germain NGOYO MOUSSSAVOU',2,'HAC',4),(11,'Médiateur de la République (MR)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'MR',NULL),(12,'Conseil National de la Démocratie et de la Participation Citoyenne (CNDPC)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'CND',NULL),(13,'Commission Nationale de Lutte contre l’Enrichissement Illicite (CNCLEI)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'CNCLEI',NULL),(14,'Contrôle Général d’État (CGE)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'CGE',NULL),(15,'Agence Nationale d’Investigation Financière (ANIF)','B.P : 189 Libreville - Gabon','(+241) 11 74 53 64 /','','elle est chargée :  - de recueillir, d’analyser, d’enrichir et d’exploiter tout renseignement propre à établir l’origine ou la destination des sommes ou la nature des opérations ayant fait l’objet d’une déclaration de soupçon ou d’une saisine par le Parquet;  - de recevoir également toutes autres informations utiles nécessaires à l’accomplissement de sa mission, notamment, celles communiquées par les autorités de contrôle ainsi que les officiers de police judiciaire;  - de demander la communication, par les assujettis ainsi que par toute personne physique ou morale, d’informations détenues par eux et susceptibles de permettre d’enrichir les déclarations de soupçon;   - d’effectuer ou faire réaliser des études périodiques sur l’évolution des techniques utilisées aux fins de blanchiment des capitaux et du financement du terrorisme au niveau du territoire national;  - d’animer et de coordonner, en tant que de besoin, au niveau national et international, les moyens d’investigation dont dis','https://anif.ga/',NULL,'',2,'ANIF',6),(16,'Agence de Régulation de l’Eau potable et de l’Énergie électrique (ARSEE)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'ARSEE',NULL),(17,'Agence de Régulation des Transports Ferroviaires (ARTF)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'ARTF',NULL),(18,'Agence de Régulation des Communications Électroniques et des Postes (ARCEP)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'ARCEP',NULL),(19,'Autorité de Régulation des Marchés Publics (ARMP)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'ARMP',NULL);
/*!40000 ALTER TABLE `structure_sauvegarde` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `structure_photos`
--

LOCK TABLES `structure_photos` WRITE;
/*!40000 ALTER TABLE `structure_photos` DISABLE KEYS */;
INSERT INTO `structure_photos` VALUES (1,'6ad3766ee2da1.png',1,10),(2,'6adfb00b6d0d1.jpeg',1,16),(3,'6adfb0eb4aec6.jpeg',1,16);
/*!40000 ALTER TABLE `structure_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `actualites`
--

LOCK TABLES `actualites` WRITE;
/*!40000 ALTER TABLE `actualites` DISABLE KEYS */;
INSERT INTO `actualites` VALUES (30,'La Haute Autorité de la Communication (HAC) constate avec préoccupation la récurrence de rumeurs et de fausses informations (fake news) diffusées à travers plusieurs plateformes numériques et réseaux sociaux en République Gabonaise.\r\n\r\nDans le cadre de ses missions de régulation du secteur des médias et d\'assainissement de l\'espace audio-visuel et numérique, la HAC tient à rappeler à l\'ensemble des acteurs de la presse (écrite, audiovisuelle et en ligne) ainsi qu\'aux usagers des réseaux sociaux :\r\n\r\nLe respect du Code de la communication : Tout professionnel des médias est tenu de vérifier la véracité des informations avant toute publication ou diffusion.\r\n\r\nLa responsabilité numérique : Les administrateurs de pages, groupes et comptes sur les réseaux sociaux tombent sous le coup de la réglementation en matière de responsabilité éditoriale.\r\n\r\nLa lutte contre la désinformation : La propagation de contenus de nature à porter atteinte à l\'ordre public ou à la dignité des personnes fera l\'objet des sanctions prévues par la législation en vigueur.\r\n\r\nLa HAC invite l\'ensemble de la population à faire preuve de vigilance, de discernement et de retenue dans le partage des informations non confirmées par les sources officielles.\r\n\r\nLe Secrétariat Général de la HAC','Communiqué : Mise en garde de la HAC concernant la diffusion de fausses informations sur les réseaux sociaux','2026-10-13',10),(32,'L\'Autorité pour la Protection des Données Personnelles et de la Vie Privée (APDPVP) rappelle à l\'attention de l\'ensemble des acteurs économiques du secteur privé, des administrations publiques et des associations opérant sur le territoire gabonais que toute collecte, enregistrement ou traitement informatique de données à caractère personnel est soumis à une déclaration préalable auprès de nos services, conformément à la loi n° 025/2023 modifiant la loi n° 001/2011 relative à la protection des données à caractère personnel.   Sont notamment concernés par cette obligation :   Les fichiers de gestion de la clientèle et des prospects ;Les bases de données des ressources humaines et paies ;Les systèmes de vidéosurveillance ou de contrôle d\'accès biométrique ;Les applications mobiles, plateformes e-commerce et sites internet collectant des identifiants d\'utilisateurs.Quels sont les engagements requis ?Tout responsable de traitement doit garantir aux citoyens le droit d\'accès, de rectification et d\'opposition sur leurs données personnelles, tout en assurant un niveau de sécurité optimal pour empêcher la fuite ou le détournement de ces informations.L\'APDPVP invite les structures n\'ayant pas encore régularisé leur situation à entamer les démarches de mise en conformité en téléchargeant le formulaire de déclaration directement depuis notre site internet officiel (www.apdpvp.ga).Rappel : Le défaut de déclaration ainsi que l\'utilisation non autorisée de données personnelles exposent les contrevenants aux sanctions administratives et pénales prévues par la législation en vigueur.   Pour la Protection des Données et du Respect de la Vie Privée   Le Secrétariat Général de l\'APDPVP','Sensibilisation : Rappel des obligations de déclaration préalable pour les entreprises et organismes collectant des données à caractère personnel','2026-10-13',8),(33,'Dans le cadre de la préparation des prochaines échéances électorales, le collège des conseillers de la HAC s\'est réuni en session extraordinaire ce mardi. Face à la résurgence de rumeurs et de contenus non vérifiés sur les réseaux sociaux, l\'instance de régulation rappelle que la diffusion d\'informations inexactes ou diffamatoires expose leurs auteurs aux sanctions prévues par le Code de la Communication.','Mise en garde contre la diffusion de fausses nouvelles en période électorale','2026-10-14',10),(34,'La HAC informe tous les promoteurs de chaînes télévisées privées émettant sur le territoire national du démarrage des opérations de vérification des cahiers des charges. Les opérateurs disposent de 30 jours pour déposer leurs dossiers de mise en conformité administrative et technique','Lancement de la campagne de conformité des chaînes de télévision privées','2026-10-14',10),(35,'En partenariat avec les associations de la presse numérique, la HAC a ouvert un séminaire de trois jours axé sur le fact-checking et la gestion responsable des commentaires en ligne. Plus de 40 journalistes et éditeurs web prennent part à cette session interactive.','Séminaire de formation sur le traitement de l\'information numérique','2026-10-14',10),(36,'Le Président de la HAC a reçu en audience la délégation de l\'Union des Journalistes. Les discussions ont porté sur la délivrance des cartes de presse professionnelles et sur le soutien institutionnel à la presse indépendante.','Audience accordée au bureau exécutif de l\'Union des Journalistes','2026-10-15',10),(38,'Un nouveau chapitre s’ouvre à la tête de l’Autorité de régulation du secteur de l’eau potable et de l’énergie électrique (ARSEE). Jean Cruz Lessagui a officiellement pris ses fonctions de Secrétaire exécutif, succédant ainsi à Northon Messan, qui occupait cette charge depuis six mois.   La cérémonie de passation de charges, qui s’est déroulée dans une atmosphère empreinte de solennité et de responsabilité institutionnelle, marque le début d\'une nouvelle feuille de route pour cet organe stratégique au Gabon.Une transition sous le signe de la continuité et du changementAprès six mois passés aux commandes de l\'ARSEE, le Secrétaire exécutif sortant, Northon Messan, a salué le travail accompli par ses équipes et formulé ses vœux de réussite à son successeur, l\'invitant à poursuivre les chantiers de consolidation de l\'institution.   De son côté, Jean Cruz Lessagui prend la direction de l\'agence avec une ambition clairement affichée : insuffler une dynamique axée sur la performance, la rigueur administrative et le résultat.« Je ne viens pas avec des promesses miracles, je viens avec une méthode : écouter, planifier, agir et rendre compte », a déclaré le nouveau Secrétaire exécutif lors de sa prise de parole.   Des défis majeurs dans le secteur de l\'eau et de l\'électricité   Nommé lors du Conseil des ministres, le nouveau dirigeant prend les commandes de l\'ARSEE à un moment charnière. Le régulateur fait face à d\'importants défis d\'amélioration de l\'accès à l\'eau potable et à l\'électricité, de protection des droits des usagers et de contrôle rigoureux des opérateurs du secteur.   Les priorités fixées par Jean Cruz Lessagui s\'articulent autour de trois axes :Renforcement de la gouvernance : Améliorer la transparence et l\'efficacité des mécanismes de régulation.   Proximité avec les consommateurs : Mettre l\'accent sur la qualité du service rendu aux ménages et le traitement des litiges.   Dialogue partenarial : Renforcer la concertation avec les tutelles ministérielles, les compagnies opératrices et les partenaires financiers.Le nouveau Secrétaire exécutif s\'est engagé à mettre en œuvre un management participatif et à collaborer étroitement avec l\'ensemble du personnel pour atteindre rapidement les objectifs fixés par les plus hautes autorités.','Jean Cruz Lessagui a officiellement pris ses fonctions de Secrétaire exécutif de l\'ARSEE, succédant au secrétaire exécutif sortant, Northon Messan (ou','2026-10-28',16);
/*!40000 ALTER TABLE `actualites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `actualites_photos`
--

LOCK TABLES `actualites_photos` WRITE;
/*!40000 ALTER TABLE `actualites_photos` DISABLE KEYS */;
INSERT INTO `actualites_photos` VALUES (1,'6ace039587221.jpg',32,1),(2,'6acf4f5e6f557.jpeg',33,1),(3,'6acf6e9cef934.jpeg',34,1),(4,'6acf6ebecf6d4.jpeg',35,1),(5,'6ad0c97558fb4.jpeg',36,1),(7,'6ae1b39e27b6c.jpeg',38,1);
/*!40000 ALTER TABLE `actualites_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `consultations`
--

LOCK TABLES `consultations` WRITE;
/*!40000 ALTER TABLE `consultations` DISABLE KEYS */;
INSERT INTO `consultations` VALUES (4,NULL,2,'remise à niveau','test de la plateforme de consultation précisement la fonctionnalité de consultation','2026-10-12 09:15:23','2026-10-13 00:00:00','ouverte'),(5,10,NULL,'Modernisation des outils de participation citoyenne et simplification de l\'accès aux institutions de la République.','Les mutations technologiques et sociétales exigent une adaptation constante de notre architecture institutionnelle. Cette consultation vise à recueillir l\'avis des citoyens, des acteurs de la société civile et des experts sur les mécanismes numériques et physiques permettant de faciliter la saisine des institutions et de rendre l\'action publique plus transparente.','2026-10-12 09:30:25','2026-10-22 00:00:00','ouverte'),(6,8,5,'Renforcement de l\'engagement civique et simplification des démarches administratives pour la primo-insertion citoyenne des jeunes adultes.','1. Contexte et Objectifs\r\nLe passage à l\'âge adulte (majorité, fin des études, entrée dans la vie active) s\'accompagne souvent d\'un parcours du combattant administratif. Cette consultation a pour double objectif de :\r\n\r\nSimplifier et unifier les premières démarches obligatoires (recensement, inscription sur les listes électorales, accès aux aides sociales de base).\r\n\r\nRenforcer les canaux d\'expression et de participation des 16-25 ans au sein des instances consultatives locales et nationales.\r\n\r\n2. Volets de la consultation\r\nLes contributions sont ouvertes autour de deux grands axes :\r\n\r\nVolet 1 : Le \"Guichet Unique de la Majorité\"\r\n\r\nÉtude sur la centralisation des démarches administratives de premier niveau sur une application unique ou un portail unifié.\r\n\r\nVolet 2 : Les \"Conseils de la Jeunesse\" et budgets participatifs institutionnels\r\n\r\nModalités de création d\'espaces de délibération obligatoires pour associer les jeunes aux politiques publiques qui les concernent (transition écologique, logement étudiant, transports).\r\n\r\n3. Modalites pratiques\r\nPublics concernés : Jeunes de 16 à 25 ans, associations de jeunesse, acteurs éducatifs et universitaires, collectivités territoriales.\r\n\r\nOutils de collecte : Ateliers participatifs en région, plateforme de contributions écrites et consultations via les réseaux partenaires du Ministère.','2026-10-12 09:33:04','2026-11-16 00:00:00','ouverte'),(7,10,NULL,'Avis de Consultation Publique n° 005/2026/HAC relatif au passage à la Haute Définition (HD) sur la TNT et à l\'encadrement des temps d\'antenne publicitaires','Dans le cadre du plan de modernisation du paysage audiovisuel gabonais et afin de répondre aux exigences de qualité technique du public, la Haute Autorité de la Communication (HAC) engage une concertation stratégique avec les éditeurs de services de télévision, les régies publicitaires, les diffuseurs techniques et les associations de consommateurs.\r\n\r\nCette consultation vise à fixer le calendrier et le cahier des charges du passage obligatoire de l\'ensemble des chaînes de la Télévision Numérique Terrestre (TNT) au format HD, tout en actualisant le cadre réglementaire régissant les coupures publicitaires et les parrainages lors des programmes d\'information et de divertissement.\r\n\r\nAxes majeurs soumis à avis\r\nLes acteurs du secteur et le public sont invités à formuler leurs observations autour des propositions suivantes :\r\n\r\nNormes techniques et calendrier HD :\r\n\r\nQuel délai minimal accorder aux chaînes privées et publiques pour mettre aux normes leurs équipements de diffusion (HD 1080p) ?\r\n\r\nQuels accompagnements techniques ou fiscaux seraient souhaitables pour faciliter cette transition ?\r\n\r\nPlafond du volume publicitaire :\r\n\r\nProposition de limitation du temps de publicité à un maximum de 12 minutes par heure d\'antenne sur les chaînes de télévision généralistes.\r\n\r\nEncadrement du niveau sonore des spots publicitaires pour éviter le relèvement intempestif du volume sonore par rapport aux programmes.\r\n\r\nProtection des jeunes publics pendant la publicité :\r\n\r\nInterdiction des publicités ciblées pour les produits à forte teneur en sucre ou boissons énergisantes dans un creneau de 30 minutes avant et après les programmes pour enfants.\r\n\r\nTransparence des parrainages (Sponsoring) :\r\n\r\nObligation d\'affichage clair et lisible de l\'identité des parrains lors des émissions d\'information ou des journaux télévisés.\r\n\r\nModalités de participation\r\nSont vivement encouragés à contribuer :\r\n\r\nLes chaînes de télévision et stations de radio publiques et privées émettant au Gabon ;\r\n\r\nLes régies publicitaires et agences de communication ;\r\n\r\nLes opérateurs de diffusion (TNT, satellite, câble, IP) ;\r\n\r\nLes associations de défense des droits des consommateur','2026-10-13 11:37:50','2026-10-30 23:59:59','ouverte');
/*!40000 ALTER TABLE `consultations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES (3,'6aa133d285e31.pdf','test2 upload',0,10),(4,'6aa13c3f5ed6d.docx','test3 upload',0,10),(5,'6aa14a8790eb6.docx','test3 upload',0,10),(6,'6ace04a59d4d9.docx','concour_professionnel',0,8),(10,'7e1ce2f69b3979f7a9dce5fb16f8e598.pdf','repartition_des_taches',1,10),(11,'74b3b11acbd95a4cebc4ede2ad534f4b.pdf','repartition_des_taches',1,10);
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
