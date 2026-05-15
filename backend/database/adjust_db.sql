ALTER TABLE guests ADD COLUMN IF NOT EXISTS company VARCHAR(150) NULL AFTER full_name;
ALTER TABLE guests ADD COLUMN IF NOT EXISTS position VARCHAR(150) NULL AFTER company;
ALTER TABLE guests ADD COLUMN IF NOT EXISTS seat_number VARCHAR(50) NULL AFTER position;
ALTER TABLE guests ADD COLUMN IF NOT EXISTS registration_number VARCHAR(64) NULL AFTER full_name;
ALTER TABLE guests ADD COLUMN IF NOT EXISTS checked_in_at DATETIME NULL AFTER vip_status;
ALTER TABLE guests ADD COLUMN IF NOT EXISTS check_in_method VARCHAR(32) NULL AFTER checked_in_at;
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin', 'manager') NOT NULL DEFAULT 'user' AFTER email;
UPDATE users SET role = 'admin' WHERE email = 'admin@luxuryhotel.test';

----------------------------------------------------------------------------------------------------------

ALTER TABLE guests DROP COLUMN position;
ALTER TABLE guests DROP COLUMN company;
ALTER TABLE guests ADD COLUMN ga_so_position VARCHAR(150) NULL AFTER full_name;
ALTER TABLE guests ADD COLUMN wa_sent_time DATETIME NULL AFTER vip_status;
ALTER TABLE guests DROP COLUMN check_in;
ALTER TABLE guests DROP COLUMN check_out;
ALTER TABLE guests DROP COLUMN special_requests;
ALTER TABLE guests DROP COLUMN vip_status;
ALTER TABLE guests DROP COLUMN status;
ALTER TABLE guests ADD COLUMN phone_number VARCHAR(32) NULL AFTER seat_number;
ALTER TABLE guests
ALTER COLUMN registration_number SET DEFAULT NULL;

----------------------------------------------------------------------------------------------------------------

ALTER TABLE guests ADD COLUMN wasender_msgId VARCHAR(100) NULL AFTER phone_number;
ALTER TABLE guests ADD COLUMN wasender_status ENUM('error', 'pending', 'sent', 'delivered', 'read', 'played') NULL AFTER wa_sent_time;
ALTER TABLE guests MODIFY COLUMN wasender_status ENUM('error', 'pending', 'sent', 'delivered', 'read', 'played') NULL DEFAULT 'pending';