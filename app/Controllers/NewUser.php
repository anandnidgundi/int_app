<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\NewUserModel;
use App\Models\NewEmployeeMasterModel;
use CodeIgniter\API\ResponseTrait;
use App\Controllers\BaseController;



class NewUser extends BaseController
{
     use ResponseTrait;

     protected $newEmployeeMasterModel;

     public function __construct()
     {
          $this->newEmployeeMasterModel = new NewEmployeeMasterModel();
     }

     public function getEmployeesWithoutUsers()
     {
          try {
               $this->validateAuthorization();

               $newUserModel = new NewUserModel();
               $builder = $newUserModel->builder();

               $builder->select('employees.emp_id AS emp_id, employees.employee_code, employees.employee_name, employees.email, employees.mobile, employees.department, employees.status, employees.isDeleted')
                    ->join('users', 'users.user_code = employees.employee_code', 'left')
                    // exclude rows where a user exists for the employee code
                    ->where('users.user_code IS NULL', null, false)
                    // exclude empty employee_code values
                    ->where("TRIM(employees.employee_code) <> ''", null, false)
                    // exclude soft deleted employees
                    ->where('employees.isDeleted', 'N')
                    ->orderBy('employees.employee_code', 'ASC');

               $rows = $builder->get()->getResultArray();

               return $this->respond([
                    'status' => true,
                    'count' => count($rows),
                    'data' => $rows
               ], 200);
          } catch (\Exception $e) {
               log_message('error', 'Error fetching employees without users: ' . $e->getMessage());
               return $this->respond([
                    'status' => false,
                    'message' => 'Error fetching employees without users: ' . $e->getMessage()
               ], 500);
          }
     }

