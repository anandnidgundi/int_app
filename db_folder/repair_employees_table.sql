-- SQL Script to Repair/Update Employees Table Structure
-- Based on NewUserModel allowedFields and payload requirements
-- Execute these statements one by one and check for errors

-- First, let's check the current table structure
-- DESCRIBE employees;

-- Add missing columns that are required by the model
-- Basic employee information fields
ALTER TABLE `employees` 
ADD COLUMN IF NOT EXISTS `company` varchar(100) DEFAULT NULL AFTER `shift_description`,
ADD COLUMN IF NOT EXISTS `pay_group` varchar(50) DEFAULT NULL AFTER `company`,
ADD COLUMN IF NOT EXISTS `city_name` varchar(100) DEFAULT NULL AFTER `caste`,
ADD COLUMN IF NOT EXISTS `cluster` varchar(100) DEFAULT NULL AFTER `city_name`,
ADD COLUMN IF NOT EXISTS `location_name` varchar(100) DEFAULT NULL AFTER `cluster`,
ADD COLUMN IF NOT EXISTS `locationName` varchar(100) DEFAULT NULL AFTER `location_name`,
ADD COLUMN IF NOT EXISTS `total_experience` decimal(4,1) DEFAULT NULL AFTER `locationName`,
ADD COLUMN IF NOT EXISTS `mail` varchar(100) DEFAULT NULL AFTER `email`,
ADD COLUMN IF NOT EXISTS `date_of_birth` date DEFAULT NULL AFTER `dob`,
ADD COLUMN IF NOT EXISTS `father_or_husband_name` varchar(100) DEFAULT NULL AFTER `father_husband_name`;

-- Financial and compliance fields
ALTER TABLE `employees` 
ADD COLUMN IF NOT EXISTS `aadhar_number` varchar(20) DEFAULT NULL AFTER `ifsc_code`,
ADD COLUMN IF NOT EXISTS `pan_number` varchar(20) DEFAULT NULL AFTER `aadhar_number`,
ADD COLUMN IF NOT EXISTS `tds_status` varchar(50) DEFAULT NULL AFTER `pan_number`;

-- Agreement and contract fields
ALTER TABLE `employees` 
ADD COLUMN IF NOT EXISTS `agreement_valid_date` date DEFAULT NULL AFTER `latest_agreement_valid_date`,
ADD COLUMN IF NOT EXISTS `agreement_end_date` date DEFAULT NULL AFTER `latest_agreement_end_date`,
ADD COLUMN IF NOT EXISTS `contract_fee_revision_amount` decimal(12,2) DEFAULT NULL AFTER `latest_contract_fee_revision_amount`;

-- Ensure all existing required columns exist with correct data types
ALTER TABLE `employees` 
MODIFY COLUMN `employee_code` varchar(50) NOT NULL,
MODIFY COLUMN `employee_name` varchar(100) NOT NULL,
MODIFY COLUMN `designation` varchar(100) DEFAULT NULL,
MODIFY COLUMN `department` varchar(100) DEFAULT NULL,
MODIFY COLUMN `joining_date` date DEFAULT NULL,
MODIFY COLUMN `employment_type` varchar(50) DEFAULT NULL,
MODIFY COLUMN `mobile` varchar(15) DEFAULT NULL,
MODIFY COLUMN `email` varchar(100) DEFAULT NULL,
MODIFY COLUMN `dob` date DEFAULT NULL,
MODIFY COLUMN `gender` enum('Male','Female','Other') DEFAULT NULL,
MODIFY COLUMN `father_husband_name` varchar(100) DEFAULT NULL,
MODIFY COLUMN `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT NULL,
MODIFY COLUMN `blood_group` varchar(10) DEFAULT NULL,
MODIFY COLUMN `religion` varchar(50) DEFAULT NULL,
MODIFY COLUMN `caste` varchar(50) DEFAULT NULL,
MODIFY COLUMN `department_category` varchar(100) DEFAULT NULL,
MODIFY COLUMN `main_department` varchar(100) DEFAULT NULL,
MODIFY COLUMN `sub_department` varchar(100) DEFAULT NULL,
MODIFY COLUMN `designation_name` varchar(100) DEFAULT NULL,
MODIFY COLUMN `grade_name` varchar(100) DEFAULT NULL,
MODIFY COLUMN `position` varchar(100) DEFAULT NULL,
MODIFY COLUMN `reporting_manager_name` varchar(100) DEFAULT NULL,
MODIFY COLUMN `reporting_manager_empcode` varchar(50) DEFAULT NULL,
MODIFY COLUMN `functional_manager_name` varchar(100) DEFAULT NULL,
MODIFY COLUMN `skip_level_manager_empcode` varchar(50) DEFAULT NULL,
MODIFY COLUMN `shift_description` varchar(100) DEFAULT NULL,
MODIFY COLUMN `bank_account_name` varchar(100) DEFAULT NULL,
MODIFY COLUMN `bank_account_number` varchar(30) DEFAULT NULL,
MODIFY COLUMN `ifsc_code` varchar(20) DEFAULT NULL,
MODIFY COLUMN `ctc` decimal(12,2) DEFAULT NULL,
MODIFY COLUMN `latest_agreement_valid_date` date DEFAULT NULL,
MODIFY COLUMN `latest_agreement_end_date` date DEFAULT NULL,
MODIFY COLUMN `latest_contract_fee_revision_amount` decimal(12,2) DEFAULT NULL,
MODIFY COLUMN `resignation` enum('Yes','No') DEFAULT 'No',
MODIFY COLUMN `resignation_date` date DEFAULT NULL,
MODIFY COLUMN `relieving_date` date DEFAULT NULL,
MODIFY COLUMN `last_working_date` date DEFAULT NULL,
MODIFY COLUMN `last_pay_date` date DEFAULT NULL,
MODIFY COLUMN `separation_status` varchar(50) DEFAULT NULL,
MODIFY COLUMN `notice_period` int(11) DEFAULT NULL,
MODIFY COLUMN `status` char(1) NOT NULL DEFAULT 'A';

-- Ensure timestamps columns exist
ALTER TABLE `employees` 
ADD COLUMN IF NOT EXISTS `created_at` timestamp NULL DEFAULT current_timestamp(),
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

-- Update existing data to map similar fields
UPDATE `employees` SET 
    `date_of_birth` = `dob` WHERE `date_of_birth` IS NULL AND `dob` IS NOT NULL;

UPDATE `employees` SET 
    `father_or_husband_name` = `father_husband_name` WHERE `father_or_husband_name` IS NULL AND `father_husband_name` IS NOT NULL;

UPDATE `employees` SET 
    `agreement_valid_date` = `latest_agreement_valid_date` WHERE `agreement_valid_date` IS NULL AND `latest_agreement_valid_date` IS NOT NULL;

UPDATE `employees` SET 
    `agreement_end_date` = `latest_agreement_end_date` WHERE `agreement_end_date` IS NULL AND `latest_agreement_end_date` IS NOT NULL;

UPDATE `employees` SET 
    `contract_fee_revision_amount` = `latest_contract_fee_revision_amount` WHERE `contract_fee_revision_amount` IS NULL AND `latest_contract_fee_revision_amount` IS NOT NULL;

UPDATE `employees` SET 
    `mail` = `email` WHERE `mail` IS NULL AND `email` IS NOT NULL;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_employee_code` ON `employees` (`employee_code`);
