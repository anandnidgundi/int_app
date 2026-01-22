<?php

namespace App\Models;

use CodeIgniter\Model;

class NewUserModel extends Model
{
     protected $table = 'employees';
     protected $primaryKey = 'emp_id';
     protected $useAutoIncrement = true;
     protected $returnType = 'array';
     protected $useSoftDeletes = false;
     protected $protectFields = true;

     protected $allowedFields = [
          'employee_code',
          'emp_type',
          'employee_name',
          'designation',
          'department',
          'joining_date',
          'employment_type',
          'mobile',
          'email',
          'mail',
          'dob',
          'gender',
          'father_husband_name',
          'marital_status',
          'blood_group',
          'religion',
          'caste',
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
          'total_experience',
          'bank_account_name',
          'bank_account_number',
          'ifsc_code',
          'aadhar_number',
          'pan_number',
          'tds_status',
          'ctc',
          'latest_agreement_valid_date',
          'latest_agreement_end_date',
          'latest_contract_fee_revision_amount',
          'resignation',
          'resignation_date',
          'relieving_date',
          'last_working_date',
          'last_pay_date',
          'separation_status',
          'notice_period',
          'status',
          'company',
          'pay_group',
          'city_name',
          'cluster',
          'location_name',
          'week_off',
          'isDeleted',
          'isLeaveApplicable',
          'address',
          'emergency_contact',
          'split_shift',
          'resignation_reason',

     ];

     protected $useTimestamps = true;
     protected $createdField = 'created_at';
     protected $updatedField = 'updated_at';

     protected $validationRules = [
          'employee_code' => 'required|is_unique[employees.employee_code,emp_id,{emp_id}]',
          'employee_name' => 'required',
          'emp_type' => 'required'
     ];

     protected $validationMessages = [
          'employee_code' => [
               'required' => 'Employee code is required',
               'is_unique' => 'Employee code must be unique'
          ]
     ];

     protected $skipValidation = false;
     protected $cleanValidationRules = true;

     protected $beforeInsert = ['fillDefaults'];
     protected $beforeUpdate = ['fillDefaults'];

     public function countAllSessions()
     {
          //     count all results in sessions table login_sessions
          return $this->db->table('login_sessions')->countAllResults();
     }



     protected function fillDefaults(array $data)
     {
          $fields = &$data['data'];

          // Defaults only for fields present in this model / table (use allowedFields keys)
          $defaults = [
               'bank_account_name'     => '',
               'bank_account_number'   => '',
               'ifsc_code'             => '',
               'status'                => $fields['status'] ?? 'A',
               'isDeleted'             => $fields['isDeleted'] ?? 'N',
               'failed_attempts'       => $fields['failed_attempts'] ?? 0,
               'created_at'            => $fields['created_at'] ?? date('Y-m-d H:i:s'),
               'updated_at'            => $fields['updated_at'] ?? date('Y-m-d H:i:s'),
               'emp_type'              => $fields['emp_type'] ?? 'CONTRACTUAL_EMPLOYEE',
          ];

          // Only apply defaults for keys that are allowed by the model (prevent unknown-column inserts)
          foreach ($defaults as $col => $val) {
               if (!in_array($col, $this->allowedFields, true)) {
                    continue;
               }
               // Fix: treat empty string as missing
               if (!array_key_exists($col, $fields) || $fields[$col] === null || $fields[$col] === '') {
                    $fields[$col] = $val;
               }
          }

          return $data;
     }

     // Custom methods for employee management

     /**
      * Get employee with related data
      */
     public function getEmployeeWithDetails($empId)
     {
          return $this->select('employees.*, users.user_code, users.role, users.is_admin')
               ->join('users', 'users.emp_id = employees.emp_id', 'left')
               ->where('employees.emp_id', $empId)
               ->first();
     }



     /**
      * Get active employees
      */
     public function getActiveEmployees()
     {
          return $this->where('status', 'A')
               ->where('resignation', 'No')
               ->findAll();
     }

     /**
      * Search employees by name or code
      */
     public function searchEmployees($searchTerm)
     {
          return $this->like('employee_name', $searchTerm)
               ->orLike('employee_code', $searchTerm)
               ->where('status', 'A')
               ->findAll();
     }

     /**
      * Get employees by department
      */
     public function getEmployeesByDepartment($department)
     {
          return $this->where('department', $department)
               ->where('status', 'A')
               ->orderBy('employee_code', 'ASC')
               ->findAll();
     }

     /**
      * Get resigned employees
      */
     public function getResignedEmployees()
     {
          return $this->where('resignation', 'Yes')
               ->findAll();
     }

