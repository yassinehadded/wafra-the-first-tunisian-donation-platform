-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 14 déc. 2025 à 14:29
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `wafra`
--

-- --------------------------------------------------------

--
-- Structure de la table `association`
--

CREATE TABLE `association` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `campagnes`
--

CREATE TABLE `campagnes` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `objectif_montant` decimal(10,2) DEFAULT NULL,
  `type_contribution` varchar(50) DEFAULT NULL,
  `statut` varchar(50) DEFAULT 'active',
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `comment_report`
--

CREATE TABLE `comment_report` (
  `id_report` int(11) NOT NULL,
  `id_comment` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `reason` enum('spam','harassment','inappropriate_content','other') NOT NULL DEFAULT 'other',
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `date_report` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `comment_report`
--

INSERT INTO `comment_report` (`id_report`, `id_comment`, `id_user`, `reason`, `description`, `status`, `admin_notes`, `reviewed_by`, `reviewed_at`, `date_report`) VALUES
(1, 2, 14725638, 'other', 'bad words', 'reviewed', '', 14785236, '2025-12-14 09:32:17', '2025-12-14 09:31:40');

-- --------------------------------------------------------

--
-- Structure de la table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user_one_id` int(11) NOT NULL,
  `user_two_id` int(11) NOT NULL,
  `related_entity_type` varchar(50) DEFAULT NULL COMMENT 'donation, post, request',
  `related_entity_id` int(11) DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `is_blocked` tinyint(1) DEFAULT 0,
  `blocked_by` int(11) DEFAULT NULL COMMENT 'User ID who blocked the conversation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `conversations`
--

INSERT INTO `conversations` (`id`, `user_one_id`, `user_two_id`, `related_entity_type`, `related_entity_id`, `last_message_at`, `is_blocked`, `blocked_by`, `created_at`, `updated_at`) VALUES
(1, 14725638, 220172730, 'post', 1, '2025-12-14 12:27:06', 0, NULL, '2025-12-14 11:54:30', '2025-12-14 12:27:06');

-- --------------------------------------------------------

--
-- Structure de la table `donor_offers`
--

CREATE TABLE `donor_offers` (
  `id` int(11) NOT NULL,
  `donor_name` varchar(255) NOT NULL,
  `donor_email` text NOT NULL,
  `donor_phone` int(8) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `item_image` varchar(255) DEFAULT NULL,
  `date` date DEFAULT curdate(),
  `status` enum('active','fulfilled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `donor_requests`
--

CREATE TABLE `donor_requests` (
  `id` int(11) NOT NULL,
  `requester_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` int(8) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `request_date` date DEFAULT curdate(),
  `donor_name` varchar(255) DEFAULT NULL,
  `donation_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','denied') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `dons`
--

CREATE TABLE `dons` (
  `id` int(11) NOT NULL,
  `nom_association` varchar(255) NOT NULL,
  `date_don` date NOT NULL,
  `type_don` enum('cheque','espece') NOT NULL,
  `type_contribution` enum('sante','food','vetement','education','autre') NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `association_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evenements`
--

CREATE TABLE `evenements` (
  `id` int(11) NOT NULL,
  `nom_evenement` varchar(255) NOT NULL,
  `type_evenement` varchar(50) NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `description` text NOT NULL,
  `lieu` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `qr_code` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `evenements`
--

INSERT INTO `evenements` (`id`, `nom_evenement`, `type_evenement`, `date_debut`, `date_fin`, `description`, `lieu`, `latitude`, `longitude`, `qr_code`, `created_at`, `updated_at`) VALUES
(4, 'Festival de Musique Moderne', 'festival', '2024-12-25 08:00:00', '2024-12-27 23:00:00', 'Festival de musique et d\'art avec plusieurs artistes et expositions. Trois jours de concerts et d\'animations culturelles.', 'Hammamet, Centre Culturel', 36.40000000, 10.61670000, NULL, '2025-12-12 16:22:26', '2025-12-12 16:22:26'),
(5, 'Séminaire Leadership & Management', 'seminaire', '2025-01-05 09:00:00', '2025-01-05 12:00:00', 'Séminaire sur les meilleures pratiques en gestion de projet et leadership. Découvrez les techniques de management moderne.', 'Tunis, Hôtel Business', 36.80650000, 10.18150000, NULL, '2025-12-12 16:22:26', '2025-12-12 16:22:26'),
(6, 'Conférence IA & Machine Learning', 'conference', '2025-01-15 10:00:00', '2025-01-15 16:00:00', 'Conférence sur l\'intelligence artificielle et le machine learning. Présentation des dernières avancées et applications pratiques.', 'Tunis, Université', 36.80650000, 10.18150000, NULL, '2025-12-12 16:22:26', '2025-12-12 16:22:26'),
(8, 'Workshop UI/UX Design', 'workshop', '2025-02-01 14:00:00', '2025-02-01 18:00:00', 'Workshop sur le design d\'interfaces utilisateur et l\'expérience utilisateur. Apprenez les principes du design moderne.', 'Tunis, Design Studio', 36.80650000, 10.18150000, NULL, '2025-12-12 16:22:26', '2025-12-12 16:22:26'),
(10, 'Séminaire Marketing Digital', 'seminaire', '2025-02-20 09:00:00', '2025-02-20 13:00:00', 'Séminaire sur les stratégies de marketing digital et les réseaux sociaux. Optimisez votre présence en ligne.', 'Sousse, Centre d\'Affaires', 35.82540000, 10.63600000, NULL, '2025-12-12 16:22:26', '2025-12-12 16:22:26'),
(12, 'Séminaire Marketing Digital', 'conference', '2025-12-02 12:00:00', '2025-12-01 12:00:00', 'hzadhadhzhdzhahdza', 'Sousse, Centre d\'Affaires', 35.82540000, 10.63600000, 'w4lWw4lORU1FTlQKTm9tOiBTw6ltaW5haXJlIE1hcmtldGluZyBEaWdpdGFsClR5cGU6IGNvbmZlcmVuY2UKRGF0ZTogMjAyNS0xMi0wMiAxMjowMApMaWV1OiBTb3Vzc2UsIENlbnRyZSBkJ0FmZmFpcmVzCg==', '2025-12-12 18:44:13', '2025-12-12 18:44:13'),
(13, 'donate for gaza', 'social', '2025-12-08 12:00:00', '2025-12-14 12:00:00', 'lets donate for gaza', 'Tunisia', 0.00000003, -0.00000003, NULL, '2025-12-13 19:47:11', '2025-12-13 19:47:11');

-- --------------------------------------------------------

--
-- Structure de la table `loginsession`
--

CREATE TABLE `loginsession` (
  `SessionID` varchar(255) NOT NULL,
  `userID` int(11) NOT NULL,
  `username` varchar(255) NOT NULL DEFAULT '',
  `loginTime` datetime DEFAULT current_timestamp(),
  `logoutTime` datetime DEFAULT NULL,
  `ipAddress` varchar(255) DEFAULT NULL,
  `device` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `loginsession`
--

INSERT INTO `loginsession` (`SessionID`, `userID`, `username`, `loginTime`, `logoutTime`, `ipAddress`, `device`) VALUES
('05377f8d93f802c92a8e17cc64c01db6', 220172730, '', '2025-12-14 13:25:55', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('0997d0d71e1965a38cc22a24e9ee4982', 14725638, '', '2025-12-13 22:22:49', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('0dc5ba3617d5b79cee5d103107a4078c', 220172730, '', '2025-12-13 19:26:20', '2025-12-13 19:28:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('0f938deb9c6b6f8e229eed1bd32b3adb', 220172730, '', '2025-12-14 13:27:22', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('1198738dc9092909fc246c7d0ccda1de', 220172730, '', '2025-12-13 17:28:00', '2025-12-13 17:31:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('130216969fdb4da5e40bf9d94ec1a395', 14785236, '', '2025-12-13 18:25:24', '2025-12-13 18:26:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('16f5f668c400e58540509ef7a3f1816d', 14725638, '', '2025-12-14 13:25:56', '2025-12-14 13:27:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('1733b44682b42c4af81641e995370d14', 220172730, '', '2025-12-07 00:21:51', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('1bfda1869fe368641f1e5f61398c80e6', 14785236, '', '2025-12-07 22:03:34', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('1e093df25ac2db7bbf83cfe45b6d9542', 14725638, '', '2025-12-14 03:16:45', '2025-12-14 03:20:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('20497b30997056393ec6ad03746c84ee', 14785236, '', '2025-12-13 13:09:42', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('20639a77e25e7a3a757e13142a762a8c', 14785236, '', '2025-12-08 10:27:55', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('21037870b081d9d72f8d372883486892', 14785236, '', '2025-12-07 12:09:26', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('23f4c33e7fdf63fec23ccf22753605c4', 14785236, '', '2025-12-13 19:18:33', '2025-12-13 19:23:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('248cd9beac6b7f33a81aa6b3bfb03de9', 220172730, '', '2025-12-14 13:27:18', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('261644cd71a4e3bc279a4b6e5c85455f', 220172730, '', '2025-12-13 19:23:33', '2025-12-13 19:25:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('2684cbf1187b742466a57b26a30fa6e2', 220172730, '', '2025-12-01 09:31:05', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('28ac75993f10dba7e50f6f4a9e356e15', 14785236, '', '2025-12-13 22:03:43', '2025-12-13 22:11:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('2baa474ddab6635277e3407207b40466', 220172730, '', '2025-12-07 00:54:16', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('2edd946971c85b86e7aa4af485167f4f', 14785236, '', '2025-12-14 10:31:55', '2025-12-14 10:42:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('33dc3aed4ac6daf76267abb64c59bfc6', 14725638, '', '2025-12-14 10:03:34', '2025-12-14 10:11:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('3582e99d2be0d3e23a1ff2bef7af022d', 220172730, '', '2025-12-13 19:26:18', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('36709907b087d63ec5776710658cb230', 14785236, '', '2025-12-14 10:11:18', '2025-12-14 10:30:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('3aa0cdbe5cacd5671179145a3378f40b', 14785236, '', '2025-12-14 01:31:00', '2025-12-14 01:32:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('3dc2d725ceb5cc186fa0abe494584558', 220172730, '', '2025-12-13 17:16:43', '2025-12-13 17:27:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('432b7fd3663c575e61996583e241a5b9', 14785236, '', '2025-12-08 09:34:36', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('4520f7c7f91d9a5ea912aaafe7f1404e', 220172730, '', '2025-12-13 18:26:40', '2025-12-13 19:09:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('45516527fd022949b2bd852664c4edb4', 220172730, '', '2025-12-13 17:36:23', '2025-12-13 18:25:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('517103ca8d45ed0f47cd5e441748ab3c', 14725638, '', '2025-12-14 10:42:51', '2025-12-14 11:58:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('5b47fa2894c041adf4dd2de9ca1d2c44', 14725638, '', '2025-12-13 22:26:12', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('5d17b93898a6cb2acfe0dbdca0eb4b58', 14725638, '', '2025-12-14 12:38:06', '2025-12-14 13:12:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('5f15ce81974d8148ee1fd07c9b952d03', 14785236, '', '2025-12-01 09:30:32', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('61b9e51236f1110bbac2cfa7a7a2900f', 220172730, '', '2025-12-13 19:26:15', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('68d8751f264ee1e4541f53fa348fe6ee', 220172730, '', '2025-12-13 19:26:17', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('6928fd038fe81ee930c4b408268d8fc6', 220172730, '', '2025-12-07 00:26:35', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('715d258e282d1cd0175103c3e3718f6a', 220172730, '', '2025-12-13 13:08:11', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('71c391b78ab7b5c287fa23077acdbfe5', 14725638, '', '2025-12-13 22:23:04', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('73fb17af187a4d9db353f8a03929cab1', 220172730, '', '2025-12-06 23:11:58', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('74bee8905d862f14ce2582bec668f3d2', 220172730, '', '2025-12-01 00:50:13', '2025-12-01 00:50:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('7a7ab792ebd5666101254efa44e2afce', 14725638, '', '2025-12-14 03:41:19', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('85a9d1d24a42dd5dc8a01fdc31bfb6b1', 14785236, '', '2025-12-08 09:48:59', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('880814261211b9a388fc4fe5cf36443c', 220172730, '', '2025-12-13 16:51:27', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('88f8a0bb4ec3d5e7b7868a9bee03ea5e', 14785236, '', '2025-12-14 03:20:09', '2025-12-14 03:41:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('97bf2b95d0744b1889efd109d14414d0', 220172730, '', '2025-12-13 16:25:22', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('99dab3b2a7d3ed350069d4147b62be13', 220172730, '', '2025-12-14 14:26:26', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('9a4cbe72beaa972f86e164b68071e1fe', 14725638, '', '2025-12-14 01:33:21', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('9b4039cdfd33368aa820b10ef2fd77be', 220172730, '', '2025-12-13 22:56:41', '2025-12-13 23:08:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('9bf54bfd2c14f3bdafa7829921282e5b', 14785236, '', '2025-12-13 22:20:35', '2025-12-13 22:20:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('9edf07fa841a8446bf3fc6cb0a384880', 14725638, '', '2025-12-14 03:13:59', '2025-12-14 03:14:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('9f41f96337b9de93ebe481d1a43f5bb6', 14785236, '', '2025-12-13 17:31:47', '2025-12-13 17:36:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('a4b73b2b47239d8f38cd14518ccfca8b', 220172730, '', '2025-12-07 00:27:52', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('a61b74a4a8f861c1c65f994b02b3c775', 220172730, '', '2025-12-13 20:47:45', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('a76403eb7b85c7876a6506e8370ec039', 14785236, '', '2025-12-01 09:27:56', '2025-12-01 09:29:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('a8a153847a8ce37ba2fb2aff7f58b46e', 14785236, '', '2025-12-07 12:17:13', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('a9d3e6eeced7798c367f91a7b93fe283', 14785236, '', '2025-12-08 09:48:20', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('ac659f78ab841c17f12c33d10b94bfe2', 220172730, '', '2025-12-08 09:36:20', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('b54f3d6d54bed3cc1e3f57f46066f5e9', 14785236, '', '2025-12-13 19:25:56', '2025-12-13 19:26:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('b6d9abf6aab3fb509f82cab3845a2e29', 220172730, '', '2025-12-06 23:41:16', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('b8dae6779e53b9b6f7afd21f5d6ae249', 14785236, '', '2025-12-13 12:39:54', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('bbf488f9bd2089c9ebbbfc89847f2c46', 220172730, '', '2025-12-07 00:38:44', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('bd595a16b8fc061b186c2235862c7fd4', 14785236, '', '2025-12-14 03:14:54', '2025-12-14 03:16:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('beaa326b4abaf3b5806f46eb81fc6aae', 14785236, '', '2025-12-13 22:13:23', '2025-12-13 22:14:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('caec63bd64c1f63bcf0993d5705b2706', 14785236, '', '2025-12-01 00:53:20', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('cdc33e8e2da1ebe13dc9740cf71e1bfa', 220172730, '', '2025-12-13 16:55:14', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('ceae48e6d400d5fe0c9d3d7817311e5f', 220172730, '', '2025-12-08 09:55:21', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('cfd3d9d0e611f0eac13c59669d0e5159', 220172730, '', '2025-12-13 23:17:26', '2025-12-13 23:19:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('d04ab09228d4259cc45cb3f20b9c6ef7', 14785236, '', '2025-12-13 19:28:31', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('d3bb2c59f7f2edc4affb86d525ca23a8', 220172730, '', '2025-12-13 20:47:48', '2025-12-13 20:48:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('dcadbb6d6edac6f7cfee27267db243c7', 14785236, '', '2025-12-13 20:58:06', '2025-12-13 21:57:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('e062bef7e57e04c1615890deecc7fe4f', 14785236, '', '2025-12-13 16:23:54', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('e14a0d9fc41cff3a7c7ddd34ba423e79', 220172730, '', '2025-12-14 11:58:31', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('e400f81d3e88761e94843db27a1f7227', 220172730, '', '2025-12-14 03:41:08', '2025-12-14 03:41:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('e5554ab5bfb2600aa0b7ee3d316c6681', 220172730, '', '2025-12-07 22:18:55', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('ea557d60d148c090a3f8acbac22d20dc', 220172730, '', '2025-12-01 00:52:56', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0'),
('ec40b9b45c1eecb23c81d14ea0c22997', 220172730, '', '2025-12-14 13:12:59', '2025-12-14 13:25:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('ecf106aefa1f3fc92c2d98144f8eab5a', 14725638, '', '2025-12-14 10:31:06', '2025-12-14 10:31:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('ee42e5f40011135602457c494e85b30c', 220172730, '', '2025-12-13 12:20:34', '2025-12-13 12:39:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('f128ad5bcdcdcbae8b2a83d7841b172a', 14725638, '', '2025-12-13 22:29:13', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('f2123cdd523e50e2a5bd74147254d5d4', 220172730, '', '2025-12-14 00:28:20', '2025-12-14 01:30:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('f52de41cb9492076076909dbab38ccf6', 14785236, '', '2025-12-13 16:45:10', '2025-12-13 16:51:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('f844342a6750b9061e3ff73fbd506786', 14785236, '', '2025-12-13 20:10:27', '2025-12-13 20:47:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('fc07454a8a7c1dafe90c949de1c645f0', 220172730, '', '2025-12-14 01:32:43', '2025-12-14 01:33:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('fcff112a41d06a3f60829d863224db4d', 220172730, '', '2025-12-13 20:48:15', '2025-12-13 20:57:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0'),
('fe3197f3bbbe8bf76eb0351964f60479', 220172730, '', '2025-12-14 02:49:24', '2025-12-14 03:13:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 14725638, 'i wanna ask about the donations in gaza', 0, '2025-12-14 11:57:11'),
(2, 1, 14725638, 'how can i donate', 0, '2025-12-14 12:27:06');

-- --------------------------------------------------------

--
-- Structure de la table `message_rate_limits`
--

CREATE TABLE `message_rate_limits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_message_date` date NOT NULL,
  `message_count` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `message_rate_limits`
--

INSERT INTO `message_rate_limits` (`id`, `user_id`, `first_message_date`, `message_count`) VALUES
(1, 14725638, '2025-12-14', 2);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `entity_type`, `entity_id`, `message`, `is_read`, `created_at`) VALUES
(1, 220172730, 14725638, 'post_liked', 'post', 1, 'Yassine Haddadi a aimé votre post', 1, '2025-12-14 10:23:03'),
(2, 220172730, 14725638, 'post_commented', 'post', 1, 'Yassine Haddadi a commenté votre post', 1, '2025-12-14 10:43:42'),
(3, 220172730, 14725638, 'new_message', 'conversation', 1, 'Yassine Haddadi vous a envoyé un message', 1, '2025-12-14 11:57:11'),
(4, 220172730, 14725638, 'new_message', 'conversation', 1, 'Yassine Haddadi vous a envoyé un message', 1, '2025-12-14 12:27:06');

-- --------------------------------------------------------

--
-- Structure de la table `post`
--

CREATE TABLE `post` (
  `id_post` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `Numéro` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `media` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `post`
--

INSERT INTO `post` (`id_post`, `id_user`, `nom`, `Numéro`, `email`, `titre`, `region`, `description`, `date_creation`, `media`) VALUES
(1, 220172730, 'yassine hadded', '50058971', 'yassine.hadded@esprit.tn', 'donate for gaza', 'ben arous', 'lets donate for gaza', '2025-12-14', 'uploads/posts/1765671123_693e00d3d1cca.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `post_comment`
--

CREATE TABLE `post_comment` (
  `id_comment` int(11) NOT NULL,
  `id_post` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `date_comment` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `post_comment`
--

INSERT INTO `post_comment` (`id_comment`, `id_post`, `id_user`, `comment_text`, `date_comment`) VALUES
(1, 1, 220172730, 'i like that', '2025-12-14 00:22:08'),
(2, 1, 220172730, 'good', '2025-12-14 01:52:54'),
(3, 1, 14725638, 'nice', '2025-12-14 10:43:42');

-- --------------------------------------------------------

--
-- Structure de la table `post_like`
--

CREATE TABLE `post_like` (
  `id_like` int(11) NOT NULL,
  `id_post` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `date_like` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `post_like`
--

INSERT INTO `post_like` (`id_like`, `id_post`, `id_user`, `date_like`) VALUES
(20, 1, 14725638, '2025-12-14 10:43:37'),
(24, 1, 220172730, '2025-12-14 11:04:26');

-- --------------------------------------------------------

--
-- Structure de la table `post_report`
--

CREATE TABLE `post_report` (
  `id_report` int(11) NOT NULL,
  `id_post` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_report` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `post_report`
--

INSERT INTO `post_report` (`id_report`, `id_post`, `id_user`, `reason`, `description`, `date_report`, `status`, `admin_notes`, `reviewed_by`, `reviewed_at`) VALUES
(1, 1, 14725638, 'other', 'this post isnt about donations', '2025-12-14 09:11:01', 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `post_save`
--

CREATE TABLE `post_save` (
  `id_save` int(11) NOT NULL,
  `id_post` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `date_save` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `post_save`
--

INSERT INTO `post_save` (`id_save`, `id_post`, `id_user`, `date_save`) VALUES
(6, 1, 220172730, '2025-12-14 02:09:06'),
(7, 1, 14725638, '2025-12-14 02:17:27');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `evenement_id` int(11) NOT NULL,
  `cin` int(11) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `tel` varchar(20) NOT NULL,
  `lieu` varchar(255) NOT NULL,
  `date_naissance` date NOT NULL,
  `softskills` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `evenement_id`, `cin`, `nom`, `tel`, `lieu`, `date_naissance`, `softskills`, `email`, `created_at`) VALUES
(33, 12, 220172730, 'Yassine Haddadi', '+33612345678', 'Sousse, Centre d\'Affaires', '2005-03-07', 'Histoire', 'haddedyassine274@gmail.com', '2025-12-13 18:23:46'),
(34, 13, 220172730, 'Yassine Haddadi', '+33612345678', 'Sousse, Centre d\'Affaires', '2005-03-07', 'Théâtre', 'haddedyassine274@gmail.com', '2025-12-13 19:48:59'),
(35, 6, 220172730, 'Yassine Haddadi', '+33612345678', 'Sousse, Centre d\'Affaires', '2005-03-07', 'Manga', 'haddedyassine274@gmail.com', '2025-12-13 19:54:10');

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

CREATE TABLE `settings` (
  `id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `site_name` varchar(150) NOT NULL,
  `site_logo_path` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
  `recaptcha_site_key` varchar(255) DEFAULT NULL,
  `recaptcha_secret_key` varchar(255) DEFAULT NULL,
  `session_timeout_minutes` int(10) UNSIGNED NOT NULL DEFAULT 30,
  `email_notifications_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `email_sender_name` varchar(255) DEFAULT NULL,
  `email_sender_email` varchar(255) DEFAULT NULL,
  `email_template_welcome` text DEFAULT NULL,
  `email_template_donation` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `cin` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`cin`, `firstname`, `lastname`, `email`, `password`, `role`, `created_at`, `updated_at`, `email_verified`, `verification_token`, `reset_token`, `reset_expires_at`, `token_expiry`, `profile_picture`) VALUES
(14725638, 'Yassine', 'Haddadi', 'haddedyassine969@gmail.com', '$2y$10$IzR9dEbCqGu.8bqYEv5Tjut9PYxoJTO1jChnCI0jIocNIe.15b/vm', 'user', '2025-12-13 22:21:55', '2025-12-13 22:30:20', 1, NULL, NULL, NULL, NULL, 'profile_693ddaec418d60.10238706_1765661420.jpg'),
(14785236, 'Yassine', 'Hadded', 'yassineou.haddadou@gmail.com', '$2y$10$AJTgZFEqb1HxCTSM9o1ZZedhxHJDFGWPk5onRGheEycHVVNR9GsgC', 'admin', '2025-11-30 15:10:21', '2025-12-13 23:17:01', 1, NULL, NULL, NULL, NULL, NULL),
(14785623, 'Chaima', 'Jammoussi', 'Chaimajammoussi@gmail.com', '$2y$10$Txr3a00hfxdQYfaCLnco8.X.CecdIU4STC654IBTGouKy1e6Ht4u2', 'user', '2025-11-30 18:22:28', '2025-11-30 18:22:28', 0, '1ee51f52f0fc931b73576c54993c897db7cd215310d78b297a96c3ba5c586ee0', NULL, NULL, '2025-12-01 18:22:28', NULL),
(74851624, 'Nabil', 'Hadded', 'tornadograte@gmail.com', '$2y$10$.9EBHEbvnVQnOIn0ucWsGObR6d/putAgTVdEHu4sWELXmsSzdTQXS', 'user', '2025-12-13 19:17:26', '2025-12-13 19:17:26', 0, '1f11b2f6e69dfe9642b4023d1925f1f9d3bcc42742ad88165444fee505cce399', NULL, NULL, '2025-12-14 19:17:26', NULL),
(111280799, 'Afef', 'Elayeb', 'afef.elayeb1980@gmail.com', '$2y$10$lIJW/CAfsBFS31js6CE.q.X.CzNgGCv5VzYMhIxx/7lvO.u9H5Yka', 'admin', '2025-11-29 12:40:40', '2025-11-30 15:08:13', 1, NULL, NULL, NULL, NULL, NULL),
(220172730, 'yassine', 'hadded', 'yassine.hadded@esprit.tn', '$2y$10$jQhwXDpOi7/82dEPZ.WWN.FSjYu4ekoH9qUicOPCXrnQ9ODi/vB3a', 'user', '2025-11-29 13:06:34', '2025-12-13 20:48:05', 1, NULL, NULL, NULL, NULL, 'profile_693dc2f5392e87.37705347_1765655285.jpg');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `association`
--
ALTER TABLE `association`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `campagnes`
--
ALTER TABLE `campagnes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `comment_report`
--
ALTER TABLE `comment_report`
  ADD PRIMARY KEY (`id_report`),
  ADD UNIQUE KEY `unique_report` (`id_comment`,`id_user`),
  ADD KEY `idx_comment` (`id_comment`),
  ADD KEY `idx_user` (`id_user`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`date_report`);

--
-- Index pour la table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_conversation` (`user_one_id`,`user_two_id`,`related_entity_type`,`related_entity_id`),
  ADD KEY `idx_user_one` (`user_one_id`),
  ADD KEY `idx_user_two` (`user_two_id`),
  ADD KEY `idx_entity` (`related_entity_type`,`related_entity_id`),
  ADD KEY `idx_last_message` (`last_message_at`),
  ADD KEY `idx_blocked` (`is_blocked`);

--
-- Index pour la table `donor_offers`
--
ALTER TABLE `donor_offers`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `donor_requests`
--
ALTER TABLE `donor_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donation_id` (`donation_id`);

--
-- Index pour la table `dons`
--
ALTER TABLE `dons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dons_association_id` (`association_id`);

--
-- Index pour la table `evenements`
--
ALTER TABLE `evenements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type_evenement`),
  ADD KEY `idx_date_debut` (`date_debut`),
  ADD KEY `idx_date_fin` (`date_fin`),
  ADD KEY `idx_nom` (`nom_evenement`(100));

--
-- Index pour la table `loginsession`
--
ALTER TABLE `loginsession`
  ADD PRIMARY KEY (`SessionID`),
  ADD KEY `userID` (`userID`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_conversation_read` (`conversation_id`,`is_read`);

--
-- Index pour la table `message_rate_limits`
--
ALTER TABLE `message_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`first_message_date`),
  ADD KEY `idx_user_date` (`user_id`,`first_message_date`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Index pour la table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`id_post`),
  ADD KEY `idx_user` (`id_user`);

--
-- Index pour la table `post_comment`
--
ALTER TABLE `post_comment`
  ADD PRIMARY KEY (`id_comment`),
  ADD KEY `idx_post` (`id_post`),
  ADD KEY `idx_user` (`id_user`);

--
-- Index pour la table `post_like`
--
ALTER TABLE `post_like`
  ADD PRIMARY KEY (`id_like`),
  ADD UNIQUE KEY `unique_like` (`id_post`,`id_user`),
  ADD KEY `idx_post` (`id_post`),
  ADD KEY `idx_user` (`id_user`);

--
-- Index pour la table `post_report`
--
ALTER TABLE `post_report`
  ADD PRIMARY KEY (`id_report`),
  ADD UNIQUE KEY `unique_report` (`id_post`,`id_user`),
  ADD KEY `idx_post` (`id_post`),
  ADD KEY `idx_user` (`id_user`);

--
-- Index pour la table `post_save`
--
ALTER TABLE `post_save`
  ADD PRIMARY KEY (`id_save`),
  ADD UNIQUE KEY `unique_save` (`id_post`,`id_user`),
  ADD KEY `idx_post` (`id_post`),
  ADD KEY `idx_user` (`id_user`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_evenement` (`evenement_id`),
  ADD KEY `idx_nom` (`nom`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_cin` (`cin`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`cin`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_lastname` (`lastname`),
  ADD KEY `idx_fullname` (`lastname`,`firstname`),
  ADD KEY `idx_users_verification_token` (`verification_token`),
  ADD KEY `idx_users_reset_token` (`reset_token`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `association`
--
ALTER TABLE `association`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `campagnes`
--
ALTER TABLE `campagnes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `comment_report`
--
ALTER TABLE `comment_report`
  MODIFY `id_report` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `donor_offers`
--
ALTER TABLE `donor_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT pour la table `donor_requests`
--
ALTER TABLE `donor_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT pour la table `dons`
--
ALTER TABLE `dons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `evenements`
--
ALTER TABLE `evenements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `message_rate_limits`
--
ALTER TABLE `message_rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `post`
--
ALTER TABLE `post`
  MODIFY `id_post` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `post_comment`
--
ALTER TABLE `post_comment`
  MODIFY `id_comment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `post_like`
--
ALTER TABLE `post_like`
  MODIFY `id_like` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `post_report`
--
ALTER TABLE `post_report`
  MODIFY `id_report` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `post_save`
--
ALTER TABLE `post_save`
  MODIFY `id_save` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `cin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220172731;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `donor_requests`
--
ALTER TABLE `donor_requests`
  ADD CONSTRAINT `donor_requests_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donor_offers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `dons`
--
ALTER TABLE `dons`
  ADD CONSTRAINT `fk_dons_association` FOREIGN KEY (`association_id`) REFERENCES `association` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `loginsession`
--
ALTER TABLE `loginsession`
  ADD CONSTRAINT `loginsession_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`cin`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`evenement_id`) REFERENCES `evenements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
