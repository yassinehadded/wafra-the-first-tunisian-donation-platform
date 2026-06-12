-- =====================================================
-- Comment Report Table Creation Script
-- =====================================================
-- This script creates the comment_report table for storing
-- user reports about inappropriate comments
-- =====================================================

CREATE TABLE IF NOT EXISTS `comment_report` (
    `id_report` INT(11) NOT NULL AUTO_INCREMENT,
    `id_comment` INT(11) NOT NULL,
    `id_user` INT(11) NOT NULL,
    `reason` ENUM('spam', 'harassment', 'inappropriate_content', 'other') NOT NULL DEFAULT 'other',
    `description` TEXT NULL,
    `status` ENUM('pending', 'reviewed', 'resolved') NOT NULL DEFAULT 'pending',
    `admin_notes` TEXT NULL,
    `reviewed_by` INT(11) NULL,
    `reviewed_at` TIMESTAMP NULL,
    `date_report` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_report`),
    INDEX `idx_comment` (`id_comment`),
    INDEX `idx_user` (`id_user`),
    INDEX `idx_status` (`status`),
    INDEX `idx_date` (`date_report`),
    UNIQUE KEY `unique_report` (`id_comment`, `id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Table Structure Explanation:
-- =====================================================
-- id_report: Primary key, auto-increment
-- id_comment: Foreign key to post_comment.id_comment
-- id_user: Foreign key to users.cin (user who reported)
-- reason: Predefined reasons for reporting
-- description: Optional detailed description from reporter
-- status: Report status (pending/reviewed/resolved)
-- admin_notes: Internal notes for admins
-- reviewed_by: Admin user ID who reviewed (FK to users.cin)
-- reviewed_at: Timestamp when report was reviewed
-- date_report: When the report was created
-- 
-- UNIQUE KEY prevents same user from reporting same comment twice
-- =====================================================





