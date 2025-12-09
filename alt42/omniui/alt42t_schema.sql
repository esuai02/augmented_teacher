-- Alt42t Database Schema for Exam System
-- Create database if not exists
CREATE DATABASE IF NOT EXISTS alt42t CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE alt42t;

-- Student exam settings table
CREATE TABLE IF NOT EXISTS student_exam_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    school VARCHAR(200) NOT NULL,
    grade VARCHAR(50) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    exam_type VARCHAR(50) NOT NULL,
    exam_start_date DATE,
    exam_end_date DATE,
    math_exam_date DATE,
    exam_scope TEXT,
    exam_status ENUM('expected', 'confirmed') DEFAULT 'expected',
    study_level ENUM('concept', 'review', 'practice') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_school_exam (school, exam_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exam information shared by students (for showing same school exam data)
CREATE TABLE IF NOT EXISTS exam_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school VARCHAR(200) NOT NULL,
    exam_type VARCHAR(50) NOT NULL,
    exam_start_date DATE NOT NULL,
    exam_end_date DATE,
    math_exam_date DATE,
    exam_scope TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_school_type (school, exam_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions table (for login tracking)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- School information table (for school homepage links)
CREATE TABLE IF NOT EXISTS schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL UNIQUE,
    homepage_url VARCHAR(500),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;