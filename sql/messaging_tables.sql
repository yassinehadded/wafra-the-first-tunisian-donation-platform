-- Messaging System Tables
-- Private 1-to-1 conversations between users

-- Conversations Table
CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_one_id INT NOT NULL,
    user_two_id INT NOT NULL,
    related_entity_type VARCHAR(50) NULL COMMENT 'donation, post, request',
    related_entity_id INT NULL,
    last_message_at TIMESTAMP NULL,
    is_blocked TINYINT(1) DEFAULT 0,
    blocked_by INT NULL COMMENT 'User ID who blocked the conversation',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_one (user_one_id),
    INDEX idx_user_two (user_two_id),
    INDEX idx_entity (related_entity_type, related_entity_id),
    INDEX idx_last_message (last_message_at),
    INDEX idx_blocked (is_blocked),
    UNIQUE KEY unique_conversation (user_one_id, user_two_id, related_entity_type, related_entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages Table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation (conversation_id),
    INDEX idx_sender (sender_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at),
    INDEX idx_conversation_read (conversation_id, is_read),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Message Rate Limiting Table (to prevent spam)
CREATE TABLE IF NOT EXISTS message_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_message_date DATE NOT NULL,
    message_count INT DEFAULT 1,
    INDEX idx_user_date (user_id, first_message_date),
    UNIQUE KEY unique_user_date (user_id, first_message_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;