     public function createUsersForMissingEmployees()
     {
          try {
               $this->validateAuthorization();

               $newUserModel = new NewUserModel();
               $builder = $newUserModel->builder();

               $builder->select('employees.emp_id AS emp_id, employees.employee_code, employees.employee_name, employees.email, employees.mobile')
                    ->join('users', 'users.user_code = employees.employee_code', 'left')
                    ->where('users.user_code IS NULL', null, false)
                    ->where("TRIM(employees.employee_code) <> ''", null, false)
                    ->where('employees.isDeleted', 'N');

               $employees = $builder->get()->getResultArray();

               if (empty($employees)) {
                    return $this->respond([
                         'status' => true,
                         'message' => 'No employees found without users',
                         'created' => 0,
                         'skipped' => 0,
                         'errors' => []
                    ], 200);
               }

               $userModel = new UserModel();
               $created = 0;
               $skipped = 0;
               $errors = [];

               foreach ($employees as $emp) {
                    $empCode = trim((string) ($emp['employee_code'] ?? ''));
                    if ($empCode === '') {
                         $skipped++;
                         $errors[] = ['employee_code' => $empCode, 'error' => 'Empty employee_code'];
                         continue;
                    }

                    // Double-check user doesn't already exist (race-safe: will still catch DB error)
                    $existing = $userModel->where('user_code', $empCode)->first();
                    if ($existing) {
                         $skipped++;
                         continue;
                    }

                    $userData = [
                         'user_name'     => $emp['employee_name'] ?? $empCode,
                         'user_code'     => $empCode,
                         'emp_id'        => $emp['emp_id'] ?? null,
                         'password'      => md5('adnet2008'),
                         'status'        => 'A',
                         'disabled'      => 'N',
                         'validity'      => date('Y-m-d', strtotime('+90 days')),
                         'failed_attems' => 0,
                         'is_admin'      => 'N',
                         'exit_date'     => '0000-00-00',
                         'role'          => 'EMPLOYEE'
                    ];

                    try {
                         $insertId = $userModel->insert($userData);
                         if ($insertId === false) {
                              $dbErr = $userModel->db->error();
                              $errors[] = ['employee_code' => $empCode, 'error' => $dbErr['message'] ?? 'Unknown DB error'];
                              $skipped++;
                              continue;
                         }
                         $created++;
                    } catch (\Exception $e) {
                         // Possibly unique constraint or other DB error - record and continue
                         $errors[] = ['employee_code' => $empCode, 'error' => $e->getMessage()];
                         $skipped++;
                    }
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'User creation completed',
                    'created' => $created,
                    'skipped' => $skipped,
                    'errors' => $errors
               ], 200);
          } catch (\Exception $e) {
               log_message('error', 'Error creating users for employees: ' . $e->getMessage());
               return $this->respond([
                    'status' => false,
                    'message' => 'Error creating users: ' . $e->getMessage()
               ], 500);
          }
     }

     public function resetTravelMasterPasswords()
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['user_code'] ?? null;

               $payload = $this->request->getJSON(true);
               if (empty($payload)) {
                    $payload = $this->request->getPost() ?? [];
               }

               $all = !empty($payload['all']);
               $codes = [];

               if (!$all) {
                    if (!empty($payload['employee_code'])) {
                         $codes = [(string) $payload['employee_code']];
                    } elseif (!empty($payload['employee_codes']) && is_array($payload['employee_codes'])) {
                         $codes = $payload['employee_codes'];
                    } else {
                         return $this->respond([
                              'status' => false,
                              'message' => 'Provide employee_code, employee_codes or set all=true'
                         ], 400);
                    }
               }

               // Normalize codes
               $codes = array_values(array_unique(array_filter(array_map('trim', $codes))));
               // Convert numeric codes to int (master uses emp_code stored as int)
               $codes = array_map(function ($c) {
                    return is_numeric($c) ? (int) $c : $c;
               }, $codes);

               // Connect to travelapp DB and update new_emp_master
               $travelDB = \Config\Database::connect('travelapp');
               $builder = $travelDB->table('new_emp_master');

               $updateData = [
                    'password' => md5('Special#2'),
                    'modified_on' => date('Y-m-d H:i:s'),
                    'modified_by' => $user
               ];

               if ($all) {
                    $builder->set($updateData)->update();
               } else {
                    $builder->whereIn('emp_code', $codes)->set($updateData)->update();
               }

               $affected = $travelDB->affectedRows();

               return $this->respond([
                    'status' => true,
                    'updated' => $affected,
                    'message' => $affected > 0 ? 'Passwords reset successfully' : 'No matching records found'
               ], 200);
          } catch (\Exception $e) {
               log_message('error', 'Error resetting travel master passwords: ' . $e->getMessage());
               return $this->respond([
                    'status' => false,
                    'message' => 'Error resetting passwords: ' . $e->getMessage()
               ], 500);
          }
     }

     public function totalSessions()
     {
          $users = new NewUserModel();
          $totalSessions = $users->countAllSessions();
          return $this->respond(['totalSessions' => $totalSessions, 'STATUS' => true], 200);
     }

     public function record_HR_Data_for_Contratual()
     {
          try {
               $db = \Config\Database::connect();
               $contractualRows = $db->table('contractual_by_hr')->get()->getResultArray();

               if (empty($contractualRows)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'No contractual HR data found'
                    ], 404);
               }

               $newUserModel = new NewUserModel();
               $userModel = new UserModel();
               $created = 0;
               $skipped = 0;
               $errors = [];

               // Helper to convert date formats to Y-m-d
               $convertDate = function ($dateStr) {
                    if (empty($dateStr) || $dateStr == '-' || $dateStr == '0000-00-00') return null;
                    $dateStr = trim($dateStr);
                    $formats = [
                         'd-M-y',
                         'd-M-Y',
                         'd/M/y',
                         'd/M/Y',
                         'd/m/Y',
                         'd/m/y',
                         'Y-m-d',
                         'Y/m/d'
                    ];
                    foreach ($formats as $fmt) {
                         $dt = \DateTime::createFromFormat($fmt, $dateStr);
                         if ($dt && $dt->format('Y') > 1900) return $dt->format('Y-m-d');
                    }
                    $ts = strtotime($dateStr);
                    if ($ts && date('Y', $ts) > 1900) return date('Y-m-d', $ts);
                    return null;
               };

               foreach ($contractualRows as $row) {
                    // Gender mapping
                    $gender = ucfirst(strtolower(trim($row['GENDER'])));
                    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
                         $gender = 'Other';
                    }

                    // Marital status mapping
                    $maritalStatus = ucfirst(strtolower(trim($row['MARITALSTATUS'])));
                    if (!in_array($maritalStatus, ['Single', 'Married', 'Divorced', 'Widowed'])) {
                         $maritalStatus = 'Single';
                    }

                    // Active_Inactive and status mapping
                    $activeInactive = (strtoupper(trim($row['EMPLOYEE_STATUS'])) === 'ACTIVE') ? 'Active' : 'Inactive';
                    $status = (strtoupper(trim($row['EMPLOYEE_STATUS'])) === 'ACTIVE') ? 'A' : 'I';

                    $employeeData = [
                         'employee_code' => $row['EMP_STAFFID'],
                         'employee_name' => $row['EMPLOYEE_NAME'],
                         'emp_type' => 'CONTRACTUAL_EMPLOYEE',
                         'designation' => $row['DESIGNATION_NAME'],
                         'department' => $row['MAIN_DEPARTMENT'],
                         'joining_date' => $convertDate($row['EMP_DATEOFJOINING']),
                         'employment_type' => $row['EMPLOYMENT_STATUS'],
                         'company' => $row['COMPANY'],
                         'pay_group' => $row['PAYGROUP'],
                         'city_name' => $row['CITY_NAME'],
                         'cluster' => $row['CLUSTER'],
                         'location_name' => $row['LOCATION_NAME'],
                         'department_category' => $row['DEPARTMENT_CATEGORY'],
                         'main_department' => $row['MAIN_DEPARTMENT'],
                         'sub_department' => $row['SUB_DEPARTMENT'],
                         'designation_name' => $row['DESIGNATION_NAME'],
                         'grade_name' => $row['GRADE_NAME'],
                         'position' => $row['POSITION'],
                         'reporting_manager_name' => $row['REPORTING_MANAGER_NAME'],
                         'reporting_manager_empcode' => $row['MANAGER_EMPCODE'],
                         'functional_manager_name' => $row['FUNCTIONAL_MANAGET_NAME'],
                         'skip_level_manager_empcode' => $row['SKIP-LEVEL_MANAGER_EMPCODE'],
                         'shift_description' => $row['SHIFT_DESCRIPTION'],
                         'shift_description_1' => $row['SHIFT_DESCRIPTION_1'],
                         'shift_description_2' => $row['SHIFT_DESCRIPTION_2'],
                         'week_off' => '', // Not available
                         'mobile' => $row['MOBILE'],
                         'email' => $row['MAIL'],
                         'dob' => $convertDate($row['DOB']),
                         'gender' => $gender,
                         'father_husband_name' => $row['FATHER_HUSBAND_NAME'],
                         'marital_status' => $maritalStatus,
                         'blood_group' => $row['BLOOD_GROUP'],
                         'religion' => $row['RELIGION'],
                         'caste' => $row['CASTE'],
                         'ctc' => is_numeric(trim($row['CTC'])) ? trim($row['CTC']) : null,
                         'aadhar_number' => $row['PER_ADHAR_NO'],
                         'pan_number' => $row['PER_PAN'],
                         'notice_period' => $row['EMP_NOTICE_PERIOD'],
                         'resignation' => strtolower(trim($row['RESIGNATION'])) === 'yes' ? 'Yes' : 'No',
                         'resignation_date' => $convertDate($row['RESIGNATION_DATE']),
                         'relieving_date' => $convertDate($row['RELIEVING_DATE']),
                         'last_working_date' => $convertDate($row['LAST_WORKING_DATE']),
                         'last_pay_date' => $convertDate($row['LAST_PAY_DATE']),
                         'separation_status' => $row['SEPARATION_STATUS'],
                         'bank_account_name' => '', // Not available
                         'bank_account_number' => '', // Not available
                         'ifsc_code' => '', // Not available
                         'total_experience' => is_numeric($row['Total_Years_of_Experience']) ? $row['Total_Years_of_Experience'] : null,
                         'latest_agreement_valid_date' => $convertDate($row['Agreement_Start_date']),
                         'latest_agreement_end_date' => $convertDate($row['Agreement_end_date']),
                         'isLeaveApplicable' => (strtolower(trim($row['Agreement_Aknowledgement'])) === 'yes') ? 'Y' : 'N',
                         'Active_Inactive' => $activeInactive,
                         'status' => $status,
                         'isDeleted' => 'N',
                         'address' => isset($row['Address']) ? $row['Address'] : null
                    ];

                    // Sanitize and filter
                    $this->sanitizeEmployeePayload($employeeData);
                    $basicEmployeeData = $this->filterBasicEmployeeData($employeeData);

                    // Check for duplicate
                    $existing = $newUserModel->where('employee_code', $employeeData['employee_code'])->first();
                    if ($existing) {
                         $skipped++;
                         continue;
                    }

                    try {
                         $newUserModel->insert($basicEmployeeData);
                         $created++;

                         $empId = $newUserModel->getInsertID();

                         // Insert qualification if DEGREE_NAME exists
                         if (!empty($row['DEGREE_NAME'])) {
                              $qualificationData = [
                                   'emp_id' => $empId,
                                   'qualification' => $row['DEGREE_NAME'],
                                   'status' => 'A'
                              ];
                              $db->table('employee_qualifications')->insert($qualificationData);
                         }

                         // Insert user record in users table
                         $user_name = $employeeData['employee_name'] ?? '';
                         $emp_code = $employeeData['employee_code'] ?? '';
                         $user_data = [
                              'user_name'     => $user_name,
                              'user_code'     => $emp_code,
                              'emp_id'        => $empId,
                              'password'      => md5('Special#2'),
                              'status'        => 'A',
                              'disabled'      => 'N',
                              'validity'      => date('Y-m-d', strtotime('+90 days')),
                              'failed_attems' => 0,
                              'is_admin'      => 'N',
                              'exit_date'     => '0000-00-00',
                              'role'          => 'EMPLOYEE',
                         ];
                         $userModel->insert($user_data);
                    } catch (\Exception $e) {
                         $errors[] = [
                              'employee_code' => $employeeData['employee_code'],
                              'error' => $e->getMessage()
                         ];
                         $skipped++;
                    }
               }

               return $this->respond([
                    'status' => true,
                    'created' => $created,
                    'skipped' => $skipped,
                    'errors' => $errors
               ]);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error importing contractual HR data: ' . $e->getMessage()
               ], 500);
          }
     }

     /**
      * Sanitize employee payload: convert empty strings to null for date fields and cast numerics
      */
     private function sanitizeEmployeePayload(&$employeeData)
     {
          // List of date fields in employees table
          $dateFields = [
               'joining_date',
               'dob',
               'latest_agreement_valid_date',
               'latest_agreement_end_date',
               'resignation_date',
               'relieving_date',
               'last_working_date',
               'last_pay_date'
          ];
          foreach ($dateFields as $field) {
               if (isset($employeeData[$field]) && $employeeData[$field] === '') {
                    $employeeData[$field] = null;
               }
          }
          // Numeric fields
          $numericFields = [
               'total_experience',
               'ctc',
               'latest_contract_fee_revision_amount',
               'notice_period'
          ];
          foreach ($numericFields as $field) {
               if (isset($employeeData[$field]) && $employeeData[$field] !== '' && $employeeData[$field] !== null) {
                    $employeeData[$field] = is_numeric($employeeData[$field]) ? $employeeData[$field] + 0 : null;
               }
          }
     }

     /**
      * Filter only basic employee fields for the main employees table
      */
     private function filterBasicEmployeeData($employeeData)
     {
          // Define fields that belong to the main employees table
          $basicFields = [
               'employee_code',
               'emp_type',
               'employee_name',
               'designation',
               'department',
               'joining_date',
               'employment_type',
               'mobile',
               'email',
               'department_category',
               'main_department',
               'sub_department',
               'designation_name',
               'grade_name',
               'position',
               'reporting_manager_name',
               'reporting_manager_empcode',
               'functional_manager_name',
               'skip_level_manager_empcode',
               'shift_description',
               'shift_description_1',
               'shift_description_2',
               'week_off',
               'company',
               'pay_group',
               'religion',
               'city_name',
               'cluster',
               'location_name',
               'locationName',
               'total_experience',
               'mail',
               'dob',
               'gender',
               'fatherHusbandName',
               'maritalStatus',
               'bloodGroup',
               'caste',
               'father_husband_name',
               'marital_status',
               'blood_group',
               'ctc',
               'agreement_valid_date',
               'agreement_end_date',
               'contract_fee_revision_amount',
               'latest_agreement_valid_date',
               'latest_agreement_end_date',
               'latest_contract_fee_revision_amount',
               'aadhar_number',
               'pan_number',
               'tds_status',
               'resignation',
               'resignation_date',
               'relieving_date',
               'last_working_date',
               'last_pay_date',
               'separation_status',
               'notice_period',
               'bank_account_name',
               'bank_account_number',
               'ifsc_code',
               'isLeaveApplicable',
               'address',
               'emergency_contact',
               'split_shift',
               'resignation_reason',
          ];

          $filteredData = [];
          foreach ($basicFields as $field) {
               if (isset($employeeData[$field])) {
                    $filteredData[$field] = $employeeData[$field];
               }
          }

          // Set default values
          $filteredData['status'] = 'A';
          $filteredData['created_at'] = date('Y-m-d H:i:s');
          $filteredData['updated_at'] = date('Y-m-d H:i:s');
          // Set default for isLeaveApplicable if not provided
          if (!isset($filteredData['isLeaveApplicable'])) {
               $filteredData['isLeaveApplicable'] = 'N';
          }
          return $filteredData;
     }

     public function findMissingEmployeesCodes()
     {
          try {
               $defaultDB = \Config\Database::connect();
               // Get distinct, trimmed employee_code values from employees
               $employeeRows = $defaultDB->table('employees')
                    ->select('DISTINCT TRIM(employee_code) as employee_code')
                    ->where('employee_code IS NOT NULL')
                    ->where("TRIM(employee_code) <> ''")
                    ->where('status', 'A')
                    ->where('Active_Inactive', 'Active')
                    ->get()
                    ->getResultArray();

               if (empty($employeeRows)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'No employees found in employees table'
                    ], 404);
               }

               $employeeCodes = array_values(array_unique(array_map(function ($r) {
                    return (string) $r['employee_code'];
               }, $employeeRows)));

               // Fetch all emp_code values from travelapp.new_emp_master and normalize
               $travelDB = \Config\Database::connect('travelapp');
               $masterRows = $travelDB->table('new_emp_master')->select('emp_code')->get()->getResultArray();

               $masterSet = [];
               foreach ($masterRows as $r) {
                    $k = trim((string) ($r['emp_code'] ?? ''));
                    if ($k !== '') {
                         $masterSet[$k] = true;
                    }
               }

               $missingCodes = [];
               foreach ($employeeCodes as $code) {
                    $k = trim((string) $code);
                    if ($k === '') continue;
                    if (!isset($masterSet[$k])) {
                         $missingCodes[] = $k;
                    }
               }

               sort($missingCodes, SORT_NATURAL | SORT_FLAG_CASE);

               return $this->respond([
                    'status' => true,
                    'missing_employee_codes' => $missingCodes,
                    'count' => count($missingCodes)
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error finding missing employee codes: ' . $e->getMessage()
               ], 500);
          }
     }

     public function createNewEmployee()
     {
          try {
               // Fetch validateAuthorization
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['user_code'];
               $role = $userDetails['role'];

               // Validate user exists
               $userModel = new UserModel();
               $currentUser = $userModel->where('user_code', $user)->first();
               if (!$currentUser) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'User not found'
                    ], 404);
               }

               // Determine how to get the data based on Content-Type
               $contentType = $this->request->getHeaderLine('Content-Type');
               $employeeData = [];

               if (strpos($contentType, 'multipart/form-data') !== false) {
                    // Handle form-data (from frontend with files)
                    $postData = $this->request->getPost();

                    if (isset($postData['data']) && !empty($postData['data'])) {
                         $employeeData = json_decode($postData['data'], true);
                         if (json_last_error() !== JSON_ERROR_NONE) {
                              return $this->respond([
                                   'status' => false,
                                   'message' => 'Invalid JSON in data field: ' . json_last_error_msg()
                              ], 400);
                         }
                    } else {
                         $employeeData = $postData;
                    }
               } else {
                    // Handle JSON data (application/json)
                    $requestData = $this->request->getJSON(true);
                    if (!empty($requestData)) {
                         $employeeData = $requestData;
                    }
               }

               if (empty($employeeData)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'No data provided'
                    ], 400);
               }


               // Sanitize payload (convert empty strings to null for dates, cast numerics)
               $this->sanitizeEmployeePayload($employeeData);

               // Debug log
               log_message('debug', 'Employee data received (sanitized): ' . json_encode($employeeData));

               // Handle uploaded files
               $uploadedFiles = $this->request->getFiles();
               $files = [];
               $documentNames = $this->request->getPost('document_names') ?? [];

               // Debug: Log what files were received
               //   log_message('error', '[FILE_UPLOAD] Content-Type: ' . $contentType);
               //   log_message('error', '[FILE_UPLOAD] Raw uploaded files: ' . json_encode($uploadedFiles));
               //   log_message('error', '[FILE_UPLOAD] Uploaded files keys: ' . json_encode(array_keys($uploadedFiles)));
               //   log_message('error', '[FILE_UPLOAD] Document names received: ' . json_encode($documentNames));

               // Additional debug for multipart data
               if (strpos($contentType, 'multipart/form-data') !== false) {
                    $postData = $this->request->getPost();
                    log_message('error', '[FILE_UPLOAD] POST data keys: ' . json_encode(array_keys($postData)));
               }

               if (!empty($uploadedFiles)) {
                    log_message('error', '[FILE_UPLOAD] Total uploaded file fields: ' . count($uploadedFiles));

                    foreach ($uploadedFiles as $fieldName => $file) {
                         log_message('error', '[FILE_UPLOAD] Processing field: ' . $fieldName . ' - Is array: ' . (is_array($file) ? 'YES (' . count($file) . ' files)' : 'NO (single file)'));

                         // Additional debug for file structure
                         if (is_array($file)) {
                              log_message('error', '[FILE_UPLOAD] Array field "' . $fieldName . '" contains ' . count($file) . ' files');
                              foreach ($file as $index => $singleFile) {
                                   log_message('error', '[FILE_UPLOAD] File [' . $index . ']: ' . $singleFile->getClientName() . ' (valid: ' . ($singleFile->isValid() ? 'YES' : 'NO') . ')');
                              }
                         } else {
                              log_message('error', '[FILE_UPLOAD] Single file "' . $fieldName . '": ' . $file->getClientName() . ' (valid: ' . ($file->isValid() ? 'YES' : 'NO') . ')');
                         }
                         if ($fieldName === 'files') {
                              // Handle multiple files uploaded as 'files'
                              if (is_array($file)) {
                                   log_message('error', '[FILE_UPLOAD] Processing files array with ' . count($file) . ' files');
                                   foreach ($file as $index => $singleFile) {
                                        log_message('error', '[FILE_UPLOAD] Processing file index ' . $index . ' - Valid: ' . ($singleFile->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($singleFile->hasMoved() ? 'YES' : 'NO'));
                                        if ($singleFile->isValid() && !$singleFile->hasMoved()) {
                                             $processedFile = $this->processFile($singleFile, $fieldName);
                                             // Use document name from frontend if available, otherwise use original filename
                                             if (isset($documentNames[$index]) && !empty($documentNames[$index])) {
                                                  $processedFile['document_name'] = $documentNames[$index];
                                             }
                                             $files[] = $processedFile;
                                             log_message('error', 'Processed file: ' . $singleFile->getClientName() . ' as ' . $processedFile['original_name']);
                                        }
                                   }
                              } else {
                                   // Single file uploaded as 'files'
                                   log_message('error', '[FILE_UPLOAD] Processing single file - Valid: ' . ($file->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($file->hasMoved() ? 'YES' : 'NO'));
                                   if ($file->isValid() && !$file->hasMoved()) {
                                        $processedFile = $this->processFile($file, $fieldName);
                                        if (isset($documentNames[0]) && !empty($documentNames[0])) {
                                             $processedFile['document_name'] = $documentNames[0];
                                        }
                                        $files[] = $processedFile;
                                        log_message('error', 'Processed single file: ' . $file->getClientName() . ' as ' . $processedFile['original_name']);
                                   }
                              }
                         } elseif (preg_match('/^files\[(\d+)\]$/', $fieldName, $matches)) {
                              // Handle indexed files like files[0], files[1], etc. (from frontend)
                              $fileIndex = (int)$matches[1];
                              log_message('error', '[FILE_UPLOAD] Processing indexed file: ' . $fieldName . ' (index: ' . $fileIndex . ') - Valid: ' . ($file->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($file->hasMoved() ? 'YES' : 'NO'));

                              if ($file->isValid() && !$file->hasMoved()) {
                                   $processedFile = $this->processFile($file, 'files');

                                   // Use document name from frontend if available
                                   if (isset($documentNames[$fileIndex]) && !empty($documentNames[$fileIndex])) {
                                        $processedFile['document_name'] = $documentNames[$fileIndex];
                                   }

                                   $files[] = $processedFile;
                                   log_message('error', 'Processed indexed file: ' . $file->getClientName() . ' as ' . $processedFile['original_name'] . ' with document name: ' . ($processedFile['document_name'] ?? 'N/A'));
                              }
                         } else {
                              // Handle other file fields (individual file inputs)
                              if (is_array($file)) {
                                   log_message('debug', '[FILE_UPLOAD] Processing other field array with ' . count($file) . ' files');
                                   foreach ($file as $index => $singleFile) {
                                        if ($singleFile->isValid() && !$singleFile->hasMoved()) {
                                             $processedFile = $this->processFile($singleFile, $fieldName);
                                             $processedFile['document_name'] = $fieldName; // Use field name as document name
                                             $files[] = $processedFile;
                                             log_message('debug', 'Processed array file: ' . $singleFile->getClientName());
                                        }
                                   }
                              } else {
                                   log_message('debug', '[FILE_UPLOAD] Processing single other field - Valid: ' . ($file->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($file->hasMoved() ? 'YES' : 'NO'));
                                   if ($file->isValid() && !$file->hasMoved()) {
                                        $processedFile = $this->processFile($file, $fieldName);
                                        $processedFile['document_name'] = $fieldName; // Use field name as document name
                                        $files[] = $processedFile;
                                        log_message('debug', 'Processed single field file: ' . $file->getClientName());
                                   }
                              }
                         }
                    }
               }

               log_message('error', 'Total files processed: ' . count($files));

               // Validate required fields
               $requiredFields = ['employee_code', 'employee_name'];
               foreach ($requiredFields as $field) {
                    if (empty($employeeData[$field])) {
                         return $this->respond([
                              'status' => false,
                              'message' => "Required field '{$field}' is missing",
                              'available_fields' => array_keys($employeeData)
                         ], 400);
                    }
               }

               // Check if employee code already exists
               $newUserModel = new NewUserModel();
               $existingEmployee = $newUserModel->where('employee_code', $employeeData['employee_code'])->first();
               if ($existingEmployee) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Employee code already exists'
                    ], 409);
               }

               // Start database transaction
               $db = \Config\Database::connect();
               $db->transStart();

               try {
                    // Create basic employee record
                    $basicEmployeeData = $this->filterBasicEmployeeData($employeeData);

                    try {
                         $empId = $newUserModel->insert($basicEmployeeData, true);
                    } catch (\Throwable $e) {
                         // Log and return detailed DB/model errors for debugging
                         $validationErrors = $newUserModel->errors();
                         $dbError = $newUserModel->db->error();
                         log_message('error', 'Create employee validation errors: ' . json_encode($validationErrors));
                         log_message('error', 'Create employee DB error: ' . json_encode($dbError));
                         log_message('error', 'Create employee exception: ' . $e->getMessage());

                         throw new \Exception('Failed to create basic employee record: ' . ($dbError['message'] ?? $e->getMessage()));
                    }

                    if (!$empId) {
                         $validationErrors = $newUserModel->errors();
                         $dbError = $newUserModel->db->error();
                         log_message('error', 'Create employee failed (no id). Validation: ' . json_encode($validationErrors) . ' DB: ' . json_encode($dbError));
                         throw new \Exception('Failed to create basic employee record: ' . ($dbError['message'] ?? 'unknown'));
                    }

                    log_message('debug', 'Employee created with ID: ' . $empId);

                    // Handle educations if present
                    if (isset($employeeData['educations']) && is_array($employeeData['educations'])) {
                         $this->handleEmployeeEducations($empId, $employeeData['educations']);
                         log_message('debug', 'Educations processed: ' . count($employeeData['educations']));
                    }

                    // Handle experiences if present  
                    if (isset($employeeData['experiences']) && is_array($employeeData['experiences'])) {
                         $this->handleEmployeeExperiences($empId, $employeeData['experiences']);
                         log_message('debug', 'Experiences processed: ' . count($employeeData['experiences']));
                    }

                    // Handle documents/files if present
                    if (!empty($files)) {
                         // Use processed uploaded files (real file uploads)
                         $this->handleEmployeeDocuments($empId, $files);
                         log_message('debug', 'Real files processed: ' . count($files));
                    } elseif (isset($employeeData['documents']) && is_array($employeeData['documents'])) {
                         // Use documents from JSON data (for testing or when files are pre-processed)
                         $this->handleEmployeeDocuments($empId, $employeeData['documents']);
                         log_message('debug', 'JSON documents processed: ' . count($employeeData['documents']));
                    }

                    // Additional logging for debugging
                    log_message('debug', 'Final summary - Files uploaded: ' . count($files) . ', JSON docs: ' . (isset($employeeData['documents']) ? count($employeeData['documents']) : 0));

                    $db->transComplete();

                    if ($db->transStatus() === false) {
                         throw new \Exception('Transaction failed');
                    }

                    // Get the created employee data
                    $createdEmployee = $newUserModel->find($empId);

                    // Insert user record in users table
                    $userModel = new UserModel();
                    $user_name = $employeeData['employee_name'] ?? '';
                    $emp_code = $employeeData['employee_code'] ?? '';
                    $user_data = [
                         'user_name'     => $user_name,
                         'user_code'     => $emp_code,
                         'emp_id'        => $empId,
                         'password'      => md5('adnet2008'),
                         'status'        => 'A',
                         'disabled'      => 'N',
                         'validity'      => date('Y-m-d', strtotime('+90 days')),
                         'failed_attems' => 0,
                         'is_admin'      => 'N',
                         'exit_date'     => '0000-00-00',
                         'role'          => 'EMPLOYEE',
                    ];

                    $userModel->insert($user_data);
                    // data to insert in Travalapp 

                    $prefixes = ['DR.', 'DR', 'MR.', 'MR', 'MRS.', 'MRS', 'MS.', 'MS', 'MISS', 'SHRI', 'SMT', 'PROF.', 'PROF'];

                    // Prepare name for splitting
                    $employeeName = trim($employeeData['employee_name'] ?? '');

                    // Remove prefix if present (case-insensitive)
                    $nameParts = preg_split('/\s+/', $employeeName);
                    if (!empty($nameParts) && in_array(strtoupper(rtrim($nameParts[0], '.')), array_map('strtoupper', $prefixes))) {
                         array_shift($nameParts); // Remove the prefix
                    }
                    // Now extract names
                    $fname = $nameParts[0] ?? ($basicEmployeeData['employee_name'] ?? null);
                    $lname = count($nameParts) > 1 ? array_pop($nameParts) : null;
                    $mname = count($nameParts) > 0 ? implode(' ', array_slice($nameParts, 1, -1)) : null;
                    $masterData = [
                         'emp_code'         => isset($basicEmployeeData['employee_code']) ? (int)$basicEmployeeData['employee_code'] : null,
                         'fname'            => $fname,
                         'mname'            => $mname,
                         'lname'            => $lname,
                         'comp_name'        => $basicEmployeeData['company'] ?? null,
                         'doj'              => !empty($basicEmployeeData['joining_date']) ? date('Y-m-d', strtotime($basicEmployeeData['joining_date'])) : null,
                         'dob'              => !empty($basicEmployeeData['dob']) ? date('Y-m-d', strtotime($basicEmployeeData['dob'])) : null,
                         'gender'           => $basicEmployeeData['gender'] ?? ($employeeData['gender'] ?? null),
                         'mail_id'          => $basicEmployeeData['mail'] ?? $basicEmployeeData['email'] ?? null,
                         'report_mngr'      => $basicEmployeeData['reporting_manager_empcode'] ?? $basicEmployeeData['reporting_manager_name'] ?? null,
                         'function_mngr'    => $basicEmployeeData['functional_manager_name'] ?? null,
                         'ou_name'          => $basicEmployeeData['main_department'] ?? null,
                         'dept_name'        => $basicEmployeeData['department'] ?? null,
                         'location_name'    => $basicEmployeeData['location_name'] ?? $basicEmployeeData['locationName'] ?? null,
                         'designation_name' => $basicEmployeeData['designation_name'] ?? $basicEmployeeData['designation'] ?? null,
                         'grade'            => $basicEmployeeData['grade_name'] ?? null,
                         'region'           => $basicEmployeeData['region'] ?? null,
                         'country'          => $basicEmployeeData['country'] ?? null,
                         'city'             => $basicEmployeeData['city_name'] ?? null,
                         'position'         => $basicEmployeeData['position'] ?? null,
                         'cost_center'      => $basicEmployeeData['cost_center'] ?? null,
                         'pay_group'        => $basicEmployeeData['pay_group'] ?? null,
                         'emp_status'       => $basicEmployeeData['status'] ?? 'A',
                         'active'           => $basicEmployeeData['active'] ?? 'Active',
                         'disabled'         => $basicEmployeeData['disabled'] ?? 'N',
                         'effective_from'   => !empty($basicEmployeeData['effective_from']) ? date('Y-m-d', strtotime($basicEmployeeData['effective_from'])) : null,
                         'created_on'       => date('Y-m-d H:i:s'),
                         'created_by'       => $user ?? null, // creator user_code from validateAuthorization()
                         'modified_on'      => null,
                         'modified_by'      => null,
                         'mobile'           => $basicEmployeeData['mobile'] ?? $employeeData['mobile'] ?? null,
                         'depend1'          => $basicEmployeeData['depend1'] ?? null,
                         'depend2'          => $basicEmployeeData['depend2'] ?? null,
                         'depend3'          => $basicEmployeeData['depend3'] ?? null,
                         'depend4'          => $basicEmployeeData['depend4'] ?? null,
                         'depend5'          => $basicEmployeeData['depend5'] ?? null,
                         'depend6'          => $basicEmployeeData['depend6'] ?? null,
                         'exit_date'        => !empty($basicEmployeeData['exit_date']) ? date('Y-m-d', strtotime($basicEmployeeData['exit_date'])) : null,
                         'validity'         => date('Y-m-d', strtotime('+90 days')),
                         'is_admin'         => $basicEmployeeData['is_admin'] ?? 'N',
                         'is_it_admin'      => $basicEmployeeData['is_it_admin'] ?? 'N',
                         'vdcapp_admin'     => $basicEmployeeData['vdcapp_admin'] ?? 'N',
                         'vdcapp_super_admin' => $basicEmployeeData['vdcapp_super_admin'] ?? 'N',
                         'driver_access_given' => $basicEmployeeData['driver_access_given'] ?? 'N',
                         'bank_name'        => $basicEmployeeData['bank_account_name'] ?? null,
                         'bank_acnum'       => $basicEmployeeData['bank_account_number'] ?? null,
                         'ifsc_code'        => $basicEmployeeData['ifsc_code'] ?? null,
                         'password'         => md5('Special#2'),
                         'failed_attempts'  => 0,
                         'session_token'    => null,
                         'session_admin_token' => null,
                         'check_list'       => $basicEmployeeData['check_list'] ?? 'N',
                         'reminder'         => $basicEmployeeData['reminder'] ?? 'N',
                         'is_radiology_doctor' => $basicEmployeeData['is_radiology_doctor'] ?? 'N', // will be updated from profile API if needed
                         'isPETCTadmin'     => $basicEmployeeData['isPETCTadmin'] ?? 'N',           // will be updated from profile API if needed
                    ];

                    $this->newEmployeeMasterModel->insert($masterData);

                    $insertResult = $this->newEmployeeMasterModel->insert($masterData);

                    if (!$insertResult) {
                         $dbError = $this->newEmployeeMasterModel->db->error();
                         $validationErrors = $this->newEmployeeMasterModel->errors();
                         log_message('error', 'new_emp_master insert failed. data: ' . json_encode($masterData));
                         log_message('error', 'new_emp_master validation errors: ' . json_encode($validationErrors));
                         log_message('error', 'new_emp_master DB error: ' . json_encode($dbError));
                         throw new \Exception('Failed to insert into new_emp_master: ' . ($dbError['message'] ?? 'unknown'));
                    }

                    $this->logActivity('Employee Created', NULL, $masterData);
                    return $this->respond([
                         'status' => true,
                         'message' => 'Employee created successfully',
                         'employee_id' => $empId,
                         'employee_code' => $employeeData['employee_code'],
                         'data' => $createdEmployee,
                         'educations_count' => isset($employeeData['educations']) ? count($employeeData['educations']) : 0,
                         'experiences_count' => isset($employeeData['experiences']) ? count($employeeData['experiences']) : 0,
                         'files_uploaded' => count($files),
                         'json_documents' => isset($employeeData['documents']) ? count($employeeData['documents']) : 0,
                         'total_documents' => count($files) + (isset($employeeData['documents']) ? count($employeeData['documents']) : 0),
                         'upload_method' => !empty($files) ? 'real_files' : (isset($employeeData['documents']) ? 'json_data' : 'none'),
                         'created_by' => $user,
                         'created_at' => date('Y-m-d H:i:s')
                    ], 201);
               } catch (\Exception $e) {
                    $db->transRollback();
                    log_message('error', 'Transaction error: ' . $e->getMessage());
                    throw $e;
               }
          } catch (\Exception $e) {
               log_message('error', 'Error creating employee: ' . $e->getMessage());
               return $this->respond([
                    'status' => false,
                    'message' => 'An error occurred while creating employee: ' . $e->getMessage(),
                    'error_code' => 'GENERAL_ERROR'
               ], 500);
          }
     }

     public function updateEmployeeStatus()
     {
          try {
               // Fetch validateAuthorization
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['user_code'];
               $role = $userDetails['role'];


               $employeeData = $this->request->getJSON(true);

               if (empty($employeeData) || !isset($employeeData['employee_code']) || !isset($employeeData['status'])) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Invalid input data'
                    ], 400);
               }

               $newUserModel = new NewUserModel();
               $employee = $newUserModel->where('employee_code', $employeeData['employee_code'])->first();
               if (!$employee) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Employee not found'
                    ], 404);
               }

               // Use the correct primary key here:
               $primaryKey = $employee['emp_id'] ?? null;
               if (!$primaryKey) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Employee primary key not found'
                    ], 500);
               }
               // Update status
               $updateData = [
                    'Active_Inactive' => $employeeData['status'] == 'Active' ? 'Active' : 'Inactive',
                    'status' => $employeeData['status'] == 'Active' ? 'A' : 'I',
                    'isDeleted' => $employeeData['status'] == 'Active' ? 'N' : 'Y',
                    'updated_at' => date('Y-m-d H:i:s')
               ];
               $newUserModel->update($primaryKey, $updateData);
               $this->logActivity('Employee Status Updated', $user, $updateData);

               return $this->respond([
                    'status' => true,
                    'message' => 'Employee status updated successfully'
               ]);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'An error occurred while updating employee status: ' . $e->getMessage()
               ], 500);
          }
     }

    //  public function createNewEmployee()
    //  {
    //       try {
    //           // Fetch validateAuthorization
    //           $userDetails = $this->validateAuthorization();
    //           $user = $userDetails['user_code'];
    //           $role = $userDetails['role'];

    //           // Validate user exists
    //           $userModel = new UserModel();
    //           $currentUser = $userModel->where('user_code', $user)->first();
    //           if (!$currentUser) {
    //                 return $this->respond([
    //                      'status' => false,
    //                      'message' => 'User not found'
    //                 ], 404);
    //           }

    //           // Determine how to get the data based on Content-Type
    //           $contentType = $this->request->getHeaderLine('Content-Type');
    //           $employeeData = [];

    //           if (strpos($contentType, 'multipart/form-data') !== false) {
    //                 // Handle form-data (from frontend with files)
    //                 $postData = $this->request->getPost();

    //                 if (isset($postData['data']) && !empty($postData['data'])) {
    //                      $employeeData = json_decode($postData['data'], true);
    //                      if (json_last_error() !== JSON_ERROR_NONE) {
    //                           return $this->respond([
    //                               'status' => false,
    //                               'message' => 'Invalid JSON in data field: ' . json_last_error_msg()
    //                           ], 400);
    //                      }
    //                 } else {
    //                      $employeeData = $postData;
    //                 }
    //           } else {
    //                 // Handle JSON data (application/json)
    //                 $requestData = $this->request->getJSON(true);
    //                 if (!empty($requestData)) {
    //                      $employeeData = $requestData;
    //                 }
    //           }

    //           if (empty($employeeData)) {
    //                 return $this->respond([
    //                      'status' => false,
    //                      'message' => 'No data provided'
    //                 ], 400);
    //           }


    //           // Sanitize payload (convert empty strings to null for dates, cast numerics)
    //           $this->sanitizeEmployeePayload($employeeData);

    //           // Debug log
    //           log_message('debug', 'Employee data received (sanitized): ' . json_encode($employeeData));

    //           // Handle uploaded files
    //           $uploadedFiles = $this->request->getFiles();
    //           $files = [];
    //           $documentNames = $this->request->getPost('document_names') ?? [];

    //           // Debug: Log what files were received
    //         //   log_message('error', '[FILE_UPLOAD] Content-Type: ' . $contentType);
    //         //   log_message('error', '[FILE_UPLOAD] Raw uploaded files: ' . json_encode($uploadedFiles));
    //         //   log_message('error', '[FILE_UPLOAD] Uploaded files keys: ' . json_encode(array_keys($uploadedFiles)));
    //         //   log_message('error', '[FILE_UPLOAD] Document names received: ' . json_encode($documentNames));

    //           // Additional debug for multipart data
    //           if (strpos($contentType, 'multipart/form-data') !== false) {
    //                 $postData = $this->request->getPost();
    //                 log_message('error', '[FILE_UPLOAD] POST data keys: ' . json_encode(array_keys($postData)));
    //           }

    //           if (!empty($uploadedFiles)) {
    //                 log_message('error', '[FILE_UPLOAD] Total uploaded file fields: ' . count($uploadedFiles));

    //                 foreach ($uploadedFiles as $fieldName => $file) {
    //                      log_message('error', '[FILE_UPLOAD] Processing field: ' . $fieldName . ' - Is array: ' . (is_array($file) ? 'YES (' . count($file) . ' files)' : 'NO (single file)'));

    //                      // Additional debug for file structure
    //                      if (is_array($file)) {
    //                           log_message('error', '[FILE_UPLOAD] Array field "' . $fieldName . '" contains ' . count($file) . ' files');
    //                           foreach ($file as $index => $singleFile) {
    //                               log_message('error', '[FILE_UPLOAD] File [' . $index . ']: ' . $singleFile->getClientName() . ' (valid: ' . ($singleFile->isValid() ? 'YES' : 'NO') . ')');
    //                           }
    //                      } else {
    //                           log_message('error', '[FILE_UPLOAD] Single file "' . $fieldName . '": ' . $file->getClientName() . ' (valid: ' . ($file->isValid() ? 'YES' : 'NO') . ')');
    //                      }
    //                      if ($fieldName === 'files') {
    //                           // Handle multiple files uploaded as 'files'
    //                           if (is_array($file)) {
    //                               log_message('error', '[FILE_UPLOAD] Processing files array with ' . count($file) . ' files');
    //                               foreach ($file as $index => $singleFile) {
    //                                     log_message('error', '[FILE_UPLOAD] Processing file index ' . $index . ' - Valid: ' . ($singleFile->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($singleFile->hasMoved() ? 'YES' : 'NO'));
    //                                     if ($singleFile->isValid() && !$singleFile->hasMoved()) {
    //                                          $processedFile = $this->processFile($singleFile, $fieldName);
    //                                          // Use document name from frontend if available, otherwise use original filename
    //                                          if (isset($documentNames[$index]) && !empty($documentNames[$index])) {
    //                                               $processedFile['document_name'] = $documentNames[$index];
    //                                          }
    //                                          $files[] = $processedFile;
    //                                          log_message('error', 'Processed file: ' . $singleFile->getClientName() . ' as ' . $processedFile['original_name']);
    //                                     }
    //                               }
    //                           } else {
    //                               // Single file uploaded as 'files'
    //                               log_message('error', '[FILE_UPLOAD] Processing single file - Valid: ' . ($file->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($file->hasMoved() ? 'YES' : 'NO'));
    //                               if ($file->isValid() && !$file->hasMoved()) {
    //                                     $processedFile = $this->processFile($file, $fieldName);
    //                                     if (isset($documentNames[0]) && !empty($documentNames[0])) {
    //                                          $processedFile['document_name'] = $documentNames[0];
    //                                     }
    //                                     $files[] = $processedFile;
    //                                     log_message('error', 'Processed single file: ' . $file->getClientName() . ' as ' . $processedFile['original_name']);
    //                               }
    //                           }
    //                      } elseif (preg_match('/^files\[(\d+)\]$/', $fieldName, $matches)) {
    //                           // Handle indexed files like files[0], files[1], etc. (from frontend)
    //                           $fileIndex = (int)$matches[1];
    //                           log_message('error', '[FILE_UPLOAD] Processing indexed file: ' . $fieldName . ' (index: ' . $fileIndex . ') - Valid: ' . ($file->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($file->hasMoved() ? 'YES' : 'NO'));

    //                           if ($file->isValid() && !$file->hasMoved()) {
    //                               $processedFile = $this->processFile($file, 'files');

    //                               // Use document name from frontend if available
    //                               if (isset($documentNames[$fileIndex]) && !empty($documentNames[$fileIndex])) {
    //                                     $processedFile['document_name'] = $documentNames[$fileIndex];
    //                               }

    //                               $files[] = $processedFile;
    //                               log_message('error', 'Processed indexed file: ' . $file->getClientName() . ' as ' . $processedFile['original_name'] . ' with document name: ' . ($processedFile['document_name'] ?? 'N/A'));
    //                           }
    //                      } else {
    //                           // Handle other file fields (individual file inputs)
    //                           if (is_array($file)) {
    //                               log_message('debug', '[FILE_UPLOAD] Processing other field array with ' . count($file) . ' files');
    //                               foreach ($file as $index => $singleFile) {
    //                                     if ($singleFile->isValid() && !$singleFile->hasMoved()) {
    //                                          $processedFile = $this->processFile($singleFile, $fieldName);
    //                                          $processedFile['document_name'] = $fieldName; // Use field name as document name
    //                                          $files[] = $processedFile;
    //                                          log_message('debug', 'Processed array file: ' . $singleFile->getClientName());
    //                                     }
    //                               }
    //                           } else {
    //                               log_message('debug', '[FILE_UPLOAD] Processing single other field - Valid: ' . ($file->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($file->hasMoved() ? 'YES' : 'NO'));
    //                               if ($file->isValid() && !$file->hasMoved()) {
    //                                     $processedFile = $this->processFile($file, $fieldName);
    //                                     $processedFile['document_name'] = $fieldName; // Use field name as document name
    //                                     $files[] = $processedFile;
    //                                     log_message('debug', 'Processed single field file: ' . $file->getClientName());
    //                               }
    //                           }
    //                      }
    //                 }
    //           }

    //           log_message('error', 'Total files processed: ' . count($files));

    //           // Validate required fields
    //           $requiredFields = ['employee_code', 'employee_name'];
    //           foreach ($requiredFields as $field) {
    //                 if (empty($employeeData[$field])) {
    //                      return $this->respond([
    //                           'status' => false,
    //                           'message' => "Required field '{$field}' is missing",
    //                           'available_fields' => array_keys($employeeData)
    //                      ], 400);
    //                 }
    //           }

    //           // Check if employee code already exists
    //           $newUserModel = new NewUserModel();
    //           $existingEmployee = $newUserModel->where('employee_code', $employeeData['employee_code'])->first();
    //           if ($existingEmployee) {
    //                 return $this->respond([
    //                      'status' => false,
    //                      'message' => 'Employee code already exists'
    //                 ], 409);
    //           }

    //           // Start database transaction
    //           $db = \Config\Database::connect();
    //           $db->transStart();

    //           try {
    //                 // Create basic employee record
    //                 $basicEmployeeData = $this->filterBasicEmployeeData($employeeData);

    //                 $empId = $newUserModel->insert($basicEmployeeData, true);

    //                 if (!$empId) {
    //                      // Log model validation errors if any
    //                      $validationErrors = $newUserModel->errors();
    //                      if (!empty($validationErrors)) {
    //                           log_message('error', 'Model validation errors: ' . json_encode($validationErrors));
    //                      }
    //                      // Log last database error if any
    //                      $dbError = $newUserModel->db->error();
    //                      if (!empty($dbError['message'])) {
    //                           log_message('error', 'DB error: ' . $dbError['message']);
    //                      }
    //                      throw new \Exception('Failed to create basic employee record');
    //                 }

    //                 log_message('debug', 'Employee created with ID: ' . $empId);

    //                 // Handle educations if present
    //                 if (isset($employeeData['educations']) && is_array($employeeData['educations'])) {
    //                      $this->handleEmployeeEducations($empId, $employeeData['educations']);
    //                      log_message('debug', 'Educations processed: ' . count($employeeData['educations']));
    //                 }

    //                 // Handle experiences if present  
    //                 if (isset($employeeData['experiences']) && is_array($employeeData['experiences'])) {
    //                      $this->handleEmployeeExperiences($empId, $employeeData['experiences']);
    //                      log_message('debug', 'Experiences processed: ' . count($employeeData['experiences']));
    //                 }

    //                 // Handle documents/files if present
    //                 if (!empty($files)) {
    //                      // Use processed uploaded files (real file uploads)
    //                      $this->handleEmployeeDocuments($empId, $files);
    //                      log_message('debug', 'Real files processed: ' . count($files));
    //                 } elseif (isset($employeeData['documents']) && is_array($employeeData['documents'])) {
    //                      // Use documents from JSON data (for testing or when files are pre-processed)
    //                      $this->handleEmployeeDocuments($empId, $employeeData['documents']);
    //                      log_message('debug', 'JSON documents processed: ' . count($employeeData['documents']));
    //                 }

    //                 // Additional logging for debugging
    //                 log_message('debug', 'Final summary - Files uploaded: ' . count($files) . ', JSON docs: ' . (isset($employeeData['documents']) ? count($employeeData['documents']) : 0));

    //                 $db->transComplete();

    //                 if ($db->transStatus() === false) {
    //                      throw new \Exception('Transaction failed');
    //                 }

    //                 // Get the created employee data
    //                 $createdEmployee = $newUserModel->find($empId);
                    
    //                 // Insert user record in users table
    //                 $userModel = new UserModel();
    //                 $user_name = $employeeData['employee_name'] ?? '';
    //                 $emp_code = $employeeData['employee_code'] ?? '';
    //                 $user_data = [
    //                      'user_name'     => $user_name,
    //                      'user_code'     => $emp_code,
    //                      'emp_id'        => $empId,
    //                      'password'      => md5('adnet2008'),
    //                      'status'        => 'A',
    //                      'disabled'      => 'N',
    //                      'validity'      => date('Y-m-d', strtotime('+90 days')),
    //                      'failed_attems' => 0,
    //                      'is_admin'      => 'N',
    //                      'exit_date'     => '0000-00-00',
    //                      'role'          => 'EMPLOYEE',
    //                 ];

    //                 $userModel->insert($user_data);


    //                 return $this->respond([
    //                      'status' => true,
    //                      'message' => 'Employee created successfully',
    //                      'employee_id' => $empId,
    //                      'employee_code' => $employeeData['employee_code'],
    //                      'data' => $createdEmployee,
    //                      'educations_count' => isset($employeeData['educations']) ? count($employeeData['educations']) : 0,
    //                      'experiences_count' => isset($employeeData['experiences']) ? count($employeeData['experiences']) : 0,
    //                      'files_uploaded' => count($files),
    //                      'json_documents' => isset($employeeData['documents']) ? count($employeeData['documents']) : 0,
    //                      'total_documents' => count($files) + (isset($employeeData['documents']) ? count($employeeData['documents']) : 0),
    //                      'upload_method' => !empty($files) ? 'real_files' : (isset($employeeData['documents']) ? 'json_data' : 'none'),
    //                      'created_by' => $user,
    //                      'created_at' => date('Y-m-d H:i:s')
    //                 ], 201);
    //           } catch (\Exception $e) {
    //                 $db->transRollback();
    //                 log_message('error', 'Transaction error: ' . $e->getMessage());
    //                 throw $e;
    //           }
    //       } catch (\Exception $e) {
    //           log_message('error', 'Error creating employee: ' . $e->getMessage());
    //           return $this->respond([
    //                 'status' => false,
    //                 'message' => 'An error occurred while creating employee: ' . $e->getMessage(),
    //                 'error_code' => 'GENERAL_ERROR'
    //           ], 500);
    //       }
    //  }
     /**
      * Helper method to process uploaded files
      */

     private function processFile($file, $fieldName)
     {
          try {
               // Create upload directory if it doesn't exist
               $uploadPath = WRITEPATH . 'uploads/employees/';
               if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
               }

               // Generate unique filename
               $fileName = $file->getRandomName();
               $file->move($uploadPath, $fileName);

               return [
                    'original_name' => $fileName, // Use the actual moved filename
                    'document_name' => $file->getClientName(), // Original filename as document name
                    'path' => 'uploads/employees/' . $fileName,
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getClientMimeType(),
                    'field_name' => $fieldName
               ];
          } catch (\Exception $e) {
               log_message('error', 'Error processing file: ' . $e->getMessage());
               throw $e;
          }
     }

     /**
      * Handle employee educations - Updated for your database schema
      */
     private function handleEmployeeEducations($empId, $educations)
     {
          if (empty($educations)) return;

          $qualificationModel = new \App\Models\EmployeeQualificationModel();

          foreach ($educations as $education) {
               $qualificationData = [
                    'emp_id' => $empId, // Use emp_id as foreign key
                    'qualification' => $education['highest_qualification'] ?? '',
                    'specialization' => $education['specialization'] ?? '',
                    'yearOfPassing' => $education['passing_year'] ?? null,
                    'collegeName' => $education['university'] ?? '',
                    'status' => 'A'
               ];

               $qualificationModel->insert($qualificationData);
          }
     }

     /**
      * Handle employee experiences - Updated for your database schema
      */
     private function handleEmployeeExperiences($empId, $experiences)
     {
          if (empty($experiences)) return;

          $experienceModel = new \App\Models\EmployeeExperienceModel();

          foreach ($experiences as $experience) {
               $experienceData = [
                    'emp_id' => $empId, // Use emp_id as foreign key
                    'previous_company' => $experience['previous_company'] ?? '',
                    'previous_designation' => $experience['previous_designation'] ?? '',
                    'experience_years' => $experience['previous_experience_years'] ?? '',
                    'status' => 'A'
               ];

               $experienceModel->insert($experienceData);
          }
     }

     /**
      * Handle employee documents - Updated to work with processed files
      */
     private function handleEmployeeDocuments($empId, $documents)
     {
          if (empty($documents)) return;

          log_message('error', '[EMP_DOC_INSERT] *** METHOD CALLED *** emp_id: ' . $empId . ' documents count: ' . count($documents));

          $documentModel = new \App\Models\EmployeeDocumentModel();

          foreach ($documents as $doc) {
               log_message('error', '[EMP_DOC_INSERT] Processing document: ' . json_encode($doc));

               // Validate emp_id
               $validEmpId = (is_numeric($empId) && intval($empId) > 0) ? intval($empId) : null;
               if (!$validEmpId) {
                    throw new \Exception('Invalid employee ID for document insertion');
               }

               // Get document name - prioritize explicit document_name, then use original filename
               $docName = '';
               if (!empty($doc['document_name'])) {
                    $docName = trim($doc['document_name']);
               } elseif (!empty($doc['field_name'])) {
                    $docName = $doc['field_name'];
               } else {
                    $docName = 'Unknown Document';
               }

               // Get document path
               $documentPath = '';
               if (!empty($doc['path'])) {
                    $documentPath = $doc['path'];
               } elseif (!empty($doc['original_name'])) {
                    $documentPath = 'uploads/employees/' . $doc['original_name'];
               }

               if (empty($documentPath)) {
                    throw new \Exception('Document path is required for: ' . $docName);
               }

               $documentData = [
                    'emp_id' => $validEmpId,
                    'document_name' => $docName,
                    'document_path' => $documentPath,
                    'uploaded_at' => date('Y-m-d H:i:s')
               ];

               log_message('error', '[EMP_DOC_INSERT] ATTEMPTING INSERT with data: ' . json_encode($documentData));

               try {
                    $result = $documentModel->insert($documentData);
                    if (!$result) {
                         $dbError = $documentModel->db->error();
                         log_message('error', '[EMP_DOC_INSERT] Insert failed: ' . json_encode($documentData) . ' DB error: ' . json_encode($dbError));
                         throw new \Exception('Failed to save document: ' . $docName . ' - ' . ($dbError['message'] ?? 'Unknown database error'));
                    } else {
                         log_message('error', '[EMP_DOC_INSERT] Insert successful for emp_id: ' . $validEmpId . ' document: ' . $docName);
                    }
               } catch (\Exception $e) {
                    log_message('error', '[EMP_DOC_INSERT] Exception during insert: ' . $e->getMessage() . ' Data: ' . json_encode($documentData));
                    throw new \Exception('Document insert failed: ' . $e->getMessage());
               }
          }
     }



     //  public function getEmployees()
     //  {
     //       try {
     //           $userDetails = $this->validateAuthorization();

     //           $search = $this->request->getGet('search') ?? '';
     //           $department = $this->request->getGet('department') ?? '';
     //           $status = $this->request->getGet('status') ?? 'A';

     //           $newUserModel = new NewUserModel();
     //           $builder = $newUserModel->builder();

     //           // Apply search filter
     //           if (!empty($search)) {
     //                 $builder->groupStart()
     //                      ->like('employee_name', $search)
     //                      ->orLike('employee_code', $search)
     //                      ->orLike('email', $search)
     //                      ->orLike('mobile', $search)
     //                      ->groupEnd();
     //           }

     //           // Apply department filter
     //           if (!empty($department)) {
     //                 $builder->where('department', $department);
     //           }

     //           // Apply status filter
     //           $builder->where('status', $status);
     //           // Apply isDeleted filter
     //           $builder->where('isDeleted', 'N');

     //           // Get all employees without pagination
     //           $employees = $builder->orderBy('employee_code', 'ASC')
     //                 ->get()
     //                 ->getResultArray();

     //           return $this->respond([
     //                 'status' => true,
     //                 'data' => $employees,
     //                 'total' => count($employees)
     //           ]);
     //       } catch (\Exception $e) {
     //           log_message('error', 'Error getting employees: ' . $e->getMessage());
     //           return $this->respond([
     //                 'status' => false,
     //                 'message' => 'Error: ' . $e->getMessage()
     //           ], 500);
     //       }
     //  }


     // public function getEmployeesForMaster()
     // {
     //      try {
     //           $userDetails = $this->validateAuthorization();

     //           $search = $this->request->getGet('search') ?? '';
     //           $department = $this->request->getGet('department') ?? '';
     //           $status = $this->request->getGet('status') ?? 'A';

     //           $newUserModel = new NewUserModel();
     //           $builder = $newUserModel->builder();

     //           // Apply search filter
     //           if (!empty($search)) {
     //                $builder->groupStart()
     //                     ->like('employee_name', $search)
     //                     ->orLike('employee_code', $search)
     //                     ->orLike('email', $search)
     //                     ->orLike('mobile', $search)
     //                     ->groupEnd();
     //           }

     //           // Apply department filter
     //           if (!empty($department)) {
     //                $builder->where('department', $department);
     //           }

     //           // Apply status filter
     //           $builder->where('status', $status);


     //           // Get all employees
     //           $employees = $builder->orderBy('employee_code', 'ASC')
     //                ->get()
     //                ->getResultArray();

     //           if (empty($employees)) {
     //                return $this->respond([
     //                     'status' => true,
     //                     'data' => [],
     //                     'total' => 0
     //                ]);
     //           }

     //           // Collect all emp_ids and employee_codes for bulk fetching
     //           $empIds = array_column($employees, 'emp_id');
     //           $empCodes = array_column($employees, 'employee_code');

     //           // Fetch all documents for these employees
     //           $documentModel = new \App\Models\EmployeeDocumentModel();
     //           $allDocuments = [];
     //           if (!empty($empIds)) {
     //                $docs = $documentModel->whereIn('emp_id', $empIds)->findAll();
     //                foreach ($docs as $doc) {
     //                     $allDocuments[$doc['emp_id']][] = [
     //                          'document_name' => $doc['document_name'] ?? '',
     //                          'document_path' => $doc['document_path'] ?? '',
     //                          'uploaded_at' => $doc['uploaded_at'] ?? '',
     //                     ];
     //                }
     //           }

     //           // Fetch all experiences for these employees
     //           $experienceModel = new \App\Models\EmployeeExperienceModel();
     //           $allExperiences = [];
     //           $experienceModel = new \App\Models\EmployeeExperienceModel();
     //           $allExperiences = [];
     //           if (!empty($empCodes)) {
     //                $exps = $experienceModel->whereIn('emp_id', $empIds)->findAll();
     //                foreach ($exps as $exp) {
     //                     $allExperiences[$exp['emp_id']][] = [
     //                          'previous_company' => $exp['previous_company'] ?? '',
     //                          'previous_designation' => $exp['previous_designation'] ?? '',
     //                          'previous_experience_years' => $exp['experience_years'] ?? '',
     //                     ];
     //                }
     //           }

     //           // Fetch all educations for these employees
     //           $qualificationModel = new \App\Models\EmployeeQualificationModel();
     //           $allEducations = [];
     //           if (!empty($empCodes)) {
     //                $edus = $qualificationModel->whereIn('emp_id', $empIds)->findAll();
     //                foreach ($edus as $edu) {
     //                     $allEducations[$edu['emp_id']][] = [
     //                          'highest_qualification' => $edu['qualification'] ?? '',
     //                          'university' => $edu['collegeName'] ?? '',
     //                          'passing_year' => $edu['yearOfPassing'] ?? '',
     //                          'specialization' => $edu['specialization'] ?? ''
     //                     ];
     //                }
     //           }

     //           // Attach sub-arrays to each employee
     //           foreach ($employees as &$emp) {
     //                $empId = $emp['emp_id'];
     //                $empCode = $emp['employee_code'];
     //                $emp['documents'] = $allDocuments[$empId] ?? [];
     //                $emp['experiences'] = $allExperiences[$empId] ?? [];
     //                $emp['educations'] = $allEducations[$empId] ?? [];
     //           }

     //           return $this->respond([
     //                'status' => true,
     //                'data' => $employees,
     //                'total' => count($employees)
     //           ]);
     //      } catch (\Exception $e) {
     //           log_message('error', 'Error getting employees: ' . $e->getMessage());
     //           return $this->respond([
     //                'status' => false,
     //                'message' => 'Error: ' . $e->getMessage()
     //           ], 500);
     //      }
     // }

     public function getEmployeesForMaster()
     {
          try {
               $userDetails = $this->validateAuthorization();

               $search = $this->request->getGet('search') ?? '';
               $department = $this->request->getGet('department') ?? '';

               // Accept status from GET or JSON body (fallback)
               $statusRaw = $this->request->getGet('status');
               if ($statusRaw === null) {
                    $jsonBody = $this->request->getJSON(true);
                    $statusRaw = $jsonBody['status'] ?? null;
               }
               $statusRaw = is_string($statusRaw) ? strtolower(trim($statusRaw, " \t\n\r\0\x0B\"'")) : (string) ($statusRaw ?? '');

               $perPage = (int) ($this->request->getGet('per_page') ?? 0);
               $page = max(1, (int) ($this->request->getGet('page') ?? 1));

               // Map incoming status values to canonical DB values 'A' or 'I'
               $statusMap = [
                    'a' => 'A',
                    'active' => 'A',
                    '1' => 'A',
                    'true' => 'A',
                    'i' => 'I',
                    'inactive' => 'I',
                    '0' => 'I',
                    'false' => 'I'
               ];

               // If explicit 'all' / '' / 'any' requested, don't apply status filter
               $applyStatusFilter = !in_array($statusRaw, ['', 'all', 'any'], true);

               // Build list of statuses to filter (allow comma separated)
               $statuses = [];
               if ($applyStatusFilter) {
                    foreach (explode(',', $statusRaw) as $part) {
                         $p = strtolower(trim($part, " \t\n\r\0\x0B\"'"));
                         if ($p === '') continue;
                         if (isset($statusMap[$p])) {
                              $statuses[] = $statusMap[$p];
                         } else {
                              // allow single-letter 'a'/'i' or uppercase 'A'/'I'
                              $candidate = strtoupper(substr($p, 0, 1));
                              if (in_array($candidate, ['A', 'I'], true)) {
                                   $statuses[] = $candidate;
                              }
                         }
                    }
                    $statuses = array_values(array_unique($statuses));
                    // If user passed an invalid status, default back to 'A' (preserve previous behavior)
                    if (empty($statuses)) {
                         $statuses = ['A'];
                    }
               }

               $newUserModel = new NewUserModel();
               $builder = $newUserModel->builder();

               // Apply search filter
               if (!empty($search)) {
                    $builder->groupStart()
                         ->like('employee_name', $search)
                         ->orLike('employee_code', $search)
                         ->orLike('email', $search)
                         ->orLike('mobile', $search)
                         ->groupEnd();
               }

               // Apply department filter
               if (!empty($department)) {
                    $builder->where('department', $department);
               }

               // Apply status filter unless 'all' requested
               if ($applyStatusFilter) {
                    // Use whereIn so multiple statuses are possible in future
                    $builder->whereIn('status', $statuses);
                    log_message('debug', 'getEmployeesForMaster applied status filter: ' . json_encode($statuses));
               } else {
                    log_message('debug', 'getEmployeesForMaster no status filter applied (all requested)');
               }

               // Capture total before pagination
               $countBuilder = clone $builder;
               $total = $countBuilder->countAllResults(false);

               // Pagination handling with a safe max cap
               $maxPerPage = 1000;
               if ($perPage > 0) {
                    $perPage = min($perPage, $maxPerPage);
                    $offset = ($page - 1) * $perPage;
                    $builder->limit($perPage, $offset);
               }

               // Get employees (paginated if per_page supplied)
               $employees = $builder->orderBy('employee_code', 'ASC')
                    ->get()
                    ->getResultArray();

               if (empty($employees)) {
                    return $this->respond([
                         'status' => true,
                         'data' => [],
                         'total' => 0,
                         'page' => $page,
                         'per_page' => $perPage
                    ]);
               }

               // Collect emp_ids for bulk fetching related records (only for returned set)
               $empIds = array_column($employees, 'emp_id');

               // Documents
               $documentModel = new \App\Models\EmployeeDocumentModel();
               $allDocuments = [];
               if (!empty($empIds)) {
                    $docs = $documentModel->whereIn('emp_id', $empIds)->findAll();
                    foreach ($docs as $doc) {
                         $allDocuments[$doc['emp_id']][] = [
                              'document_name' => $doc['document_name'] ?? '',
                              'document_path' => $doc['document_path'] ?? '',
                              'uploaded_at' => $doc['uploaded_at'] ?? '',
                         ];
                    }
               }

               // Experiences
               $experienceModel = new \App\Models\EmployeeExperienceModel();
               $allExperiences = [];
               if (!empty($empIds)) {
                    $exps = $experienceModel->whereIn('emp_id', $empIds)->findAll();
                    foreach ($exps as $exp) {
                         $allExperiences[$exp['emp_id']][] = [
                              'previous_company' => $exp['previous_company'] ?? '',
                              'previous_designation' => $exp['previous_designation'] ?? '',
                              'previous_experience_years' => $exp['experience_years'] ?? '',
                         ];
                    }
               }

               // Educations
               $qualificationModel = new \App\Models\EmployeeQualificationModel();
               $allEducations = [];
               if (!empty($empIds)) {
                    $edus = $qualificationModel->whereIn('emp_id', $empIds)->findAll();
                    foreach ($edus as $edu) {
                         $allEducations[$edu['emp_id']][] = [
                              'highest_qualification' => $edu['qualification'] ?? '',
                              'university' => $edu['collegeName'] ?? '',
                              'passing_year' => $edu['yearOfPassing'] ?? '',
                              'specialization' => $edu['specialization'] ?? ''
                         ];
                    }
               }

               // Attach sub-arrays to each employee
               foreach ($employees as &$emp) {
                    $empId = $emp['emp_id'];
                    $emp['documents'] = $allDocuments[$empId] ?? [];
                    $emp['experiences'] = $allExperiences[$empId] ?? [];
                    $emp['educations'] = $allEducations[$empId] ?? [];
               }

               return $this->respond([
                    'status' => true,
                    'data' => $employees,
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage
               ]);
          } catch (\Exception $e) {
               log_message('error', 'Error getting employees: ' . $e->getMessage());
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }

     public function getEmployees()
     {
          try {
               $userDetails = $this->validateAuthorization();

               $search = $this->request->getGet('search') ?? '';
               $department = $this->request->getGet('department') ?? '';
               $status = $this->request->getGet('status') ?? 'A';

               $newUserModel = new NewUserModel();
               $builder = $newUserModel->builder();

               // Apply search filter
               if (!empty($search)) {
                    $builder->groupStart()
                         ->like('employee_name', $search)
                         ->orLike('employee_code', $search)
                         ->orLike('email', $search)
                         ->orLike('mobile', $search)
                         ->groupEnd();
               }

               // Apply department filter
               if (!empty($department)) {
                    $builder->where('department', $department);
               }

               // Apply status filter
               $builder->where('status', $status);

               // Exclude deleted employees
               // $builder->where('isDeleted', 'N');

               // Filter split shift - split_shift = 'N'
               //  $builder->where('split_shift', 'N');


               if (
                    $this->userRole === 'REPORTING_MANAGER' || $this->userRole === 'HOD_DOCTORS'
                    || $this->userRole === 'HOD_CONTRACTUAL' || $this->userRole === 'HOD_POOJARI'
               ) {
                    $builder->groupStart()
                         ->where('reporting_manager_empcode', $this->userEmpCode)
                         ->orWhere('skip_level_manager_empcode', $this->userEmpCode)
                         ->groupEnd();
               }

               // Get all employees
               $employees = $builder->orderBy('employee_code', 'ASC')
                    ->get()
                    ->getResultArray();

               if (empty($employees)) {
                    return $this->respond([
                         'status' => true,
                         'data' => [],
                         'total' => 0
                    ]);
               }

               // Collect all emp_ids and employee_codes for bulk fetching
               $empIds = array_column($employees, 'emp_id');
               $empCodes = array_column($employees, 'employee_code');

               // Fetch all documents for these employees
               $documentModel = new \App\Models\EmployeeDocumentModel();
               $allDocuments = [];
               if (!empty($empIds)) {
                    $docs = $documentModel->whereIn('emp_id', $empIds)->findAll();
                    foreach ($docs as $doc) {
                         $allDocuments[$doc['emp_id']][] = [
                              'document_name' => $doc['document_name'] ?? '',
                              'document_path' => $doc['document_path'] ?? '',
                              'uploaded_at' => $doc['uploaded_at'] ?? '',
                         ];
                    }
               }

               // Fetch all experiences for these employees
               $experienceModel = new \App\Models\EmployeeExperienceModel();
               $allExperiences = [];
               $experienceModel = new \App\Models\EmployeeExperienceModel();
               $allExperiences = [];
               if (!empty($empCodes)) {
                    $exps = $experienceModel->whereIn('emp_id', $empIds)->findAll();
                    foreach ($exps as $exp) {
                         $allExperiences[$exp['emp_id']][] = [
                              'previous_company' => $exp['previous_company'] ?? '',
                              'previous_designation' => $exp['previous_designation'] ?? '',
                              'previous_experience_years' => $exp['experience_years'] ?? '',
                         ];
                    }
               }

               // Fetch all educations for these employees
               $qualificationModel = new \App\Models\EmployeeQualificationModel();
               $allEducations = [];
               if (!empty($empCodes)) {
                    $edus = $qualificationModel->whereIn('emp_id', $empIds)->findAll();
                    foreach ($edus as $edu) {
                         $allEducations[$edu['emp_id']][] = [
                              'highest_qualification' => $edu['qualification'] ?? '',
                              'university' => $edu['collegeName'] ?? '',
                              'passing_year' => $edu['yearOfPassing'] ?? '',
                              'specialization' => $edu['specialization'] ?? ''
                         ];
                    }
               }

               // Attach sub-arrays to each employee
               foreach ($employees as &$emp) {
                    $empId = $emp['emp_id'];
                    $empCode = $emp['employee_code'];
                    $emp['documents'] = $allDocuments[$empId] ?? [];
                    $emp['experiences'] = $allExperiences[$empId] ?? [];
                    $emp['educations'] = $allEducations[$empId] ?? [];
               }

               return $this->respond([
                    'status' => true,
                    'data' => $employees,
                    'total' => count($employees)
               ]);
          } catch (\Exception $e) {
               log_message('error', 'Error getting employees: ' . $e->getMessage());
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }
     /**
      * Get employee by ID with complete data
      */
     public function getEmployeeById($empId = null)
     {
          try {
               $userDetails = $this->validateAuthorization();

               if (!$empId) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Employee ID is required'
                    ], 400);
               }

               $newUserModel = new NewUserModel();
               $employee = $newUserModel->find($empId);

               if (!$employee) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Employee not found'
                    ], 404);
               }

               // Get related data
               $employeeData = $employee;

               // Get educations
               try {
                    $qualificationModel = new \App\Models\EmployeeQualificationModel();
                    $educations = $qualificationModel->where('employee_code', $empId)->findAll();
                    $employeeData['educations'] = $educations;
               } catch (\Exception $e) {
                    log_message('warning', 'Could not fetch educations: ' . $e->getMessage());
                    $employeeData['educations'] = [];
               }

               // Get experiences
               try {
                    $experienceModel = new \App\Models\EmployeeExperienceModel();
                    $experiences = $experienceModel->where('employee_code', $empId)->findAll();
                    $employeeData['experiences'] = $experiences;
               } catch (\Exception $e) {
                    log_message('warning', 'Could not fetch experiences: ' . $e->getMessage());
                    $employeeData['experiences'] = [];
               }

               // Get documents
               try {
                    $documentModel = new \App\Models\EmployeeDocumentModel();
                    $documents = $documentModel->where('emp_id', $empId)->findAll();
                    $employeeData['documents'] = $documents;
               } catch (\Exception $e) {
                    log_message('warning', 'Could not fetch documents: ' . $e->getMessage());
                    $employeeData['documents'] = [];
               }

               return $this->respond([
                    'status' => true,
                    'data' => $employeeData
               ]);
          } catch (\Exception $e) {
               log_message('error', 'Error getting employee by ID: ' . $e->getMessage());
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }


     public function getEmployeeByEmpCode($empCode = null)
     {
          try {
               $userDetails = $this->validateAuthorization();

               if (!$empCode) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Employee Code is required'
                    ], 400);
               }

               $newUserModel = new NewUserModel();
               $employee = $newUserModel->where('employee_code', $empCode)->first();

               if (!$employee) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Employee not found'
                    ], 404);
               }

               // Get related data
               $employeeData = $employee;
               $empId = $employee['employee_code']; // Use employee_code for related tables

               // Get educations
               try {
                    $qualificationModel = new \App\Models\EmployeeQualificationModel();
                    $educations = $qualificationModel->where('employee_code', $empId)->findAll();
                    $employeeData['educations'] = array_map(function ($edu) {
                         return [
                              'highest_qualification' => $edu['qualification'] ?? '',
                              'university' => $edu['collegeName'] ?? '',
                              'passing_year' => $edu['yearOfPassing'] ?? '',
                              'specialization' => $edu['specialization'] ?? ''
                         ];
                    }, $educations);
               } catch (\Exception $e) {
                    log_message('warning', 'Could not fetch educations: ' . $e->getMessage());
                    $employeeData['educations'] = [];
               }

               // Get experiences
               try {
                    $experienceModel = new \App\Models\EmployeeExperienceModel();
                    $experiences = $experienceModel->where('employee_code', $empId)->findAll();
                    $employeeData['experiences'] = array_map(function ($exp) {
                         return [
                              'previous_company' => $exp['previous_company'] ?? '',
                              'previous_designation' => $exp['previous_designation'] ?? '',
                              'previous_experience_years' => $exp['experience_years'] ?? ''
                         ];
                    }, $experiences);
               } catch (\Exception $e) {
                    log_message('warning', 'Could not fetch experiences: ' . $e->getMessage());
                    $employeeData['experiences'] = [];
               }

               // Get documents
               try {
                    $documentModel = new \App\Models\EmployeeDocumentModel();
                    // Use the actual primary key ID for documents, not employee_code
                    $empPrimaryKey = $employee['id'] ?? $employee['emp_id'] ?? $employee['employee_id'] ?? null;
                    if ($empPrimaryKey) {
                         $documents = $documentModel->where('emp_id', $empPrimaryKey)->findAll();
                    } else {
                         // Fallback: try to find documents by employee_code if emp_id field exists in documents table
                         $documents = $documentModel->where('employee_code', $empId)->findAll();
                    }
                    $employeeData['documents'] = array_map(function ($doc) {
                         return [
                              'document_name' => $doc['document_name'] ?? '',
                              'file_type' => 'application/pdf', // Default file type
                              'file_size' => 0, // File size not stored
                              'original_name' => basename($doc['document_path'] ?? ''),
                              'document_path' => $doc['document_path'] ?? '',
                              'uploaded_at' => $doc['uploaded_at'] ?? ''
                         ];
                    }, $documents);
               } catch (\Exception $e) {
                    log_message('warning', 'Could not fetch documents: ' . $e->getMessage());
                    $employeeData['documents'] = [];
               }

               return $this->respond([
                    'status' => true,
                    'data' => $employeeData
               ]);
          } catch (\Exception $e) {
               log_message('error', 'Error getting employee by Code: ' . $e->getMessage());
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }



     // public function updateEmployee($empId = null)
     // {
     //      try {
     //           $userDetails = $this->validateAuthorization();
     //           $user = $userDetails['user_code'];

     //           if (!$empId) {
     //                return $this->respond([
     //                     'status' => false,
     //                     'message' => 'Employee ID is required'
     //                ], 400);
     //           }

     //           // Check if employee exists
     //           $newUserModel = new NewUserModel();
     //           $existingEmployee = $newUserModel->find($empId);
     //           if (!$existingEmployee) {
     //                return $this->respond([
     //                     'status' => false,
     //                     'message' => 'Employee not found'
     //                ], 404);
     //           }

     //           // Handle both form-data and JSON similar to createNewEmployee
     //           $contentType = $this->request->getHeaderLine('Content-Type');
     //           $employeeData = [];

     //           if (strpos($contentType, 'multipart/form-data') !== false) {
     //                $postData = $this->request->getPost();

     //                if (isset($postData['data']) && !empty($postData['data'])) {
     //                     $employeeData = json_decode($postData['data'], true);
     //                     if (json_last_error() !== JSON_ERROR_NONE) {
     //                          return $this->respond([
     //                               'status' => false,
     //                               'message' => 'Invalid JSON in data field: ' . json_last_error_msg()
     //                          ], 400);
     //                     }
     //                } else {
     //                     $employeeData = $postData;
     //                }
     //           } else {
     //                $requestData = $this->request->getJSON(true);
     //                if (!empty($requestData)) {
     //                     $employeeData = $requestData;
     //                }
     //           }

     //           if (empty($employeeData)) {
     //                return $this->respond([
     //                     'status' => false,
     //                     'message' => 'No data provided for update'
     //                ], 400);
     //           }

     //           // Sanitize payload (convert empty strings to null for dates, cast numerics)
     //           $this->sanitizeEmployeePayload($employeeData);

     //           // Handle uploaded files (COPY FROM createNewEmployee)
     //           $uploadedFiles = $this->request->getFiles();
     //           $files = [];
     //           $documentNames = $this->request->getPost('document_names') ?? [];

     //           // Debug: Log what files were received
     //           log_message('error', '[FILE_UPLOAD] Content-Type: ' . $contentType);
     //           log_message('error', '[FILE_UPLOAD] Raw uploaded files: ' . json_encode($uploadedFiles));
     //           log_message('error', '[FILE_UPLOAD] Uploaded files keys: ' . json_encode(array_keys($uploadedFiles)));
     //           log_message('error', '[FILE_UPLOAD] Document names received: ' . json_encode($documentNames));

     //           // Additional debug for multipart data
     //           if (strpos($contentType, 'multipart/form-data') !== false) {
     //                $postData = $this->request->getPost();
     //                log_message('error', '[FILE_UPLOAD] POST data keys: ' . json_encode(array_keys($postData)));
     //           }

     //           if (!empty($uploadedFiles)) {
     //                log_message('error', '[FILE_UPLOAD] Total uploaded file fields: ' . count($uploadedFiles));

     //                foreach ($uploadedFiles as $fieldName => $file) {
     //                     log_message('error', '[FILE_UPLOAD] Processing field: ' . $fieldName . ' - Is array: ' . (is_array($file) ? 'YES (' . count($file) . ' files)' : 'NO (single file)'));

     //                     if ($fieldName === 'files') {
     //                          // Handle multiple files uploaded as 'files'
     //                          if (is_array($file)) {
     //                               log_message('error', '[FILE_UPLOAD] Processing files array with ' . count($file) . ' files');
     //                               foreach ($file as $index => $singleFile) {
     //                                    log_message('error', '[FILE_UPLOAD] Processing file index ' . $index . ' - Valid: ' . ($singleFile->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($singleFile->hasMoved() ? 'YES' : 'NO'));
     //                                    if ($singleFile->isValid() && !$singleFile->hasMoved()) {
     //                                         $processedFile = $this->processFile($singleFile, $fieldName);
     //                                         if (isset($documentNames[$index]) && !empty($documentNames[$index])) {
     //                                              $processedFile['document_name'] = $documentNames[$index];
     //                                         }
     //                                         $files[] = $processedFile;
     //                                         log_message('error', 'Processed file: ' . $singleFile->getClientName() . ' as ' . $processedFile['original_name']);
     //                                    }
     //                               }
     //                          } else {
     //                               // Single file uploaded as 'files'
     //                               log_message('error', '[FILE_UPLOAD] Processing single file - Valid: ' . ($file->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($file->hasMoved() ? 'YES' : 'NO'));
     //                               if ($file->isValid() && !$file->hasMoved()) {
     //                                    $processedFile = $this->processFile($file, $fieldName);
     //                                    if (isset($documentNames[0]) && !empty($documentNames[0])) {
     //                                         $processedFile['document_name'] = $documentNames[0];
     //                                    }
     //                                    $files[] = $processedFile;
     //                                    log_message('error', 'Processed single file: ' . $file->getClientName() . ' as ' . $processedFile['original_name']);
     //                               }
     //                          }
     //                     } elseif (preg_match('/^files\[(\d+)\]$/', $fieldName, $matches)) {
     //                          // Handle indexed files like files[0], files[1], etc. (from frontend)
     //                          $fileIndex = (int)$matches[1];
     //                          log_message('error', '[FILE_UPLOAD] Processing indexed file: ' . $fieldName . ' (index: ' . $fileIndex . ') - Valid: ' . ($file->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($file->hasMoved() ? 'YES' : 'NO'));

     //                          if ($file->isValid() && !$file->hasMoved()) {
     //                               $processedFile = $this->processFile($file, 'files');

     //                               // Use document name from frontend if available
     //                               if (isset($documentNames[$fileIndex]) && !empty($documentNames[$fileIndex])) {
     //                                    $processedFile['document_name'] = $documentNames[$fileIndex];
     //                               }

     //                               $files[] = $processedFile;
     //                               log_message('error', 'Processed indexed file: ' . $file->getClientName() . ' as ' . $processedFile['original_name'] . ' with document name: ' . ($processedFile['document_name'] ?? 'N/A'));
     //                          }
     //                     } else {
     //                          // Handle other file fields (individual file inputs)
     //                          if (is_array($file)) {
     //                               foreach ($file as $index => $singleFile) {
     //                                    if ($singleFile->isValid() && !$singleFile->hasMoved()) {
     //                                         $processedFile = $this->processFile($singleFile, $fieldName);
     //                                         $processedFile['document_name'] = $fieldName;
     //                                         $files[] = $processedFile;
     //                                    }
     //                               }
     //                          } else {
     //                               if ($file->isValid() && !$file->hasMoved()) {
     //                                    $processedFile = $this->processFile($file, $fieldName);
     //                                    $processedFile['document_name'] = $fieldName;
     //                                    $files[] = $processedFile;
     //                               }
     //                          }
     //                     }
     //                }
     //           }

     //           log_message('error', 'Total files processed: ' . count($files));

     //           // Start database transaction
     //           $db = \Config\Database::connect();
     //           $db->transStart();

     //           try {
     //                // Update basic employee record
     //                $basicEmployeeData = $this->filterBasicEmployeeData($employeeData);
     //                $basicEmployeeData['updated_at'] = date('Y-m-d H:i:s');

     //                // Remove employee_code if it's the same as existing to avoid unique constraint
     //                if (
     //                     isset($basicEmployeeData['employee_code']) &&
     //                     $basicEmployeeData['employee_code'] === $existingEmployee['employee_code']
     //                ) {
     //                     unset($basicEmployeeData['employee_code']);
     //                }

     //                $result = $newUserModel->update($empId, $basicEmployeeData);

     //                if (!$result) {
     //                     $validationErrors = $newUserModel->errors();
     //                     if (!empty($validationErrors)) {
     //                          log_message('error', 'Model validation errors: ' . json_encode($validationErrors));
     //                     }
     //                     $dbError = $newUserModel->db->error();
     //                     if (!empty($dbError['message'])) {
     //                          log_message('error', 'DB error: ' . $dbError['message']);
     //                     }
     //                     throw new \Exception('Failed to update employee record');
     //                }

     //                // Update educations if present
     //                if (isset($employeeData['educations']) && is_array($employeeData['educations'])) {
     //                     $qualificationModel = new \App\Models\EmployeeQualificationModel();
     //                     $qualificationModel->where('emp_id', $empId)->delete();
     //                     $this->handleEmployeeEducations($empId, $employeeData['educations']);
     //                }

     //                // Update experiences if present
     //                if (isset($employeeData['experiences']) && is_array($employeeData['experiences'])) {
     //                     $experienceModel = new \App\Models\EmployeeExperienceModel();
     //                     $experienceModel->where('emp_id', $empId)->delete();
     //                     $this->handleEmployeeExperiences($empId, $employeeData['experiences']);
     //                }

     //                // CHANGED: Only add new documents, don't delete existing ones
     //                if (!empty($files)) {
     //                     // Add new documents without deleting existing ones
     //                     $this->handleEmployeeDocuments($empId, $files);
     //                     log_message('debug', 'New files added: ' . count($files));
     //                } elseif (isset($employeeData['documents']) && is_array($employeeData['documents'])) {
     //                     // Add JSON documents without deleting existing ones
     //                     $this->handleEmployeeDocuments($empId, $employeeData['documents']);
     //                     log_message('debug', 'JSON documents added: ' . count($employeeData['documents']));
     //                }

     //                $db->transComplete();

     //                if ($db->transStatus() === false) {
     //                     throw new \Exception('Transaction failed');
     //                }

     //                // Get updated employee data
     //                $updatedEmployee = $newUserModel->find($empId);

     //                $this->logActivity('Employee Updated', NULL, $updatedEmployee);

     //                $employeeCode = $existingEmployee['employee_code'];

     //                // data to insert in Travalapp 

     //                $prefixes = ['DR.', 'DR', 'MR.', 'MR', 'MRS.', 'MRS', 'MS.', 'MS', 'MISS', 'SHRI', 'SMT', 'PROF.', 'PROF'];

     //                // Prepare name for splitting
     //                $employeeName = trim($employeeData['employee_name'] ?? '');

     //                // Remove prefix if present (case-insensitive)
     //                $nameParts = preg_split('/\s+/', $employeeName);
     //                if (!empty($nameParts) && in_array(strtoupper(rtrim($nameParts[0], '.')), array_map('strtoupper', $prefixes))) {
     //                     array_shift($nameParts); // Remove the prefix
     //                }

     //                // Now extract names
     //                $fname = $nameParts[0] ?? ($basicEmployeeData['employee_name'] ?? null);
     //                $lname = count($nameParts) > 1 ? array_pop($nameParts) : null;
     //                $mname = count($nameParts) > 0 ? implode(' ', array_slice($nameParts, 1, -1)) : null;
     //                $masterData = [
     //                     'fname'            => $fname,
     //                     'mname'            => $mname,
     //                     'lname'            => $lname,
     //                     'comp_name'        => $basicEmployeeData['company'] ?? null,
     //                     'doj'              => !empty($basicEmployeeData['joining_date']) ? date('Y-m-d', strtotime($basicEmployeeData['joining_date'])) : null,
     //                     'dob'              => !empty($basicEmployeeData['dob']) ? date('Y-m-d', strtotime($basicEmployeeData['dob'])) : null,
     //                     'gender'           => $basicEmployeeData['gender'] ?? ($employeeData['gender'] ?? null),
     //                     'mail_id'          => $basicEmployeeData['mail'] ?? $basicEmployeeData['email'] ?? null,
     //                     'report_mngr'      => $basicEmployeeData['reporting_manager_empcode'] ?? $basicEmployeeData['reporting_manager_name'] ?? null,
     //                     'function_mngr'    => $basicEmployeeData['functional_manager_name'] ?? null,
     //                     'ou_name'          => $basicEmployeeData['main_department'] ?? null,
     //                     'dept_name'        => $basicEmployeeData['department'] ?? null,
     //                     'location_name'    => $basicEmployeeData['location_name'] ?? $basicEmployeeData['locationName'] ?? null,
     //                     'designation_name' => $basicEmployeeData['designation_name'] ?? $basicEmployeeData['designation'] ?? null,
     //                     'grade'            => $basicEmployeeData['grade_name'] ?? null,
     //                     'region'           => $basicEmployeeData['region'] ?? null,
     //                     'country'          => $basicEmployeeData['country'] ?? null,
     //                     'city'             => $basicEmployeeData['city_name'] ?? null,
     //                     'position'         => $basicEmployeeData['position'] ?? null,
     //                     'cost_center'      => $basicEmployeeData['cost_center'] ?? null,
     //                     'pay_group'        => $basicEmployeeData['pay_group'] ?? null,
     //                     'emp_status'       => $basicEmployeeData['status'] ?? 'A',
     //                     'active'           => $basicEmployeeData['active'] ?? 'Active',
     //                     'disabled'         => $basicEmployeeData['disabled'] ?? 'N',
     //                     'effective_from'   => !empty($basicEmployeeData['effective_from']) ? date('Y-m-d', strtotime($basicEmployeeData['effective_from'])) : null,
     //                     'modified_on'      => date('Y-m-d H:i:s'),
     //                     'modified_by'      => $user ?? null,
     //                     'mobile'           => $basicEmployeeData['mobile'] ?? $employeeData['mobile'] ?? null,
     //                     'exit_date'        => !empty($basicEmployeeData['exit_date']) ? date('Y-m-d', strtotime($basicEmployeeData['exit_date'])) : null,
     //                     'validity'         => date('Y-m-d', strtotime('+90 days')),
     //                     'bank_name'        => $basicEmployeeData['bank_account_name'] ?? null,
     //                     'bank_acnum'       => $basicEmployeeData['bank_account_number'] ?? null,
     //                     'ifsc_code'        => $basicEmployeeData['ifsc_code'] ?? null,
     //                     'failed_attempts'  => 0,
     //                ];
     //                // log employee code
     //                log_message('error', 'Updating master data for emp_code: ' . $employeeCode);
     //                // log master data
     //                //log_message('error', 'Master data to update: ' . json_encode($masterData));

     //                // $this->newEmployeeMasterModel->insert($masterData);
     //                // update master data based on emp_code
     //                $this->newEmployeeMasterModel->where('emp_code', $employeeCode)->set($masterData)->update();

     //                return $this->respond([
     //                     'status' => true,
     //                     'message' => 'Employee updated successfully',
     //                     'employee_id' => $empId,
     //                     'data' => $updatedEmployee,
     //                     'educations_count' => isset($employeeData['educations']) ? count($employeeData['educations']) : 0,
     //                     'experiences_count' => isset($employeeData['experiences']) ? count($employeeData['experiences']) : 0,
     //                     'files_uploaded' => count($files),
     //                     'json_documents' => isset($employeeData['documents']) ? count($employeeData['documents']) : 0,
     //                     'total_documents' => count($files) + (isset($employeeData['documents']) ? count($employeeData['documents']) : 0),
     //                     'upload_method' => !empty($files) ? 'real_files' : (isset($employeeData['documents']) ? 'json_data' : 'none'),
     //                     'updated_by' => $user,
     //                     'updated_at' => date('Y-m-d H:i:s')
     //                ]);
     //           } catch (\Exception $e) {
     //                $db->transRollback();
     //                log_message('error', 'Transaction error: ' . $e->getMessage());
     //                throw $e;
     //           }
     //      } catch (\Exception $e) {
     //           log_message('error', 'Error updating employee: ' . $e->getMessage());
     //           return $this->respond([
     //                'status' => false,
     //                'message' => 'Error: ' . $e->getMessage()
     //           ], 500);
     //      }
     // }




     public function updateEmployee($empId = null)
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['user_code'];

               if (!$empId) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Employee ID is required'
                    ], 400);
               }

               // Check if employee exists
               $newUserModel = new NewUserModel();
               $existingEmployee = $newUserModel->find($empId);
               if (!$existingEmployee) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Employee not found'
                    ], 404);
               }

               // **STORE EMPLOYEE CODE EARLY** - Before any processing
               $employeeCode = $existingEmployee['employee_code'];

               // Handle both form-data and JSON similar to createNewEmployee
               $contentType = $this->request->getHeaderLine('Content-Type');
               $employeeData = [];

               if (strpos($contentType, 'multipart/form-data') !== false) {
                    $postData = $this->request->getPost();

                    if (isset($postData['data']) && !empty($postData['data'])) {
                         $employeeData = json_decode($postData['data'], true);
                         if (json_last_error() !== JSON_ERROR_NONE) {
                              return $this->respond([
                                   'status' => false,
                                   'message' => 'Invalid JSON in data field: ' . json_last_error_msg()
                              ], 400);
                         }
                    } else {
                         $employeeData = $postData;
                    }
               } else {
                    $requestData = $this->request->getJSON(true);
                    if (!empty($requestData)) {
                         $employeeData = $requestData;
                    }
               }

               if (empty($employeeData)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'No data provided for update'
                    ], 400);
               }

               // Sanitize payload (convert empty strings to null for dates, cast numerics)
               $this->sanitizeEmployeePayload($employeeData);

               // Handle uploaded files (COPY FROM createNewEmployee)
               $uploadedFiles = $this->request->getFiles();
               $files = [];
               $documentNames = $this->request->getPost('document_names') ?? [];

               // Debug: Log what files were received
               // log_message('error', '[FILE_UPLOAD] Content-Type: ' . $contentType);
               // log_message('error', '[FILE_UPLOAD] Raw uploaded files: ' . json_encode($uploadedFiles));
               // log_message('error', '[FILE_UPLOAD] Uploaded files keys: ' . json_encode(array_keys($uploadedFiles)));
               // log_message('error', '[FILE_UPLOAD] Document names received: ' . json_encode($documentNames));

               // Additional debug for multipart data
               if (strpos($contentType, 'multipart/form-data') !== false) {
                    $postData = $this->request->getPost();
                    log_message('error', '[FILE_UPLOAD] POST data keys: ' . json_encode(array_keys($postData)));
               }

               if (!empty($uploadedFiles)) {
                    log_message('error', '[FILE_UPLOAD] Total uploaded file fields: ' . count($uploadedFiles));

                    foreach ($uploadedFiles as $fieldName => $file) {
                         log_message('error', '[FILE_UPLOAD] Processing field: ' . $fieldName . ' - Is array: ' . (is_array($file) ? 'YES (' . count($file) . ' files)' : 'NO (single file)'));

                         if ($fieldName === 'files') {
                              // Handle multiple files uploaded as 'files'
                              if (is_array($file)) {
                                   log_message('error', '[FILE_UPLOAD] Processing files array with ' . count($file) . ' files');
                                   foreach ($file as $index => $singleFile) {
                                        log_message('error', '[FILE_UPLOAD] Processing file index ' . $index . ' - Valid: ' . ($singleFile->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($singleFile->hasMoved() ? 'YES' : 'NO'));
                                        if ($singleFile->isValid() && !$singleFile->hasMoved()) {
                                             $processedFile = $this->processFile($singleFile, $fieldName);
                                             if (isset($documentNames[$index]) && !empty($documentNames[$index])) {
                                                  $processedFile['document_name'] = $documentNames[$index];
                                             }
                                             $files[] = $processedFile;
                                             log_message('error', 'Processed file: ' . $singleFile->getClientName() . ' as ' . $processedFile['original_name']);
                                        }
                                   }
                              } else {
                                   // Single file uploaded as 'files'
                                   log_message('error', '[FILE_UPLOAD] Processing single file - Valid: ' . ($file->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($file->hasMoved() ? 'YES' : 'NO'));
                                   if ($file->isValid() && !$file->hasMoved()) {
                                        $processedFile = $this->processFile($file, $fieldName);
                                        if (isset($documentNames[0]) && !empty($documentNames[0])) {
                                             $processedFile['document_name'] = $documentNames[0];
                                        }
                                        $files[] = $processedFile;
                                        log_message('error', 'Processed single file: ' . $file->getClientName() . ' as ' . $processedFile['original_name']);
                                   }
                              }
                         } elseif (preg_match('/^files\[(\d+)\]$/', $fieldName, $matches)) {
                              // Handle indexed files like files[0], files[1], etc. (from frontend)
                              $fileIndex = (int)$matches[1];
                              log_message('error', '[FILE_UPLOAD] Processing indexed file: ' . $fieldName . ' (index: ' . $fileIndex . ') - Valid: ' . ($file->isValid() ? 'YES' : 'NO') . ' - Moved: ' . ($file->hasMoved() ? 'YES' : 'NO'));

                              if ($file->isValid() && !$file->hasMoved()) {
                                   $processedFile = $this->processFile($file, 'files');

                                   // Use document name from frontend if available
                                   if (isset($documentNames[$fileIndex]) && !empty($documentNames[$fileIndex])) {
                                        $processedFile['document_name'] = $documentNames[$fileIndex];
                                   }

                                   $files[] = $processedFile;
                                   log_message('error', 'Processed indexed file: ' . $file->getClientName() . ' as ' . $processedFile['original_name'] . ' with document name: ' . ($processedFile['document_name'] ?? 'N/A'));
                              }
                         } else {
                              // Handle other file fields (individual file inputs)
                              if (is_array($file)) {
                                   foreach ($file as $index => $singleFile) {
                                        if ($singleFile->isValid() && !$singleFile->hasMoved()) {
                                             $processedFile = $this->processFile($singleFile, $fieldName);
                                             $processedFile['document_name'] = $fieldName;
                                             $files[] = $processedFile;
                                        }
                                   }
                              } else {
                                   if ($file->isValid() && !$file->hasMoved()) {
                                        $processedFile = $this->processFile($file, $fieldName);
                                        $processedFile['document_name'] = $fieldName;
                                        $files[] = $processedFile;
                                   }
                              }
                         }
                    }
               }

               log_message('error', 'Total files processed: ' . count($files));

               // Start database transaction
               $db = \Config\Database::connect();
               $db->transStart();

               try {
                    // Update basic employee record
                    $basicEmployeeData = $this->filterBasicEmployeeData($employeeData);
                    $basicEmployeeData['updated_at'] = date('Y-m-d H:i:s');

                    // Remove employee_code if it's the same as existing to avoid unique constraint
                    if (
                         isset($basicEmployeeData['employee_code']) &&
                         $basicEmployeeData['employee_code'] === $existingEmployee['employee_code']
                    ) {
                         unset($basicEmployeeData['employee_code']);
                    }

                    $result = $newUserModel->update($empId, $basicEmployeeData);

                    if (!$result) {
                         $validationErrors = $newUserModel->errors();
                         if (!empty($validationErrors)) {
                              log_message('error', 'Model validation errors: ' . json_encode($validationErrors));
                         }
                         $dbError = $newUserModel->db->error();
                         if (!empty($dbError['message'])) {
                              log_message('error', 'DB error: ' . $dbError['message']);
                         }
                         throw new \Exception('Failed to update employee record');
                    }

                    // Update educations if present
                    if (isset($employeeData['educations']) && is_array($employeeData['educations'])) {
                         $qualificationModel = new \App\Models\EmployeeQualificationModel();
                         $qualificationModel->where('emp_id', $empId)->delete();
                         $this->handleEmployeeEducations($empId, $employeeData['educations']);
                    }

                    // Update experiences if present
                    if (isset($employeeData['experiences']) && is_array($employeeData['experiences'])) {
                         $experienceModel = new \App\Models\EmployeeExperienceModel();
                         $experienceModel->where('emp_id', $empId)->delete();
                         $this->handleEmployeeExperiences($empId, $employeeData['experiences']);
                    }

                    // CHANGED: Only add new documents, don't delete existing ones
                    if (!empty($files)) {
                         // Add new documents without deleting existing ones
                         $this->handleEmployeeDocuments($empId, $files);
                         log_message('debug', 'New files added: ' . count($files));
                    } elseif (isset($employeeData['documents']) && is_array($employeeData['documents'])) {
                         // Add JSON documents without deleting existing ones
                         $this->handleEmployeeDocuments($empId, $employeeData['documents']);
                         log_message('debug', 'JSON documents added: ' . count($employeeData['documents']));
                    }

                    $db->transComplete();

                    if ($db->transStatus() === false) {
                         throw new \Exception('Transaction failed');
                    }

                    // Get updated employee data
                    $updatedEmployee = $newUserModel->find($empId);

                    $this->logActivity('Employee Updated', NULL, $updatedEmployee);

                    // **UPDATE TRAVEL APP MASTER DATA**
                    // Use stored employee code
                    $prefixes = ['DR.', 'DR', 'MR.', 'MR', 'MRS.', 'MRS', 'MS.', 'MS', 'MISS', 'SHRI', 'SMT', 'PROF.', 'PROF'];

                    // Prepare name for splitting
                    $employeeName = trim($employeeData['employee_name'] ?? '');

                    // Remove prefix if present (case-insensitive)
                    $nameParts = preg_split('/\s+/', $employeeName);
                    if (!empty($nameParts) && in_array(strtoupper(rtrim($nameParts[0], '.')), array_map('strtoupper', $prefixes))) {
                         array_shift($nameParts); // Remove the prefix
                    }

                    // Now extract names
                    $fname = $nameParts[0] ?? ($employeeData['employee_name'] ?? null);
                    $lname = count($nameParts) > 1 ? array_pop($nameParts) : null;
                    $mname = count($nameParts) > 0 ? implode(' ', array_slice($nameParts, 1)) : null;

                    $masterData = [
                         'fname'            => $fname,
                         'mname'            => $mname,
                         'lname'            => $lname,
                         'comp_name'        => $basicEmployeeData['company'] ?? $employeeData['company'] ?? null,
                         'doj'              => !empty($basicEmployeeData['joining_date']) ? date('Y-m-d', strtotime($basicEmployeeData['joining_date'])) : (!empty($employeeData['joining_date']) ? date('Y-m-d', strtotime($employeeData['joining_date'])) : null),
                         'dob'              => !empty($basicEmployeeData['dob']) ? date('Y-m-d', strtotime($basicEmployeeData['dob'])) : (!empty($employeeData['dob']) ? date('Y-m-d', strtotime($employeeData['dob'])) : null),
                         'gender'           => $basicEmployeeData['gender'] ?? $employeeData['gender'] ?? null,
                         'mail_id'          => $basicEmployeeData['mail'] ?? $basicEmployeeData['email'] ?? $employeeData['email'] ?? null,
                         'report_mngr'      => $basicEmployeeData['reporting_manager_empcode'] ?? $basicEmployeeData['reporting_manager_name'] ?? $employeeData['reporting_manager_empcode'] ?? null,
                         'function_mngr'    => $basicEmployeeData['functional_manager_name'] ?? $employeeData['functional_manager_name'] ?? null,
                         'ou_name'          => $basicEmployeeData['main_department'] ?? $employeeData['main_department'] ?? null,
                         'dept_name'        => $basicEmployeeData['department'] ?? $employeeData['department'] ?? null,
                         'location_name'    => $basicEmployeeData['location_name'] ?? $basicEmployeeData['locationName'] ?? $employeeData['location_name'] ?? null,
                         'designation_name' => $basicEmployeeData['designation_name'] ?? $basicEmployeeData['designation'] ?? $employeeData['designation'] ?? null,
                         'grade'            => $basicEmployeeData['grade_name'] ?? $employeeData['grade_name'] ?? null,
                         'region'           => $basicEmployeeData['region'] ?? $employeeData['region'] ?? null,
                         'country'          => $basicEmployeeData['country'] ?? $employeeData['country'] ?? null,
                         'city'             => $basicEmployeeData['city_name'] ?? $employeeData['city_name'] ?? null,
                         'position'         => $basicEmployeeData['position'] ?? $employeeData['position'] ?? null,
                         'cost_center'      => $basicEmployeeData['cost_center'] ?? $employeeData['cost_center'] ?? null,
                         'pay_group'        => $basicEmployeeData['pay_group'] ?? $employeeData['pay_group'] ?? null,
                         'emp_status'       => $basicEmployeeData['status'] ?? $employeeData['status'] ?? 'A',
                         'active'           => $basicEmployeeData['active'] ?? $employeeData['active'] ?? 'Active',
                         'disabled'         => $basicEmployeeData['disabled'] ?? $employeeData['disabled'] ?? 'N',
                         'effective_from'   => !empty($basicEmployeeData['effective_from']) ? date('Y-m-d', strtotime($basicEmployeeData['effective_from'])) : (!empty($employeeData['effective_from']) ? date('Y-m-d', strtotime($employeeData['effective_from'])) : null),
                         'modified_on'      => date('Y-m-d H:i:s'),
                         'modified_by'      => $user ?? null,
                         'mobile'           => $basicEmployeeData['mobile'] ?? $employeeData['mobile'] ?? null,
                         'exit_date'        => !empty($basicEmployeeData['exit_date']) ? date('Y-m-d', strtotime($basicEmployeeData['exit_date'])) : (!empty($employeeData['exit_date']) ? date('Y-m-d', strtotime($employeeData['exit_date'])) : null),
                         'validity'         => date('Y-m-d', strtotime('+90 days')),
                         'bank_name'        => $basicEmployeeData['bank_account_name'] ?? $employeeData['bank_account_name'] ?? null,
                         'bank_acnum'       => $basicEmployeeData['bank_account_number'] ?? $employeeData['bank_account_number'] ?? null,
                         'ifsc_code'        => $basicEmployeeData['ifsc_code'] ?? $employeeData['ifsc_code'] ?? null,
                         'failed_attempts'  => 0,
                    ];

                    // Log employee code
                    log_message('error', 'Updating master data for emp_code: ' . $employeeCode);

                    // Check if record exists in travel app master table
                    $existingMaster = $this->newEmployeeMasterModel->where('emp_code', $employeeCode)->first();

                    log_message('error', 'Existing master record found: ' . ($existingMaster ? 'YES' : 'NO'));

                    if ($existingMaster) {
                         log_message('error', 'Master data to update: ' . json_encode($masterData));
                         log_message('error', 'Password field in masterData before unset: ' . (isset($masterData['password']) ? 'YES' : 'NO'));
                         // Ensure we DON'T overwrite existing password in travel app master
                         if (isset($masterData['password'])) {
                              unset($masterData['password']);
                              log_message('error', 'updateEmployee: removed password from masterData to avoid overwriting existing password for emp_code: ' . $employeeCode);
                         }
                         // Update existing record
                         $updateResult = $this->newEmployeeMasterModel->where('emp_code', $employeeCode)->set($masterData)->update();

                         if (!$updateResult) {
                              $dbError = $this->newEmployeeMasterModel->db->error();
                              log_message('error', 'Failed to update newEmployeeMasterModel for emp_code: ' . $employeeCode . ' Error: ' . json_encode($dbError));
                         } else {
                              log_message('error', 'Successfully updated newEmployeeMasterModel for emp_code: ' . $employeeCode);
                         }
                    } else {
                         // Insert new record if doesn't exist
                         $masterData['emp_code'] = $employeeCode;
                         $masterData['created_on'] = date('Y-m-d H:i:s');
                         $masterData['created_by'] = $user;
                         $masterData['password'] = md5('Adnet@2008');

                         $insertResult = $this->newEmployeeMasterModel->insert($masterData);

                         if (!$insertResult) {
                              $dbError = $this->newEmployeeMasterModel->db->error();
                              log_message('error', 'Failed to insert into newEmployeeMasterModel for emp_code: ' . $employeeCode . ' Error: ' . json_encode($dbError));
                         } else {
                              log_message('error', 'Successfully inserted into newEmployeeMasterModel for emp_code: ' . $employeeCode);
                         }
                    }

                    return $this->respond([
                         'status' => true,
                         'message' => 'Employee updated successfully',
                         'employee_id' => $empId,
                         'data' => $updatedEmployee,
                         'educations_count' => isset($employeeData['educations']) ? count($employeeData['educations']) : 0,
                         'experiences_count' => isset($employeeData['experiences']) ? count($employeeData['experiences']) : 0,
                         'files_uploaded' => count($files),
                         'json_documents' => isset($employeeData['documents']) ? count($employeeData['documents']) : 0,
                         'total_documents' => count($files) + (isset($employeeData['documents']) ? count($employeeData['documents']) : 0),
                         'upload_method' => !empty($files) ? 'real_files' : (isset($employeeData['documents']) ? 'json_data' : 'none'),
                         'updated_by' => $user,
                         'updated_at' => date('Y-m-d H:i:s')
                    ]);
               } catch (\Exception $e) {
                    $db->transRollback();
                    log_message('error', 'Transaction error: ' . $e->getMessage());
                    throw $e;
               }
          } catch (\Exception $e) {
               log_message('error', 'Error updating employee: ' . $e->getMessage());
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }



     public function deleteEmployeeDocument($documentId = null)
     {
          try {
               $userDetails = $this->validateAuthorization();

               if (!$documentId) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Document ID is required'
                    ], 400);
               }

               $documentModel = new \App\Models\EmployeeDocumentModel();
               $document = $documentModel->find($documentId);

               if (!$document) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Document not found'
                    ], 404);
               }

               // Delete physical file
               $filePath = WRITEPATH . $document['document_path'];
               if (file_exists($filePath)) {
                    unlink($filePath);
               }

               // Delete database record
               $documentModel->delete($documentId);
               $this->logActivity('Employee Document Deleted', NULL, $document);
               return $this->respond([
                    'status' => true,
                    'message' => 'Document deleted successfully',
                    'document_id' => $documentId
               ]);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }
     /**
      * Delete employee (soft delete)
      */
     public function deleteEmployee($empCode = null)
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['user_code'];

               if (!$empCode) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Employee code is required'
                    ], 400);
               }

               $newUserModel = new NewUserModel();

               // Find employee by employee code
               $employee = $newUserModel->where('employee_code', $empCode)->first();

               if (!$employee) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Employee not found'
                    ], 404);
               }

               // Perform soft delete using employee_code
               $updateData = [
                    'isDeleted' => 'Y',
                    'Active_Inactive' => 'Inactive',
                    'status' => 'I',
                    'updated_at' => date('Y-m-d H:i:s')
               ];

               $result = $newUserModel->where('employee_code', $empCode)->set($updateData)->update();

               if ($result) {
                    $this->logActivity('Employee Deleted', NULL, $employee);
                    return $this->respond([
                         'status' => true,
                         'message' => 'Employee deleted successfully',
                         'employee_code' => $empCode,
                         'deleted_by' => $user,
                         'deleted_at' => date('Y-m-d H:i:s')
                    ]);
               } else {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Failed to delete employee'
                    ], 500);
               }
          } catch (\Exception $e) {
               log_message('error', 'Error deleting employee: ' . $e->getMessage());
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }

     public function viewEmployeeDocument($filename = null)
     {
          try {
               // Handle OPTIONS request for CORS
               if ($this->request->getMethod() === 'OPTIONS') {
                    return $this->response
                         ->setHeader('Access-Control-Allow-Origin', '*')
                         ->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
                         ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                         ->setStatusCode(200);
               }

               if (!$filename) {
                    throw new \Exception('Filename is required');
               }

               // Decode filename if it's URL encoded
               $filename = urldecode($filename);

               $filePath = WRITEPATH . 'uploads/employees/' . $filename;

               log_message('error', 'Trying to access file: ' . $filePath);

               if (!file_exists($filePath)) {
                    log_message('error', 'File not found: ' . $filePath);
                    throw new \Exception('File not found: ' . $filePath);
               }

               // Get MIME type - Alternative method without finfo
               $mimeType = $this->getMimeType($filePath, $filename);

               // Set CORS headers
               $this->response->setHeader('Access-Control-Allow-Origin', '*');
               $this->response->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
               $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

               // Set file headers
               $this->response->setHeader('Content-Type', $mimeType);
               $this->response->setHeader('Content-Length', filesize($filePath));
               $this->response->setHeader('Content-Disposition', 'inline; filename="' . basename($filename) . '"');

               // Read and return file content
               $this->response->setBody(file_get_contents($filePath));
               return $this->response;
          } catch (\Exception $e) {
               log_message('error', 'Error viewing employee document: ' . $e->getMessage());
               return $this->response
                    ->setStatusCode(404)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setJSON([
                         'status' => false,
                         'message' => 'File not found: ' . $e->getMessage()
                    ]);
          }
     }

     /**
      * Get MIME type for file without using finfo extension
      */
     private function getMimeType($filePath, $filename)
     {
          // First, try using finfo if available
          if (function_exists('finfo_open')) {
               $finfo = finfo_open(FILEINFO_MIME_TYPE);
               if ($finfo) {
                    $mimeType = finfo_file($finfo, $filePath);
                    finfo_close($finfo);
                    if ($mimeType) {
                         return $mimeType;
                    }
               }
          }

          // Fallback: Determine MIME type by file extension
          $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

          $mimeTypes = [
               // Images
               'jpg' => 'image/jpeg',
               'jpeg' => 'image/jpeg',
               'png' => 'image/png',
               'gif' => 'image/gif',
               'bmp' => 'image/bmp',
               'webp' => 'image/webp',
               'svg' => 'image/svg+xml',

               // Documents
               'pdf' => 'application/pdf',
               'doc' => 'application/msword',
               'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
               'xls' => 'application/vnd.ms-excel',
               'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
               'ppt' => 'application/vnd.ms-powerpoint',
               'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
               'txt' => 'text/plain',
               'rtf' => 'application/rtf',

               // Archives
               'zip' => 'application/zip',
               'rar' => 'application/x-rar-compressed',
               '7z' => 'application/x-7z-compressed',

               // Other common types
               'json' => 'application/json',
               'xml' => 'application/xml',
               'csv' => 'text/csv',
               'mp4' => 'video/mp4',
               'mp3' => 'audio/mpeg',
               'wav' => 'audio/wav',
          ];

          return $mimeTypes[$extension] ?? 'application/octet-stream';
     }

     public function getEmployeesWithLeaveApplicable()
     {
          try {
               $userDetails = $this->validateAuthorization();

               $search = $this->request->getGet('search') ?? '';
               $department = $this->request->getGet('department') ?? '';
               $status = $this->request->getGet('status') ?? 'A';

               $newUserModel = new NewUserModel();
               $builder = $newUserModel->builder();

               // Apply search filter
               if (!empty($search)) {
                    $builder->groupStart()
                         ->like('employee_name', $search)
                         ->orLike('employee_code', $search)
                         ->orLike('email', $search)
                         ->orLike('mobile', $search)
                         ->groupEnd();
               }

               // Apply department filter
               if (!empty($department)) {
                    $builder->where('department', $department);
               }

               // Apply status filter
               $builder->where('status', $status);

               // Exclude deleted employees
               $builder->where('isDeleted', 'N');

               // Only include employees where isLeaveApplicable is 'Y'
               $builder->where('isLeaveApplicable', 'Y');

               // Get all employees
               $employees = $builder->orderBy('employee_code', 'ASC')
                    ->get()
                    ->getResultArray();

               if (empty($employees)) {
                    return $this->respond([
                         'status' => true,
                         'data' => [],
                         'total' => 0
                    ]);
               }

               // Collect all emp_ids and employee_codes for bulk fetching
               $empIds = array_column($employees, 'emp_id');
               $empCodes = array_column($employees, 'employee_code');

               // Fetch all documents for these employees
               $documentModel = new \App\Models\EmployeeDocumentModel();
               $allDocuments = [];
               if (!empty($empIds)) {
                    $docs = $documentModel->whereIn('emp_id', $empIds)->findAll();
                    foreach ($docs as $doc) {
                         $allDocuments[$doc['emp_id']][] = [
                              'document_name' => $doc['document_name'] ?? '',
                              'document_path' => $doc['document_path'] ?? '',
                              'uploaded_at' => $doc['uploaded_at'] ?? '',
                         ];
                    }
               }

               // Fetch all experiences for these employees
               $experienceModel = new \App\Models\EmployeeExperienceModel();
               $allExperiences = [];
               $experienceModel = new \App\Models\EmployeeExperienceModel();
               $allExperiences = [];
               if (!empty($empCodes)) {
                    $exps = $experienceModel->whereIn('emp_id', $empIds)->findAll();
                    foreach ($exps as $exp) {
                         $allExperiences[$exp['emp_id']][] = [
                              'previous_company' => $exp['previous_company'] ?? '',
                              'previous_designation' => $exp['previous_designation'] ?? '',
                              'previous_experience_years' => $exp['experience_years'] ?? '',
                         ];
                    }
               }

               // Fetch all educations for these employees
               $qualificationModel = new \App\Models\EmployeeQualificationModel();
               $allEducations = [];
               if (!empty($empCodes)) {
                    $edus = $qualificationModel->whereIn('emp_id', $empIds)->findAll();
                    foreach ($edus as $edu) {
                         $allEducations[$edu['emp_id']][] = [
                              'highest_qualification' => $edu['qualification'] ?? '',
                              'university' => $edu['collegeName'] ?? '',
                              'passing_year' => $edu['yearOfPassing'] ?? '',
                              'specialization' => $edu['specialization'] ?? ''
                         ];
                    }
               }

               // Attach sub-arrays to each employee
               foreach ($employees as &$emp) {
                    $empId = $emp['emp_id'];
                    $empCode = $emp['employee_code'];
                    $emp['documents'] = $allDocuments[$empId] ?? [];
                    $emp['experiences'] = $allExperiences[$empId] ?? [];
                    $emp['educations'] = $allEducations[$empId] ?? [];
               }

               return $this->respond([
                    'status' => true,
                    'data' => $employees,
                    'total' => count($employees)
               ]);
          } catch (\Exception $e) {
               log_message('error', 'Error getting employees: ' . $e->getMessage());
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }
}
