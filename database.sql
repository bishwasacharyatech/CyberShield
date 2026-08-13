CREATE DATABASE IF NOT EXISTS cybershield CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cybershield;

-- 1. USERS
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT '',
    role ENUM('user','analyst','admin') DEFAULT 'user',
    status ENUM('active','suspended') DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- defULT DATA
-- Admin account (password: admin123)
INSERT IGNORE INTO users (full_name, email, username, password, role, status)
VALUES ('System Admin','admin@cybershield.local','admin',
'$2y$12$tupP5G7hw4nuPncjBBaA6.Nfcf4DWWWfWPaRjT7Js1NjkycxNbv.q','admin','active');