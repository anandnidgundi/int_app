-- Safe SQL Script to Repair Employees Table
-- Execute these statements one by one in your MySQL/phpMyAdmin
-- This version uses standard MySQL syntax without IF NOT EXISTS

-- Step 1: Add new columns (skip if column already exists)
-- Basic employee information fields
ALTER TABLE employees ADD COLUMN company varchar(100) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN pay_group varchar(50) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN city_name varchar(100) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN cluster varchar(100) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN location_name varchar(100) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN locationName varchar(100) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN total_experience decimal(4,1) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN mail varchar(100) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN date_of_birth date DEFAULT NULL;
ALTER TABLE employees ADD COLUMN father_or_husband_name varchar(100) DEFAULT NULL;

-- Financial and compliance fields
ALTER TABLE employees ADD COLUMN aadhar_number varchar(20) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN pan_number varchar(20) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN tds_status varchar(50) DEFAULT NULL;

-- Agreement and contract fields  
ALTER TABLE employees ADD COLUMN agreement_valid_date date DEFAULT NULL;
ALTER TABLE employees ADD COLUMN agreement_end_date date DEFAULT NULL;
ALTER TABLE employees ADD COLUMN contract_fee_revision_amount decimal(12,2) DEFAULT NULL;

-- Step 2: Ensure timestamps exist
ALTER TABLE employees ADD COLUMN created_at timestamp NULL DEFAULT current_timestamp();
ALTER TABLE employees ADD COLUMN updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

-- Step 3: Update existing data mappings
UPDATE employees SET date_of_birth = dob WHERE date_of_birth IS NULL AND dob IS NOT NULL;
UPDATE employees SET father_or_husband_name = father_husband_name WHERE father_or_husband_name IS NULL AND father_husband_name IS NOT NULL;
UPDATE employees SET agreement_valid_date = latest_agreement_valid_date WHERE agreement_valid_date IS NULL AND latest_agreement_valid_date IS NOT NULL;
UPDATE employees SET agreement_end_date = latest_agreement_end_date WHERE agreement_end_date IS NULL AND latest_agreement_end_date IS NOT NULL;
UPDATE employees SET contract_fee_revision_amount = latest_contract_fee_revision_amount WHERE contract_fee_revision_amount IS NULL AND latest_contract_fee_revision_amount IS NOT NULL;
UPDATE employees SET mail = email WHERE mail IS NULL AND email IS NOT NULL;

-- Step 4: Create indexes for performance
CREATE INDEX idx_employee_code ON employees (employee_code);
CREATE INDEX idx_employee_name ON employees (employee_name);
CREATE INDEX idx_department ON employees (department);
CREATE INDEX idx_status ON employees (status);
CREATE INDEX idx_resignation ON employees (resignation);
CREATE INDEX idx_reporting_manager ON employees (reporting_manager_empcode);
CREATE INDEX idx_joining_date ON employees (joining_date);
CREATE INDEX idx_email ON employees (email);
CREATE INDEX idx_mobile ON employees (mobile);

-- Step 5: Ensure proper constraints
-- Make sure employee_code is unique
-- ALTER TABLE employees ADD UNIQUE KEY unique_employee_code (employee_code);

-- Step 6: Set default values for critical fields
UPDATE employees SET status = 'A' WHERE status IS NULL OR status = '';
UPDATE employees SET resignation = 'No' WHERE resignation IS NULL OR resignation = '';

-- Step 7: Verification queries
-- Check table structure
DESCRIBE employees;

-- Check data integrity
SELECT 
    COUNT(*) as total_employees,
    COUNT(CASE WHEN employee_code IS NULL OR employee_code = '' THEN 1 END) as missing_employee_code,
    COUNT(CASE WHEN employee_name IS NULL OR employee_name = '' THEN 1 END) as missing_employee_name,
    COUNT(CASE WHEN status = 'A' THEN 1 END) as active_employees,
    COUNT(CASE WHEN status = 'I' THEN 1 END) as inactive_employees
FROM employees;

-- Check for duplicate employee codes
SELECT employee_code, COUNT(*) as count 
FROM employees 
GROUP BY employee_code 
HAVING COUNT(*) > 1;
