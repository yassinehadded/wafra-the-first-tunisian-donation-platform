-- Reclamation System Tables
-- Based on wafra-reclam structure

CREATE TABLE IF NOT EXISTS `reclamation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'User CIN from users table',
  `nom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'Service, Produit, Livraison, Facturation, Technique, Autre',
  `priorite` varchar(20) NOT NULL COMMENT 'Basse, Moyenne, Haute',
  `description` text NOT NULL,
  `statut` varchar(20) DEFAULT 'En attente' COMMENT 'En attente, En cours, Répondu',
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_statut` (`statut`),
  KEY `idx_priorite` (`priorite`),
  KEY `idx_type` (`type`),
  KEY `idx_date_creation` (`date_creation`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`cin`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reponses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reclamation_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL COMMENT 'Admin user CIN',
  `message` text NOT NULL,
  `date_reponse` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reclamation_id` (`reclamation_id`),
  KEY `idx_admin_id` (`admin_id`),
  FOREIGN KEY (`reclamation_id`) REFERENCES `reclamation`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`cin`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





