-- Additive columns only. Never DROP.
-- Safe to run more than once (MariaDB 10.3.3+ / MySQL 8+ IF NOT EXISTS).
-- The PHP app also adds these on first load via includes/schema_extend.php.
-- Error #1060 means the column is already present — that is OK.

ALTER TABLE `messes`
  ADD COLUMN IF NOT EXISTS `description` TEXT NULL;

ALTER TABLE `messes`
  ADD COLUMN IF NOT EXISTS `address` VARCHAR(255) NULL;

ALTER TABLE `mess_members`
  ADD COLUMN IF NOT EXISTS `status` ENUM('active','left') NOT NULL DEFAULT 'active';

ALTER TABLE `mess_members`
  ADD COLUMN IF NOT EXISTS `left_at` TIMESTAMP NULL DEFAULT NULL;

ALTER TABLE `deposits`
  ADD COLUMN IF NOT EXISTS `month_id` INT NULL DEFAULT NULL AFTER `mess_id`;
