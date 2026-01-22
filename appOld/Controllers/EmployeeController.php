<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\NewUserModel;
use App\Models\EmployeeQualificationModel;
use App\Models\EmployeeExperienceModel;
use App\Models\EmployeeDocumentModel;

class EmployeeController extends ResourceController
{
     protected $format = 'json';

     /**
      * Create new employee with complete data
      * Handles the payload structure you provided
      */
     public function create()
     {
          try {
               // Get JSON payload
               $payload = $this->request->getJSON(true);

               // Get uploaded files
               $files = [];
               $uploadedFiles = $this->request->getFiles();

               if ($uploadedFiles) {
                    foreach ($uploadedFiles as $file) {
                         if ($file->isValid() && !$file->hasMoved()) {
                              // Move file to upload directory
                              $fileName = $file->getRandomName();
                              $file->move(FCPATH . 'uploads/employees/', $fileName);

                              $files[] = [
                                   'name' => $file->getClientName(),
                                   'path' => 'uploads/employees/' . $fileName
                              ];
                         }
                    }
               }

               $employeeModel = new NewUserModel();
               $empId = $employeeModel->createCompleteEmployee($payload, $files);

               if ($empId) {
                    return $this->respond([
                         'status' => 'success',
                         'message' => 'Employee created successfully',
                         'employee_id' => $empId,
                         'data' => $employeeModel->getCompleteEmployeeData($empId)
                    ], 201);
               } else {
                    return $this->respond([
                         'status' => 'error',
                         'message' => 'Failed to create employee'
                    ], 400);
               }
          } catch (\Exception $e) {
               log_message('error', 'Employee creation error: ' . $e->getMessage());
               return $this->respond([
                    'status' => 'error',
                    'message' => 'Internal server error: ' . $e->getMessage()
               ], 500);
          }
     }

     /**
      * Update existing employee
      */
     public function update($id = null)
     {
          try {
               if (!$id) {
                    return $this->respond([
                         'status' => 'error',
                         'message' => 'Employee ID is required'
                    ], 400);
               }

               // Get JSON payload
               $payload = $this->request->getJSON(true);

               // Get uploaded files
               $files = [];
               $uploadedFiles = $this->request->getFiles();

               if ($uploadedFiles) {
                    foreach ($uploadedFiles as $file) {
                         if ($file->isValid() && !$file->hasMoved()) {
                              // Move file to upload directory
                              $fileName = $file->getRandomName();
                              $file->move(FCPATH . 'uploads/employees/', $fileName);

                              $files[] = [
                                   'name' => $file->getClientName(),
                                   'path' => 'uploads/employees/' . $fileName
                              ];
                         }
                    }
               }

               $employeeModel = new NewUserModel();
               $result = $employeeModel->updateCompleteEmployee($id, $payload, $files);

               if ($result) {
                    return $this->respond([
                         'status' => 'success',
                         'message' => 'Employee updated successfully',
                         'data' => $employeeModel->getCompleteEmployeeData($id)
                    ]);
               } else {
                    return $this->respond([
                         'status' => 'error',
                         'message' => 'Failed to update employee'
                    ], 400);
               }
          } catch (\Exception $e) {
               log_message('error', 'Employee update error: ' . $e->getMessage());
               return $this->respond([
                    'status' => 'error',
                    'message' => 'Internal server error: ' . $e->getMessage()
               ], 500);
          }
     }

     /**
      * Get employee with complete data
      */
     public function show($id = null)
     {
          try {
               if (!$id) {
                    return $this->respond([
                         'status' => 'error',
                         'message' => 'Employee ID is required'
                    ], 400);
               }

               $employeeModel = new NewUserModel();
               $employee = $employeeModel->getCompleteEmployeeData($id);

               if ($employee) {
                    return $this->respond([
                         'status' => 'success',
                         'data' => $employee
                    ]);
               } else {
                    return $this->respond([
                         'status' => 'error',
                         'message' => 'Employee not found'
                    ], 404);
               }
          } catch (\Exception $e) {
               log_message('error', 'Employee fetch error: ' . $e->getMessage());
               return $this->respond([
                    'status' => 'error',
                    'message' => 'Internal server error'
               ], 500);
          }
     }

     /**
      * Get all employees with pagination
      */
     public function index()
     {
          try {
               $employeeModel = new NewUserModel();

               // Get query parameters
               $page = $this->request->getGet('page') ?? 1;
               $limit = $this->request->getGet('limit') ?? 10;
               $search = $this->request->getGet('search');
               $department = $this->request->getGet('department');

               $offset = ($page - 1) * $limit;

               // Build query
               $builder = $employeeModel->builder();

               if ($search) {
                    $builder->groupStart()
                         ->like('employee_name', $search)
                         ->orLike('employee_code', $search)
                         ->orLike('email', $search)
                         ->groupEnd();
               }

               if ($department) {
                    $builder->where('department', $department);
               }

               $builder->where('status', 'A');

               // Get total count
               $total = $builder->countAllResults(false);

               // Get paginated results
               $employees = $builder->limit($limit, $offset)
                    ->orderBy('created_at', 'DESC')
                    ->get()
                    ->getResultArray();

               return $this->respond([
                    'status' => 'success',
                    'data' => $employees,
                    'pagination' => [
                         'current_page' => (int)$page,
                         'per_page' => (int)$limit,
                         'total' => $total,
                         'total_pages' => ceil($total / $limit)
                    ]
               ]);
          } catch (\Exception $e) {
               log_message('error', 'Employee list error: ' . $e->getMessage());
               return $this->respond([
                    'status' => 'error',
                    'message' => 'Internal server error'
               ], 500);
          }
     }

     /**
      * Soft delete employee
      */
     public function delete($id = null)
     {
          try {
               if (!$id) {
                    return $this->respond([
                         'status' => 'error',
                         'message' => 'Employee ID is required'
                    ], 400);
               }

               $employeeModel = new NewUserModel();
               $result = $employeeModel->update($id, ['status' => 'I']);

               if ($result) {
                    return $this->respond([
                         'status' => 'success',
                         'message' => 'Employee deleted successfully'
                    ]);
               } else {
                    return $this->respond([
                         'status' => 'error',
                         'message' => 'Failed to delete employee'
                    ], 400);
               }
          } catch (\Exception $e) {
               log_message('error', 'Employee delete error: ' . $e->getMessage());
               return $this->respond([
                    'status' => 'error',
                    'message' => 'Internal server error'
               ], 500);
          }
     }

     /**
      * Get employee statistics
      */
     public function stats()
     {
          try {
               $employeeModel = new NewUserModel();
               $stats = $employeeModel->getEmployeeStats();

               return $this->respond([
                    'status' => 'success',
                    'data' => $stats
               ]);
          } catch (\Exception $e) {
               log_message('error', 'Employee stats error: ' . $e->getMessage());
               return $this->respond([
                    'status' => 'error',
                    'message' => 'Internal server error'
               ], 500);
          }
     }
}
