-- Notifications Table
-- This table stores all user and admin notifications

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    actor_id INT NULL,
    type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at),
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notification Types:
-- User notifications:
--   - post_liked: Someone liked your post
--   - post_commented: Someone commented on your post
--   - comment_replied: Someone replied to your comment
--   - post_milestone: Your post reached engagement milestones
--   - post_removed: Your post was hidden or removed by admin
--
-- Admin notifications:
--   - admin_post_reported: New reported post
--   - admin_comment_reported: New reported comment
--   - admin_user_threshold: User exceeded report threshold





