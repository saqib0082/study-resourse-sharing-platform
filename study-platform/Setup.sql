-- =============================================
--  StudyVault — Complete Database Setup
--  Run in phpMyAdmin → SQL tab
--  OR use autofix.php for automatic setup
-- =============================================

CREATE DATABASE IF NOT EXISTS study_platform
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE study_platform;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(100)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('student','admin') NOT NULL DEFAULT 'student',
    last_login DATETIME DEFAULT NULL,
    is_online  TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notes table (file_size stores bytes for quota tracking)
CREATE TABLE IF NOT EXISTS notes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    subject     VARCHAR(100) NOT NULL,
    category_id INT DEFAULT NULL,
    file_name   VARCHAR(255) NOT NULL,
    file_path   VARCHAR(255) NOT NULL,
    file_size   BIGINT DEFAULT 0,           -- bytes, for quota tracking
    uploaded_by INT NOT NULL,
    downloads   INT DEFAULT 0,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If upgrading existing install, add file_size column:
-- ALTER TABLE notes ADD COLUMN file_size BIGINT DEFAULT 0 AFTER file_path;

-- Default categories
INSERT IGNORE INTO categories (name) VALUES
    ('Programming'), ('Mathematics'), ('Physics'),
    ('Web Technologies'), ('Database'), ('Other');

-- =============================================
--  Default Admin Account
--  Email:    admin@studyvault.com
--  Password: admin123
--  ⚠️ Change password after first login!
--
--  Run autofix.php to auto-generate this with
--  a fresh bcrypt hash — recommended approach.
-- =============================================
-- (Use autofix.php to insert admin — it generates
--  a fresh hash ensuring password_verify works)
