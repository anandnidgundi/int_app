# SQL Repair Guide for Employees Table

## Overview
This guide will help you repair and update the `employees` table to match the current NewUserModel structure.

## Prerequisites
- Backup your database before running these commands
- Access to MySQL/phpMyAdmin
- Database: `vdcapp2_internal_hrms`

## Step-by-Step Execution

### Step 1: Check Current Table Structure
```sql
USE vdcapp2_internal_hrms;
DESCRIBE employees;
```

### Step 2: Add Missing Columns (Execute one by one)

#### Basic Information Fields:
```sql
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
```

#### Financial & Compliance Fields:
```sql
ALTER TABLE employees ADD COLUMN aadhar_number varchar(20) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN pan_number varchar(20) DEFAULT NULL;
ALTER TABLE employees ADD COLUMN tds_status varchar(50) DEFAULT NULL;
```

#### Agreement & Contract Fields:
```sql
ALTER TABLE employees ADD COLUMN agreement_valid_date date DEFAULT NULL;
ALTER TABLE employees ADD COLUMN agreement_end_date date DEFAULT NULL;
ALTER TABLE employees ADD COLUMN contract_fee_revision_amount decimal(12,2) DEFAULT NULL;
```

#### Timestamp Fields:
```sql
ALTER TABLE employees ADD COLUMN created_at timestamp NULL DEFAULT current_timestamp();
ALTER TABLE employees ADD COLUMN updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();
```

### Step 3: Update Existing Data Mappings
```sql
UPDATE employees SET date_of_birth = dob WHERE date_of_birth IS NULL AND dob IS NOT NULL;
UPDATE employees SET father_or_husband_name = father_husband_name WHERE father_or_husband_name IS NULL AND father_husband_name IS NOT NULL;
UPDATE employees SET agreement_valid_date = latest_agreement_valid_date WHERE agreement_valid_date IS NULL AND latest_agreement_valid_date IS NOT NULL;
UPDATE employees SET agreement_end_date = latest_agreement_end_date WHERE agreement_end_date IS NULL AND latest_agreement_end_date IS NOT NULL;
UPDATE employees SET contract_fee_revision_amount = latest_contract_fee_revision_amount WHERE contract_fee_revision_amount IS NULL AND latest_contract_fee_revision_amount IS NOT NULL;
UPDATE employees SET mail = email WHERE mail IS NULL AND email IS NOT NULL;
```

### Step 4: Set Default Values
```sql
UPDATE employees SET status = 'A' WHERE status IS NULL OR status = '';
UPDATE employees SET resignation = 'No' WHERE resignation IS NULL OR resignation = '';
```

### Step 5: Create Performance Indexes
```sql
CREATE INDEX idx_employee_code ON employees (employee_code);
CREATE INDEX idx_employee_name ON employees (employee_name);
CREATE INDEX idx_department ON employees (department);
CREATE INDEX idx_status ON employees (status);
CREATE INDEX idx_resignation ON employees (resignation);
CREATE INDEX idx_reporting_manager ON employees (reporting_manager_empcode);
CREATE INDEX idx_joining_date ON employees (joining_date);
CREATE INDEX idx_email ON employees (email);
CREATE INDEX idx_mobile ON employees (mobile);
```

### Step 6: Ensure Unique Constraint (if needed)
```sql
-- Only run this if employee_code doesn't have unique constraint
ALTER TABLE employees ADD UNIQUE KEY unique_employee_code (employee_code);
```

### Step 7: Verification Queries

#### Check Table Structure:
```sql
DESCRIBE employees;
```

#### Check Data Integrity:
```sql
SELECT 
    COUNT(*) as total_employees,
    COUNT(CASE WHEN employee_code IS NULL OR employee_code = '' THEN 1 END) as missing_employee_code,
    COUNT(CASE WHEN employee_name IS NULL OR employee_name = '' THEN 1 END) as missing_employee_name,
    COUNT(CASE WHEN status = 'A' THEN 1 END) as active_employees,
    COUNT(CASE WHEN status = 'I' THEN 1 END) as inactive_employees
FROM employees;
```

#### Check for Duplicate Employee Codes:
```sql
SELECT employee_code, COUNT(*) as count 
FROM employees 
GROUP BY employee_code 
HAVING COUNT(*) > 1;
```

## Expected Results

After running all commands, your `employees` table should have these additional columns:
- `company`
- `pay_group` 
- `city_name`
- `cluster`
- `location_name`
- `locationName`
- `total_experience`
- `mail`
- `date_of_birth`
- `father_or_husband_name`
- `aadhar_number`
- `pan_number`
- `tds_status`
- `agreement_valid_date`
- `agreement_end_date`
- `contract_fee_revision_amount`
- `created_at`
- `updated_at`

## Error Handling

If you get "Column already exists" errors:
- Skip that particular ALTER TABLE command
- Continue with the next command

If you get "Duplicate key" errors on indexes:
- The index already exists, skip it

If you get foreign key constraint errors:
- Make sure related tables (employee_qualifications, employee_experience, employee_documents) exist

## Notes

1. **Backup First**: Always backup your database before making structural changes
2. **Test Environment**: Run these commands in a test environment first
3. **Monitor Performance**: After adding indexes, monitor query performance
4. **Data Migration**: Consider migrating qualification/experience data to separate tables
5. **Cleanup**: After successful migration, you may remove old columns that are now in separate tables

## Related Tables Status

Make sure these tables exist and have proper structure:
- `employee_qualifications` - for education data
- `employee_experience` - for work experience data  
- `employee_documents` - for file attachments
- `users` - for authentication data

The NewUserModel is now configured to work with this structure and handle the complete payload format you specified.
