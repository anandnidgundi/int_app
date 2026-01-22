<?php

namespace App\Models;

use CodeIgniter\Model;

class HRMSModel extends Model
{
     protected $table = 'employees';
     protected $primaryKey = 'emp_id';

     /**
      * Get complete employee profile with all related data
      */
     public function getCompleteEmployeeProfile($empId)
     {
          $employeeModel = new NewUserModel();
          $userModel = new UserModel();
          $documentModel = new EmployeeDocumentModel();
          $experienceModel = new EmployeeExperienceModel();
          $qualificationModel = new EmployeeQualificationModel();

          // Get employee basic info
          $employee = $employeeModel->find($empId);

          if (!$employee) {
               return null;
          }

          // Get user account info
          $user = $userModel->where('emp_id', $empId)->first();

          // Get documents
          $documents = $documentModel->getEmployeeDocuments($empId);

          // Get experience (using employee_code)
          $experience = $experienceModel->getEmployeeExperience($employee['employee_code']);

          // Get qualifications (using employee_code)
          $qualifications = $qualificationModel->getEmployeeQualifications($employee['employee_code']);

          return [
               'employee' => $employee,
               'user' => $user,
               'documents' => $documents,
               'experience' => $experience,
               'qualifications' => $qualifications,
               'stats' => [
                    'document_count' => count($documents),
                    'experience_count' => count($experience),
                    'qualification_count' => count($qualifications),
                    'total_experience' => $experienceModel->getTotalExperience($employee['employee_code'])
               ]
          ];
     }

     /**
      * Create new employee with all related data
      */
     public function createCompleteEmployee($employeeData, $userData = null, $documents = [], $experience = [], $qualifications = [])
     {
          $db = \Config\Database::connect();
          $db->transStart();

          try {
               $employeeModel = new NewUserModel();
               $userModel = new UserModel();
               $documentModel = new EmployeeDocumentModel();
               $experienceModel = new EmployeeExperienceModel();
               $qualificationModel = new EmployeeQualificationModel();

               // Create employee
               $empId = $employeeModel->insert($employeeData);

               if (!$empId) {
                    throw new \Exception('Failed to create employee');
               }

               // Create user account if provided
               if ($userData) {
                    $userData['emp_id'] = $empId;
                    $userId = $userModel->insert($userData);

                    if (!$userId) {
                         throw new \Exception('Failed to create user account');
                    }
               }

               // Add documents
               foreach ($documents as $doc) {
                    $documentModel->uploadDocument($empId, $doc['name'], $doc['path']);
               }

               // Add experience records
               foreach ($experience as $exp) {
                    $exp['employee_code'] = $employeeData['employee_code'];
                    $experienceModel->addExperience($exp);
               }

               // Add qualification records
               foreach ($qualifications as $qual) {
                    $qual['employee_code'] = $employeeData['employee_code'];
                    $qualificationModel->addQualification($qual);
               }

               $db->transComplete();

               if ($db->transStatus() === FALSE) {
                    throw new \Exception('Transaction failed');
               }

               return $empId;
          } catch (\Exception $e) {
               $db->transRollback();
               log_message('error', 'Error creating complete employee: ' . $e->getMessage());
               return false;
          }
     }

