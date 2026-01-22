-- Add edit audit columns to regularize_requests
ALTER TABLE `regularize_requests`
  ADD COLUMN `edited_by` VARCHAR(50) DEFAULT NULL,
  ADD COLUMN `edited_on` DATETIME DEFAULT NULL,
  ADD COLUMN `edit_count` INT UNSIGNED DEFAULT 0;
