-- Option 2: an admin may create their own 'user' accounts within their own scope.
-- owner_admin_id = NULL means: created by super, company-wide (previous behavior).
-- owner_admin_id = <id> means: created by that admin, sees only that admin's own codes.

SET @db := DATABASE();

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'owner_admin_id'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `users` ADD COLUMN `owner_admin_id` INT(25) DEFAULT NULL',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
