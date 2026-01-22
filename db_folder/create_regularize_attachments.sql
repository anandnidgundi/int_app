-- Create attachments table for regularize requests
CREATE TABLE IF NOT EXISTS `regularize_attachments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime` VARCHAR(100) DEFAULT NULL,
  `size` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`),
  CONSTRAINT `fk_regularize_request` FOREIGN KEY (`request_id`) REFERENCES `regularize_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: add attachment columns to regularize_requests for backward compatibility
ALTER TABLE `regularize_requests`
  ADD COLUMN `attachment_path` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `attachment_name` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `attachment_mime` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN `attachment_size` INT UNSIGNED DEFAULT NULL;
