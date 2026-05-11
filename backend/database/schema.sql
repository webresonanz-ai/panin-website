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
    registration_number VARCHAR(64) NULL DEFAULT 'PANIN_00',
    ga_so_position VARCHAR(150) NULL,
    seat_number VARCHAR(50) NULL,
    phone_number VARCHAR(20) NULL,
    wa_sent_time DATETIME NULL,
    checked_in_at DATETIME NULL,
    check_in_method VARCHAR(32) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

UPDATE guests
SET registration_number = CONCAT(
    'PANIN_',
    id,
    '_',
    UNIX_TIMESTAMP(created_at),
    '_',
    UPPER(SUBSTRING(MD5(CONCAT(id, '-', created_at)), 1, 4))
)
WHERE registration_number IS NULL OR registration_number = '';

ALTER TABLE guests MODIFY COLUMN registration_number VARCHAR(64) NOT NULL;

SET @drop_guest_email = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'guests' AND column_name = 'email'
    ),
    'ALTER TABLE guests DROP COLUMN email',
    'SELECT 1'
);
PREPARE stmt FROM @drop_guest_email;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_guest_phone = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'guests' AND column_name = 'phone'
    ),
    'ALTER TABLE guests DROP COLUMN phone',
    'SELECT 1'
);
PREPARE stmt FROM @drop_guest_phone;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_guest_suite = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'guests' AND column_name = 'suite'
    ),
    'ALTER TABLE guests DROP COLUMN suite',
    'SELECT 1'
);
PREPARE stmt FROM @drop_guest_suite;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO users (name, email, password_hash)
SELECT 'Front Desk Admin', 'admin@luxuryhotel.test', '$2y$12$D002k3/UfVMYxorR896X8erG89F9GtQeLjDnFdc8wAQ79TYa68qA.'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@luxuryhotel.test'
);

INSERT INTO guests (full_name, company, position, seat_number, check_in, check_out, status, special_requests, vip_status)
SELECT 'Isabella Rossi', 'Rossi Holdings', 'Chairwoman', 'A01', '2026-04-30', '2026-05-05', 'active', 'Champagne upon arrival, Extra pillows', 1
WHERE NOT EXISTS (SELECT 1 FROM guests WHERE full_name = 'Isabella Rossi' AND seat_number = 'A01');

INSERT INTO guests (full_name, company, position, seat_number, check_in, check_out, status, special_requests, vip_status)
SELECT 'Alexander Chen', 'Chen Ventures', 'Managing Director', 'B14', '2026-05-01', '2026-05-07', 'pending', 'Vegan meal plan, Airport transfer', 1
WHERE NOT EXISTS (SELECT 1 FROM guests WHERE full_name = 'Alexander Chen' AND seat_number = 'B14');

INSERT INTO guests (full_name, company, position, seat_number, check_in, check_out, status, special_requests, vip_status)
SELECT 'Victoria Sterling', 'Sterling & Co.', 'Brand Director', 'C07', '2026-05-03', '2026-05-06', 'active', 'Spa appointments daily', 0
WHERE NOT EXISTS (SELECT 1 FROM guests WHERE full_name = 'Victoria Sterling' AND seat_number = 'C07');
