-- Self-registration: adds email as the login identifier (mirrors qr-vip's
-- 006_vip_and_create_rights.sql email migration) plus a marker for
-- self-registered accounts.

SET @db := DATABASE();

-- 1. email: becomes the login identifier going forward. Nullable so existing accounts (which
--    have no email) don't violate a NOT NULL constraint; a unique index still allows unlimited
--    NULLs in InnoDB, so pre-existing NULL-email rows never collide with each other.
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) DEFAULT NULL',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND INDEX_NAME = 'email'
);
SET @sql := IF(@idx_exists = 0,
  'ALTER TABLE `users` ADD UNIQUE KEY `email` (`email`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. must_set_email: forces existing (pre-migration) accounts through a one-time "set your
--    email" interstitial on next login, mirroring must_change_password. New accounts created
--    after this migration always have an email from creation, so they never get this flag.
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'must_set_email'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `users` ADD COLUMN `must_set_email` TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `users` SET `must_set_email` = 1 WHERE `email` IS NULL;

-- 3. self_registered_at: NULL for accounts created by an admin/super, set for accounts created
--    through register.php. Purely informational/reporting for now.
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'self_registered_at'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `users` ADD COLUMN `self_registered_at` DATETIME DEFAULT NULL',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
