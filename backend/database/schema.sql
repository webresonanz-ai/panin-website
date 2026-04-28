CREATE DATABASE IF NOT EXISTS panin_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE panin_dashboard;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_api_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS guests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    suite VARCHAR(100) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('active', 'pending') NOT NULL DEFAULT 'active',
    special_requests TEXT NULL,
    vip_status TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO users (name, email, password_hash)
SELECT 'Front Desk Admin', 'admin@luxuryhotel.test', '$2y$12$D002k3/UfVMYxorR896X8erG89F9GtQeLjDnFdc8wAQ79TYa68qA.'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@luxuryhotel.test'
);

INSERT INTO guests (full_name, email, phone, suite, check_in, check_out, status, special_requests, vip_status)
SELECT 'Isabella Rossi', 'isabella.rossi@email.com', '+39 123 456 7890', 'Imperial Suite', '2026-04-30', '2026-05-05', 'active', 'Champagne upon arrival, Extra pillows', 1
WHERE NOT EXISTS (SELECT 1 FROM guests WHERE email = 'isabella.rossi@email.com');

INSERT INTO guests (full_name, email, phone, suite, check_in, check_out, status, special_requests, vip_status)
SELECT 'Alexander Chen', 'alex.chen@email.com', '+86 987 654 3210', 'Royal Penthouse', '2026-05-01', '2026-05-07', 'pending', 'Vegan meal plan, Airport transfer', 1
WHERE NOT EXISTS (SELECT 1 FROM guests WHERE email = 'alex.chen@email.com');

INSERT INTO guests (full_name, email, phone, suite, check_in, check_out, status, special_requests, vip_status)
SELECT 'Victoria Sterling', 'vicky.sterling@email.com', '+44 20 1234 5678', 'Diamond Suite', '2026-05-03', '2026-05-06', 'active', 'Spa appointments daily', 0
WHERE NOT EXISTS (SELECT 1 FROM guests WHERE email = 'vicky.sterling@email.com');
