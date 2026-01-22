-- Migration script to update employees table structure
-- Add new fields from payload and remove fields now handled by separate tables

-- Add new columns to employees table
ALTER TABLE `employees` 
ADD COLUMN `company` varchar(100) DEFAULT NULL AFTER `shift_description`,
ADD COLUMN `pay_group` varchar(50) DEFAULT NULL AFTER `company`,
ADD COLUMN `city_name` varchar(100) DEFAULT NULL AFTER `religion`,
ADD COLUMN `cluster` varchar(100) DEFAULT NULL AFTER `city_name`,
ADD COLUMN `location_name` varchar(100) DEFAULT NULL AFTER `cluster`,
ADD COLUMN `locationName` varchar(100) DEFAULT NULL AFTER `location_name`,
ADD COLUMN `mail` varchar(100) DEFAULT NULL AFTER `email`,
ADD COLUMN `date_of_birth` date DEFAULT NULL AFTER `dob`,
ADD COLUMN `father_or_husband_name` varchar(100) DEFAULT NULL AFTER `father_husband_name`,
ADD COLUMN `aadhar_number` varchar(20) DEFAULT NULL AFTER `ifsc_code`,
ADD COLUMN `pan_number` varchar(20) DEFAULT NULL AFTER `aadhar_number`,
ADD COLUMN `tds_status` varchar(50) DEFAULT NULL AFTER `pan_number`,
ADD COLUMN `agreement_valid_date` date DEFAULT NULL AFTER `latest_agreement_valid_date`,
ADD COLUMN `agreement_end_date` date DEFAULT NULL AFTER `latest_agreement_end_date`,
ADD COLUMN `contract_fee_revision_amount` decimal(12,2) DEFAULT NULL AFTER `latest_contract_fee_revision_amount`;

-- Remove columns that are now handled by separate tables
-- Note: Be careful with these operations in production - backup data first!

-- These fields are now handled by employee_qualifications table
-- ALTER TABLE `employees` DROP COLUMN `highest_qualification`;
-- ALTER TABLE `employees` DROP COLUMN `university`;
-- ALTER TABLE `employees` DROP COLUMN `passing_year`;

-- These fields are now handled by employee_experience table  
-- ALTER TABLE `employees` DROP COLUMN `previous_company`;
-- ALTER TABLE `employees` DROP COLUMN `previous_designation`;
-- ALTER TABLE `employees` DROP COLUMN `previous_experience_years`;

-- The documents field is now handled by employee_documents table
-- ALTER TABLE `employees` DROP COLUMN `documents`;

-- Update existing data mappings if needed
UPDATE `employees` SET 
    `date_of_birth` = `dob` WHERE `date_of_birth` IS NULL AND `dob` IS NOT NULL,
    `father_or_husband_name` = `father_husband_name` WHERE `father_or_husband_name` IS NULL AND `father_husband_name` IS NOT NULL,
    `agreement_valid_date` = `latest_agreement_valid_date` WHERE `agreement_valid_date` IS NULL AND `latest_agreement_valid_date` IS NOT NULL,
    `agreement_end_date` = `latest_agreement_end_date` WHERE `agreement_end_date` IS NULL AND `latest_agreement_end_date` IS NOT NULL,
    `contract_fee_revision_amount` = `latest_contract_fee_revision_amount` WHERE `contract_fee_revision_amount` IS NULL AND `latest_contract_fee_revision_amount` IS NOT NULL,
    `mail` = `email` WHERE `mail` IS NULL AND `email` IS NOT NULL;

-- Add indexes for better performance
CREATE INDEX `idx_employee_code` ON `employees` (`employee_code`);
CREATE INDEX `idx_department` ON `employees` (`department`);
CREATE INDEX `idx_status` ON `employees` (`status`);
CREATE INDEX `idx_resignation` ON `employees` (`resignation`);
CREATE INDEX `idx_reporting_manager` ON `employees` (`reporting_manager_empcode`);

-- Add indexes to related tables
CREATE INDEX `idx_emp_code_qual` ON `employee_qualifications` (`employee_code`);
CREATE INDEX `idx_emp_code_exp` ON `employee_experience` (`employee_code`);
CREATE INDEX `idx_emp_id_doc` ON `employee_documents` (`emp_id`);

-- Ensure proper constraints
ALTER TABLE `employee_qualifications` 
MODIFY COLUMN `employee_code` varchar(50) NOT NULL;

ALTER TABLE `employee_experience` 
MODIFY COLUMN `employee_code` varchar(50) NOT NULL;