     /**
      * Create employee with user account
      */
     public function createEmployeeWithUser($employeeData, $userData = null)
     {
          $db = \Config\Database::connect();
          $db->transStart();

          try {
               // Insert employee
               $empId = $this->insert($employeeData);

               if ($empId && $userData) {
                    // Create user account
                    $userModel = new \App\Models\UserModel();
                    $userData['emp_id'] = $empId;
                    $userModel->insert($userData);
               }

               $db->transComplete();

               if ($db->transStatus() === FALSE) {
                    return false;
               }

               return $empId;
          } catch (\Exception $e) {
               $db->transRollback();
               return false;
          }
     }

     /**
      * Create complete employee with related data (educations, experiences, documents)
      */
     public function createCompleteEmployee($payload, $files = [])
     {
          $db = \Config\Database::connect();
          $db->transStart();

          try {
               // Separate main employee data from related data
               $educations = $payload['educations'] ?? [];
               $experiences = $payload['experiences'] ?? [];
               unset($payload['educations'], $payload['experiences']);

               // Map alternative field names to database fields
               $fieldMapping = [
                    'fatherHusbandName' => 'father_husband_name',
                    'maritalStatus' => 'marital_status',
                    'bloodGroup' => 'blood_group',
                    'agreement_valid_date' => 'latest_agreement_valid_date',
                    'agreement_end_date' => 'latest_agreement_end_date',
                    'contract_fee_revision_amount' => 'latest_contract_fee_revision_amount'
               ];

               // Map payload fields to database fields
               $employeeData = [];
               foreach ($payload as $key => $value) {
                    $dbField = $fieldMapping[$key] ?? $key;
                    if (in_array($dbField, $this->allowedFields)) {
                         $employeeData[$dbField] = $value;
                    }
               }

               // Set default status if not provided
               $employeeData['status'] = $employeeData['status'] ?? 'A';

               // Insert employee
               $empId = $this->insert($employeeData);
               if (!$empId) {
                    $dbError = $this->db->error();
                    log_message('error', 'Failed to create employee: ' . print_r($dbError, true));
                    throw new \Exception('Failed to create employee: ' . ($dbError['message'] ?? 'Unknown DB error'));
               }

               // Handle educations/qualifications
               if (!empty($educations)) {
                    $qualificationModel = new \App\Models\EmployeeQualificationModel();
                    foreach ($educations as $education) {
                         $qualData = [
                              'employee_code' => $employeeData['employee_code'],
                              'qualification' => $education['highest_qualification'] ?? '',
                              'collegeName' => $education['university'] ?? '',
                              'yearOfPassing' => isset($education['passing_year']) ? $education['passing_year'] . '-01-01' : null,
                              'specialization' => $education['specialization'] ?? '',
                              'status' => 'A'
                         ];
                         $qualificationModel->insert($qualData);
                    }
               }

               // Handle experiences
               if (!empty($experiences)) {
                    $experienceModel = new \App\Models\EmployeeExperienceModel();
                    foreach ($experiences as $experience) {
                         $expData = [
                              'employee_code' => $employeeData['employee_code'],
                              'previous_company' => $experience['previous_company'] ?? '',
                              'previous_designation' => $experience['previous_designation'] ?? '',
                              'experience_years' => $experience['previous_experience_years'] ?? '0',
                              'status' => 'A'
                         ];
                         $experienceModel->insert($expData);
                    }
               }

               // Handle file uploads
               if (!empty($files)) {
                    $documentModel = new \App\Models\EmployeeDocumentModel();
                    foreach ($files as $file) {
                         if (isset($file['name']) && isset($file['path'])) {
                              $documentModel->insert([
                                   'emp_id' => $empId,
                                   'document_name' => $file['name'],
                                   'document_path' => $file['path']
                              ]);
                         }
                    }
               }

               $db->transComplete();

               if ($db->transStatus() === FALSE) {
                    $dbError = $db->error();
                    log_message('error', 'Transaction failed: ' . print_r($dbError, true));
                    throw new \Exception('Transaction failed: ' . ($dbError['message'] ?? 'Unknown DB error'));
               }

               return $empId;
          } catch (\Exception $e) {
               $db->transRollback();
               log_message('error', 'Error creating complete employee: ' . $e->getMessage());
               return false;
          }
     }

