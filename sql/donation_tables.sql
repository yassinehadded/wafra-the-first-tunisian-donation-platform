-- Donation Tables Schema
-- Table: donor_offers

-- Drop table if exists (for clean install)
-- DROP TABLE IF EXISTS `donor_offers`;

CREATE TABLE IF NOT EXISTS `donor_offers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `donor_name` VARCHAR(255) NOT NULL,
  `donor_email` TEXT DEFAULT NULL,
  `donor_phone` INT(8) DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `quantity` INT(11) DEFAULT 1,
  `item_image` VARCHAR(255) DEFAULT NULL,
  `date` DATE DEFAULT CURDATE(),
  `status` ENUM('active','fulfilled') DEFAULT 'active',
  `user_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraint only if users table exists and user_id column exists
-- ALTER TABLE `donor_offers` 
--   ADD CONSTRAINT `fk_donor_offers_user` 
--   FOREIGN KEY (`user_id`) REFERENCES `users` (`cin`) 
--   ON DELETE SET NULL ON UPDATE CASCADE;

-- Migration: Add columns if they don't exist (for existing tables)
-- ALTER TABLE `donor_offers` 
--   ADD COLUMN IF NOT EXISTS `user_id` INT(11) DEFAULT NULL AFTER `status`,
--   ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `user_id`,
--   ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;


