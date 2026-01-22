<?php

namespace App\Controllers;

use App\Models\ShiftListsModel;
use App\Controllers\BaseController;

class EmployeeShiftMaster extends BaseController
{
     protected $model;

     public function __construct()
     {
          $this->model = new ShiftListsModel();
     }

     public function getEmployeeShifts()
     {
          $shifts = $this->model
               ->where('status', 'A')
               ->orderBy('ShiftName', 'ASC')
               ->findAll();

          return $this->response->setJSON([
               'status' => true,
               'data' => $shifts
          ]);
     }

     public function createEmployeeShift()
     {
          $auth = $this->validateAuthorization();
          if (!$auth) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Unauthorized'
               ])->setStatusCode(401);
          }
          $user = $auth['emp_code'];
          $json = $this->request->getJSON(true);

          // Validate required fields
          if (empty($json['ShiftName']) || empty($json['ShiftStart']) || empty($json['ShiftEnd'])) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'ShiftName, ShiftStart, and ShiftEnd are required'
               ])->setStatusCode(400);
          }

          // Calculate working hours if not provided
          $workingHoursFullDay = $json['WorkingsHoursToBeConsiderdFullDay'] ?? null;
          $workingHoursHalfDay = $json['WorkingsHoursToBeConsiderdHalfDay'] ?? null;

          // If full day hours not provided, calculate from shift times
          if (empty($workingHoursFullDay)) {
               $start = strtotime($json['ShiftStart']);
               $end = strtotime($json['ShiftEnd']);
               $diff = ($end - $start) / 3600; // Convert to hours
               $workingHoursFullDay = round($diff, 2);
          }

          // If half day hours not provided, set to half of full day
          if (empty($workingHoursHalfDay)) {
               $workingHoursHalfDay = round($workingHoursFullDay / 2, 2);
          }

          $data = [
               'ShiftName'                           => $json['ShiftName'],
               'ShiftStart'                          => $json['ShiftStart'],
               'ShiftEnd'                            => $json['ShiftEnd'],
               'WorkingsHoursToBeConsiderdFullDay'   => $workingHoursFullDay,
               'WorkingsHoursToBeConsiderdHalfDay'   => $workingHoursHalfDay,
               'late_login_applicable'               => $json['late_login_applicable'] ?? 'N',
               'emp_type'                            => $json['emp_type'] ?? null,
               'status'                              => $json['status'] ?? 'A',
               'split_shift'                         => $json['split_shift'] ?? 'N'
          ];

          if ($this->model->insert($data)) {
               return $this->response->setJSON([
                    'status' => true,
                    'message' => 'Employee shift created successfully',
                    'shift_id' => $this->model->getInsertID()
               ]);
          }

          return $this->response->setJSON([
               'status' => false,
               'message' => 'Failed to create employee shift',
               'errors' => $this->model->errors()
          ])->setStatusCode(400);
     }

     public function getEmployeeShiftById($id)
     {
          $shift = $this->model->find($id);
          if ($shift) {
               return $this->response->setJSON([
                    'status' => true,
                    'data' => $shift
               ]);
          }

          return $this->response->setJSON([
               'status' => false,
               'message' => 'Employee shift not found'
          ])->setStatusCode(404);
     }

     public function updateEmployeeShiftById($id)
     {
          $auth = $this->validateAuthorization();
          if (!$auth) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Unauthorized'
               ])->setStatusCode(401);
          }
          $user = $auth['emp_code'];

          $json = $this->request->getJSON(true);

          // Check if shift exists
          $existing = $this->model->find($id);
          if (!$existing) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Employee shift not found'
               ])->setStatusCode(404);
          }

          // Build update data (only include provided fields)
          $data = [];

          if (isset($json['ShiftName'])) {
               $data['ShiftName'] = $json['ShiftName'];
          }
          if (isset($json['ShiftStart'])) {
               $data['ShiftStart'] = $json['ShiftStart'];
          }
          if (isset($json['ShiftEnd'])) {
               $data['ShiftEnd'] = $json['ShiftEnd'];
          }
          if (isset($json['WorkingsHoursToBeConsiderdFullDay'])) {
               $data['WorkingsHoursToBeConsiderdFullDay'] = $json['WorkingsHoursToBeConsiderdFullDay'];
          }
          if (isset($json['WorkingsHoursToBeConsiderdHalfDay'])) {
               $data['WorkingsHoursToBeConsiderdHalfDay'] = $json['WorkingsHoursToBeConsiderdHalfDay'];
          }
          if (isset($json['late_login_applicable'])) {
               $data['late_login_applicable'] = $json['late_login_applicable'];
          }
          if (isset($json['emp_type'])) {
               $data['emp_type'] = $json['emp_type'];
          }
          if (isset($json['status'])) {
               $data['status'] = $json['status'];
          }
          if (isset($json['split_shift'])) {
               $data['split_shift'] = $json['split_shift'];
          }

          // Recalculate working hours if shift times changed
          if (isset($data['ShiftStart']) && isset($data['ShiftEnd']) && !isset($json['WorkingsHoursToBeConsiderdFullDay'])) {
               $start = strtotime($data['ShiftStart']);
               $end = strtotime($data['ShiftEnd']);
               $diff = ($end - $start) / 3600;
               $data['WorkingsHoursToBeConsiderdFullDay'] = round($diff, 2);
               $data['WorkingsHoursToBeConsiderdHalfDay'] = round($diff / 2, 2);
          }

          if ($this->model->update($id, $data)) {
               return $this->response->setJSON([
                    'status' => true,
                    'message' => 'Employee shift updated successfully'
               ]);
          }

          return $this->response->setJSON([
               'status' => false,
               'message' => 'Failed to update employee shift',
               'errors' => $this->model->errors()
          ])->setStatusCode(400);
     }

     public function deleteEmployeeShiftById($id)
     {
          $auth = $this->validateAuthorization();
          if (!$auth) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Unauthorized'
               ])->setStatusCode(401);
          }
          $user = $auth['emp_code'];

          $db = \Config\Database::connect();

          // Check if shift exists
          $existing = $db->table('shiftslist')->where('id', $id)->get()->getRowArray();

          if (!$existing) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Employee shift not found'
               ])->setStatusCode(404);
          }

          // Check if already deleted/inactive
          if ($existing['status'] === 'I') {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Employee shift is already deleted'
               ])->setStatusCode(400);
          }

          // Change status to 'I' for inactive instead of deleting
          $result = $db->table('shiftslist')
               ->where('id', $id)
               ->update(['status' => 'I']);

          if ($result) {
               return $this->response->setJSON([
                    'status' => true,
                    'message' => 'Employee shift deleted successfully'
               ]);
          }

          return $this->response->setJSON([
               'status' => false,
               'message' => 'Failed to delete employee shift'
          ])->setStatusCode(500);
     }
     public function getAllEmployeeShifts()
     {
          // Get all shifts including inactive ones
          $shifts = $this->model->findAll();
          return $this->response->setJSON([
               'status' => true,
               'data' => $shifts
          ]);
     }

     public function getEmployeeShiftsByType($empType)
     {
          // Get shifts by employee type
          $shifts = $this->model
               ->where('emp_type', $empType)
               ->where('status', 'A')
               ->findAll();

          return $this->response->setJSON([
               'status' => true,
               'data' => $shifts
          ]);
     }
}