     /**
      * Update complete employee with related data
      */
     public function updateCompleteEmployee($empId, $payload, $files = [])
     {
          $db = \Config\Database::connect();
          $db->transStart();

          try {
               // Get existing employee data
               $existingEmployee = $this->find($empId);
               if (!$existingEmployee) {
                    throw new \Exception('Employee not found');
               }

               // Separate main employee data from related data
               $educations = $payload['educations'] ?? [];
               $experiences = $payload['experiences'] ?? [];
               unset($payload['educations'], $payload['experiences']);

               // Map alternative field names to database fields
               $fieldMapping = [
                    'fatherHusbandName' => 'father_husband_name',
                    'maritalStatus' => 'marital_status',
                    'bloodGroup' => 'blood_group',
                    'agreement_valid_date' => 'latest_agreement_valid_date',
                    'agreement_end_date' => 'latest_agreement_end_date',
                    'contract_fee_revision_amount' => 'latest_contract_fee_revision_amount'
               ];

               // Map payload fields to database fields
               $employeeData = [];
               foreach ($payload as $key => $value) {
                    $dbField = $fieldMapping[$key] ?? $key;
                    if (in_array($dbField, $this->allowedFields)) {
                         $employeeData[$dbField] = $value;
                    }
               }

               // Update employee
               $this->update($empId, $employeeData);

               // Update educations/qualifications
               if (!empty($educations)) {
                    $qualificationModel = new \App\Models\EmployeeQualificationModel();
                    // Delete existing qualifications
                    $qualificationModel->where('employee_code', $existingEmployee['employee_code'])
                         ->set(['status' => 'I'])
                         ->update();

                    // Add new qualifications
                    foreach ($educations as $education) {
                         $qualData = [
                              'employee_code' => $existingEmployee['employee_code'],
                              'qualification' => $education['highest_qualification'] ?? '',
                              'collegeName' => $education['university'] ?? '',
                              'yearOfPassing' => isset($education['passing_year']) ? $education['passing_year'] . '-01-01' : null,
                              'specialization' => $education['specialization'] ?? '',
                              'status' => 'A'
                         ];
                         $qualificationModel->insert($qualData);
                    }
               }

               // Update experiences
               if (!empty($experiences)) {
                    $experienceModel = new \App\Models\EmployeeExperienceModel();
                    // Delete existing experiences
                    $experienceModel->where('employee_code', $existingEmployee['employee_code'])
                         ->set(['status' => 'I'])
                         ->update();

                    // Add new experiences
                    foreach ($experiences as $experience) {
                         $expData = [
                              'employee_code' => $existingEmployee['employee_code'],
                              'previous_company' => $experience['previous_company'] ?? '',
                              'previous_designation' => $experience['previous_designation'] ?? '',
                              'experience_years' => $experience['previous_experience_years'] ?? '0',
                              'status' => 'A'
                         ];
                         $experienceModel->insert($expData);
                    }
               }

               // Handle file uploads (append new files)
               if (!empty($files)) {
                    $documentModel = new \App\Models\EmployeeDocumentModel();
                    foreach ($files as $file) {
                         if (isset($file['name']) && isset($file['path'])) {
                              $documentModel->insert([
                                   'emp_id' => $empId,
                                   'document_name' => $file['name'],
                                   'document_path' => $file['path']
                              ]);
                         }
                    }
               }

               $db->transComplete();

               if ($db->transStatus() === FALSE) {
                    throw new \Exception('Transaction failed');
               }

               return true;
          } catch (\Exception $e) {
               $db->transRollback();
               log_message('error', 'Error updating complete employee: ' . $e->getMessage());
               return false;
          }
     }

     /**
      * Get complete employee data with related tables
      */
     public function getCompleteEmployeeData($empId)
     {
          $employee = $this->find($empId);
          if (!$employee) {
               return null;
          }

          // Get related data
          $qualificationModel = new \App\Models\EmployeeQualificationModel();
          $experienceModel = new \App\Models\EmployeeExperienceModel();
          $documentModel = new \App\Models\EmployeeDocumentModel();

          $employee['educations'] = $qualificationModel->getEmployeeQualifications($employee['employee_code']);
          $employee['experiences'] = $experienceModel->getEmployeeExperience($employee['employee_code']);
          $employee['documents'] = $documentModel->getEmployeeDocuments($empId);

          return $employee;
     }

     /**
      * Update employee resignation status
      */
     public function updateResignationStatus($empId, $resignationData)
     {
          $updateData = [
               'resignation' => 'Yes',
               'resignation_date' => $resignationData['resignation_date'] ?? null,
               'relieving_date' => $resignationData['relieving_date'] ?? null,
               'last_working_date' => $resignationData['last_working_date'] ?? null,
               'last_pay_date' => $resignationData['last_pay_date'] ?? null,
               'separation_status' => $resignationData['separation_status'] ?? null,
               'notice_period' => $resignationData['notice_period'] ?? null
          ];

          return $this->update($empId, $updateData);
     }

     /**
      * Get employee count by status
      */
     public function getEmployeeStats()
     {
          $stats = [];
          $stats['total'] = $this->countAll();
          $stats['active'] = $this->where('status', 'A')->where('resignation', 'No')->countAllResults(false);
          $stats['resigned'] = $this->where('resignation', 'Yes')->countAllResults(false);
          $stats['inactive'] = $this->where('status', 'I')->countAllResults();

          return $stats;
     }
}