     /**
      * Update employee with related data
      */
     public function updateCompleteEmployee($empId, $employeeData = null, $userData = null, $documents = [], $experience = [], $qualifications = [])
     {
          $db = \Config\Database::connect();
          $db->transStart();

          try {
               $employeeModel = new NewUserModel();
               $userModel = new UserModel();
               $documentModel = new EmployeeDocumentModel();
               $experienceModel = new EmployeeExperienceModel();
               $qualificationModel = new EmployeeQualificationModel();

               // Get employee code for related updates
               $employee = $employeeModel->find($empId);
               if (!$employee) {
                    throw new \Exception('Employee not found');
               }

               // Update employee basic info
               if ($employeeData) {
                    $employeeModel->update($empId, $employeeData);
               }

               // Update user account
               if ($userData) {
                    $user = $userModel->where('emp_id', $empId)->first();
                    if ($user) {
                         $userModel->update($user['id'], $userData);
                    }
               }

               // Handle document updates (simple approach: delete old, add new)
               if (!empty($documents)) {
                    // Note: In production, you might want more sophisticated document management
                    foreach ($documents as $doc) {
                         $documentModel->uploadDocument($empId, $doc['name'], $doc['path']);
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
      * Get HRMS dashboard statistics
      */
     public function getHRMSDashboardStats()
     {
          $employeeModel = new NewUserModel();
          $userModel = new UserModel();

          $stats = $employeeModel->getEmployeeStats();

          // Add user statistics
          $stats['total_users'] = $userModel->countAll();
          $stats['active_users'] = $userModel->where('status', 'A')->where('disabled', 'N')->countAllResults(false);
          $stats['admin_users'] = $userModel->where('is_admin', 'Y')->countAllResults();

          // Department wise count
          $departmentStats = $employeeModel->select('department, COUNT(*) as count')
               ->where('status', 'A')
               ->groupBy('department')
               ->findAll();

          $stats['department_wise'] = $departmentStats;

          return $stats;
     }

     /**
      * Search employees across all criteria
      */
     public function searchEmployees($searchTerm, $filters = [])
     {
          $employeeModel = new NewUserModel();
          $builder = $employeeModel->builder();

          // Basic search
          $builder->groupStart()
               ->like('employee_name', $searchTerm)
               ->orLike('employee_code', $searchTerm)
               ->orLike('email', $searchTerm)
               ->orLike('mobile', $searchTerm)
               ->groupEnd();

          // Apply filters
          if (isset($filters['department']) && !empty($filters['department'])) {
               $builder->where('department', $filters['department']);
          }

          if (isset($filters['designation']) && !empty($filters['designation'])) {
               $builder->where('designation', $filters['designation']);
          }

          if (isset($filters['employment_type']) && !empty($filters['employment_type'])) {
               $builder->where('employment_type', $filters['employment_type']);
          }

          if (isset($filters['status']) && !empty($filters['status'])) {
               $builder->where('status', $filters['status']);
          } else {
               $builder->where('status', 'A'); // Default to active only
          }

          return $builder->get()->getResultArray();
     }

     /**
      * Get employee hierarchy/reporting structure
      */
     public function getEmployeeHierarchy($empId = null)
     {
          $employeeModel = new NewUserModel();

          if ($empId) {
               // Get specific employee with their team
               $employee = $employeeModel->find($empId);
               if ($employee) {
                    $subordinates = $employeeModel->where('reporting_manager_empcode', $employee['employee_code'])
                         ->where('status', 'A')
                         ->findAll();
                    $employee['subordinates'] = $subordinates;
                    return $employee;
               }
          }

          // Get all managers with their teams
          $managers = $employeeModel->select('DISTINCT reporting_manager_empcode, reporting_manager_name')
               ->where('reporting_manager_empcode IS NOT NULL')
               ->where('status', 'A')
               ->findAll();

          $hierarchy = [];
          foreach ($managers as $manager) {
               $team = $employeeModel->where('reporting_manager_empcode', $manager['reporting_manager_empcode'])
                    ->where('status', 'A')
                    ->findAll();
               $hierarchy[] = [
                    'manager' => $manager,
                    'team' => $team
               ];
          }

          return $hierarchy;
     }

     /**
      * Get employees due for contract renewal
      */
     public function getContractRenewalDue($days = 30)
     {
          $employeeModel = new NewUserModel();
          $cutoffDate = date('Y-m-d', strtotime("+{$days} days"));

          return $employeeModel->where('latest_agreement_end_date <=', $cutoffDate)
               ->where('latest_agreement_end_date >=', date('Y-m-d'))
               ->where('status', 'A')
               ->where('resignation', 'No')
               ->findAll();
     }

     /**
      * Get birthday list for current month
      */
     public function getMonthlyBirthdays($month = null, $year = null)
     {
          $employeeModel = new NewUserModel();
          $month = $month ?: date('m');
          $year = $year ?: date('Y');

          return $employeeModel->where('MONTH(dob)', $month)
               ->where('status', 'A')
               ->orderBy('DAY(dob)', 'ASC')
               ->findAll();
     }

     /**
      * Get work anniversaries for current month
      */
     public function getMonthlyAnniversaries($month = null, $year = null)
     {
          $employeeModel = new NewUserModel();
          $month = $month ?: date('m');
          $year = $year ?: date('Y');

          return $employeeModel->where('MONTH(joining_date)', $month)
               ->where('status', 'A')
               ->where('resignation', 'No')
               ->orderBy('DAY(joining_date)', 'ASC')
               ->findAll();
     }
}