CREATE INDEX IF NOT EXISTS `idx_employee_name` ON `employees` (`employee_name`);
CREATE INDEX IF NOT EXISTS `idx_department` ON `employees` (`department`);
CREATE INDEX IF NOT EXISTS `idx_status` ON `employees` (`status`);
CREATE INDEX IF NOT EXISTS `idx_resignation` ON `employees` (`resignation`);
CREATE INDEX IF NOT EXISTS `idx_reporting_manager` ON `employees` (`reporting_manager_empcode`);
CREATE INDEX IF NOT EXISTS `idx_joining_date` ON `employees` (`joining_date`);
CREATE INDEX IF NOT EXISTS `idx_email` ON `employees` (`email`);
CREATE INDEX IF NOT EXISTS `idx_mobile` ON `employees` (`mobile`);
CREATE INDEX IF NOT EXISTS `idx_created_at` ON `employees` (`created_at`);

-- Ensure the primary key and unique constraint exist
ALTER TABLE `employees` 
ADD PRIMARY KEY IF NOT EXISTS (`emp_id`),
ADD UNIQUE KEY IF NOT EXISTS `unique_employee_code` (`employee_code`);

-- Make sure auto increment is set correctly
ALTER TABLE `employees` 
MODIFY COLUMN `emp_id` int(11) NOT NULL AUTO_INCREMENT;

-- Clean up any duplicate employee codes (if any exist)
-- This is a safety measure - run with caution in production
-- DELETE e1 FROM employees e1 
-- INNER JOIN employees e2 
-- WHERE e1.emp_id > e2.emp_id 
-- AND e1.employee_code = e2.employee_code;

-- Optional: Remove columns that are now handled by separate tables
-- Uncomment these only after you've migrated data to the new tables
-- and you're sure you don't need these columns anymore

-- Remove qualification-related columns (now in employee_qualifications table)
-- ALTER TABLE `employees` DROP COLUMN IF EXISTS `highest_qualification`;
-- ALTER TABLE `employees` DROP COLUMN IF EXISTS `university`;
-- ALTER TABLE `employees` DROP COLUMN IF EXISTS `passing_year`;

-- Remove experience-related columns (now in employee_experience table)
-- ALTER TABLE `employees` DROP COLUMN IF EXISTS `previous_company`;
-- ALTER TABLE `employees` DROP COLUMN IF EXISTS `previous_designation`;
-- ALTER TABLE `employees` DROP COLUMN IF EXISTS `previous_experience_years`;

-- Remove documents column (now in employee_documents table)
-- ALTER TABLE `employees` DROP COLUMN IF EXISTS `documents`;

-- Verify the table structure
DESCRIBE `employees`;

-- Check for any data issues
SELECT 
    COUNT(*) as total_employees,
    COUNT(CASE WHEN employee_code IS NULL OR employee_code = '' THEN 1 END) as missing_employee_code,
    COUNT(CASE WHEN employee_name IS NULL OR employee_name = '' THEN 1 END) as missing_employee_name,
    COUNT(CASE WHEN status IS NULL THEN 1 END) as missing_status,
    COUNT(CASE WHEN status = 'A' THEN 1 END) as active_employees,
    COUNT(CASE WHEN status = 'I' THEN 1 END) as inactive_employees
FROM `employees`;

-- Show any duplicate employee codes
SELECT employee_code, COUNT(*) as count 
FROM `employees` 
GROUP BY employee_code 
HAVING COUNT(*) > 1;
