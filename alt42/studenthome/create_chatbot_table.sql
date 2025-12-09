-- Chatbot messages table for learning mode-aware conversations
CREATE TABLE IF NOT EXISTS mdl_chatbot_messages (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT(10) NOT NULL,
    learning_mode VARCHAR(50) NOT NULL,  -- current learning mode (curriculum, exam, custom, etc.)
    message_type VARCHAR(10) NOT NULL,   -- 'user' or 'bot'
    message TEXT NOT NULL,
    context TEXT,                        -- conversation context for AI
    timestamp BIGINT(10) NOT NULL,
    INDEX idx_student_mode (student_id, learning_mode),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add chatbot preferences table for personalization
CREATE TABLE IF NOT EXISTS mdl_chatbot_preferences (
    id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT(10) NOT NULL UNIQUE,
    bot_name VARCHAR(100) DEFAULT '학습 도우미',
    welcome_message TEXT,
    personality_traits TEXT,  -- JSON field for storing personality configuration
    last_active BIGINT(10),
    created_at BIGINT(10) NOT NULL,
    updated_at BIGINT(10),
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;