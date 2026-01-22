<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\NewUserModel;

use CodeIgniter\API\ResponseTrait;
use App\Controllers\BaseController;



class NewUser extends BaseController
{
     use ResponseTrait;

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
               'date_of_birth',
               'father_or_husband_name',
               'marital_status',
               'blood_group',
               'ctc',
               'agreement_valid_date',
               'agreement_end_date',
               'contract_fee_revision_amount',
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
               'ifsc_code'
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

          return $filteredData;
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
               log_message('error', '[FILE_UPLOAD] Content-Type: ' . $contentType);
               log_message('error', '[FILE_UPLOAD] Raw uploaded files: ' . json_encode($uploadedFiles));
               log_message('error', '[FILE_UPLOAD] Uploaded files keys: ' . json_encode(array_keys($uploadedFiles)));
               log_message('error', '[FILE_UPLOAD] Document names received: ' . json_encode($documentNames));

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

                    $empId = $newUserModel->insert($basicEmployeeData, true);

                    if (!$empId) {
                         // Log model validation errors if any
                         $validationErrors = $newUserModel->errors();
                         if (!empty($validationErrors)) {
                              log_message('error', 'Model validation errors: ' . json_encode($validationErrors));
                         }
                         // Log last database error if any
                         $dbError = $newUserModel->db->error();
                         if (!empty($dbError['message'])) {
                              log_message('error', 'DB error: ' . $dbError['message']);
                         }
                         throw new \Exception('Failed to create basic employee record');
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
                    'employee_code' => $empId, // Note: Using employee_code as per your schema
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
                    'employee_code' => $empId, // Note: Using employee_code as per your schema
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


     /**
      * Get all employees with pagination and filters
      */

     // public function getEmployees()
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

     //           // Apply isDeleted filter
     //           $builder->where('isDeleted', 'N');
     //           // Get all employees without pagination
     //           $employees = $builder->orderBy('employee_code', 'ASC')
     //                ->get()
     //                ->getResultArray();

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
               $builder->where('isDeleted', 'N');

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
               if (!empty($empIds)) {
                    $exps = $experienceModel->whereIn('employee_code', $empIds)->findAll();
                    foreach ($exps as $exp) {
                         $allExperiences[$exp['employee_code']][] = [
                              'previous_company' => $exp['previous_company'] ?? '',
                              'previous_designation' => $exp['previous_designation'] ?? '',
                              'previous_experience_years' => $exp['experience_years'] ?? '',
                         ];
                    }
               }

               // Fetch all educations for these employees
               $qualificationModel = new \App\Models\EmployeeQualificationModel();
               $allEducations = [];
               if (!empty($empIds)) {
                    $edus = $qualificationModel->whereIn('employee_code', $empIds)->findAll();
                    foreach ($edus as $edu) {
                         $allEducations[$edu['employee_code']][] = [
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

     //getEmployeeByEmpCode
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


     /**
      * Update employee with complete data
      */

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

               // Debug log
               log_message('debug', 'Employee data received (sanitized): ' . json_encode($employeeData));

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

                    $result = $newUserModel->update($empId, $basicEmployeeData, false); // Skip validation

                    if (!$result) {
                         // Log model validation errors if any
                         $validationErrors = $newUserModel->errors();
                         if (!empty($validationErrors)) {
                              log_message('error', 'Model validation errors: ' . json_encode($validationErrors));
                         }
                         // Log last database error if any
                         $dbError = $newUserModel->db->error();
                         if (!empty($dbError['message'])) {
                              log_message('error', 'DB error: ' . $dbError['message']);
                         }
                         throw new \Exception('Failed to update employee record');
                    }

                    // Update educations if present
                    if (isset($employeeData['educations']) && is_array($employeeData['educations'])) {
                         $qualificationModel = new \App\Models\EmployeeQualificationModel();
                         $qualificationModel->where('employee_code', $empId)->delete();
                         $this->handleEmployeeEducations($empId, $employeeData['educations']);
                    }

                    // Update experiences if present
                    if (isset($employeeData['experiences']) && is_array($employeeData['experiences'])) {
                         $experienceModel = new \App\Models\EmployeeExperienceModel();
                         $experienceModel->where('employee_code', $empId)->delete();
                         $this->handleEmployeeExperiences($empId, $employeeData['experiences']);
                    }

                    // Handle new documents/files if present
                    $uploadedFiles = $this->request->getFiles();
                    $files = [];
                    if (!empty($uploadedFiles)) {
                         foreach ($uploadedFiles as $fieldName => $file) {
                              if (is_array($file)) {
                                   foreach ($file as $singleFile) {
                                        if ($singleFile->isValid() && !$singleFile->hasMoved()) {
                                             $files[] = $this->processFile($singleFile, $fieldName);
                                        }
                                   }
                              } else {
                                   if ($file->isValid() && !$file->hasMoved()) {
                                        $files[] = $this->processFile($file, $fieldName);
                                   }
                              }
                         }
                    }

                    // Handle documents from JSON data if present
                    if (!empty($files)) {
                         // Use processed uploaded files (real file uploads)
                         $this->handleEmployeeDocuments($empId, $files);
                         log_message('debug', 'Real files processed: ' . count($files));
                    } elseif (isset($employeeData['documents']) && is_array($employeeData['documents'])) {
                         // Use documents from JSON data (for testing or when files are pre-processed)
                         $this->handleEmployeeDocuments($empId, $employeeData['documents']);
                         log_message('debug', 'JSON documents processed: ' . count($employeeData['documents']));
                    }

                    $db->transComplete();

                    if ($db->transStatus() === false) {
                         throw new \Exception('Transaction failed');
                    }

                    // Get updated employee data
                    $updatedEmployee = $newUserModel->find($empId);

                    return $this->respond([
                         'status' => true,
                         'message' => 'Employee updated successfully',
                         'employee_id' => $empId,
                         'data' => $updatedEmployee,
                         'files_uploaded' => count($files),
                         'json_documents' => isset($employeeData['documents']) ? count($employeeData['documents']) : 0,
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
                    'updated_at' => date('Y-m-d H:i:s')
               ];

               $result = $newUserModel->where('employee_code', $empCode)->set($updateData)->update();

               if ($result) {
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
}
