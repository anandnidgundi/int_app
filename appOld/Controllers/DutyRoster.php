<?php

namespace App\Controllers;


use App\Models\DutyRosterModel;

use App\Models\ShiftListsModel;
use CodeIgniter\API\ResponseTrait;
use App\Controllers\BaseController;
use App\Models\UserModel;

class DutyRoster extends BaseController
{
     use ResponseTrait;
     protected $dutyRosterModel;

     protected $shiftListsModel;

     public function __construct()
     {
          $this->dutyRosterModel = new DutyRosterModel();
          $this->shiftListsModel = new ShiftListsModel();
     }

     public function index()
     {
          $data = $this->dutyRosterModel->findAll();
          return $this->respond(['status' => 200, 'error' => null, 'data' => $data]);
     }


     public function createDutyRoster()
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['user_code'];
               $json = $this->request->getJSON(true);

               // Validate emp_id and roster array
               if (empty($json['emp_id']) || empty($json['roster']) || !is_array($json['roster'])) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'emp_id and roster array are required'
                    ], 400);
               }

               $emp_id = $json['emp_id'];
               $roster = $json['roster'];
               $month = $json['month'] ?? null;

               $dutyRosterModel = new \App\Models\DutyRosterModel();
               $success = [];
               $failed = [];

               foreach ($roster as $entry) {
                    // Prepare data for each day
                    $data = [
                         'emp_id' => $emp_id,
                         'shift_id' => $entry['shift_id'],
                         'attendance_date' => $entry['date'],
                         'custom_weekoff_date' => ($entry['weekoff'] === true) ? $entry['date'] : null,
                         'createdBy' => $user,
                         'createdDTM' => date('Y-m-d H:i:s')
                    ];

                    // Check if record exists for emp_id + attendance_date
                    $existing = $dutyRosterModel
                         ->where('emp_id', $emp_id)
                         ->where('attendance_date', $entry['date'])
                         ->first();

                    if ($existing) {
                         // Update existing record
                         $updateData = $data;
                         $updateData['updatedBy'] = $user;
                         $updateData['updatedDTM'] = date('Y-m-d H:i:s');
                         if ($dutyRosterModel->update($existing['id'], $updateData)) {
                              $success[] = ['date' => $entry['date'], 'action' => 'updated'];
                         } else {
                              $failed[] = ['date' => $entry['date'], 'action' => 'update_failed'];
                         }
                    } else {
                         // Insert new record
                         if ($dutyRosterModel->insert($data)) {
                              $success[] = ['date' => $entry['date'], 'action' => 'created'];
                         } else {
                              $failed[] = ['date' => $entry['date'], 'action' => 'create_failed'];
                         }
                    }
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'Duty roster processed',
                    'success' => $success,
                    'failed' => $failed
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }

     // public function createDutyRoster()
     // {
     //      $userDetails = $this->validateAuthorization();
     //      $user = $userDetails['user_code'];

     //      $json = $this->request->getJSON(true);
     //      $input = [
     //           'emp_id' => $json['emp_id'],
     //           'shift_id' => $json['shift_id'],
     //           'attendance_date' => $json['attendance_date'],
     //           'custom_weekoff_date' => $json['custom_weekoff_date'] ?? null,
     //           'createdBy' => $user,
     //           'createdDTM' => date('Y-m-d H:i:s')
     //      ];
     //      $checkExisting = $this->dutyRosterModel->where('emp_id', $input['emp_id'])
     //           ->where('attendance_date', $input['attendance_date'])
     //           ->first();

     //      if ($checkExisting) {
     //           return $this->respond(['status' => 409, 'error' => 'Conflict', 'messages' => 'Duty roster entry already exists']);
     //      }

     //      $this->dutyRosterModel->insert($input);

     //      return $this->respondCreated(['status' => 201, 'error' => null, 'data' => $input]);
     // }

     public function updateDutyRoster($id = null)
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $json = $this->request->getJSON(true);
          $input = [
               'emp_id' => $json['emp_id'],
               'shift_id' => $json['shift_id'],
               'attendance_date' => $json['attendance_date'],
               'custom_weekoff_date' => $json['custom_weekoff_date'] ?? null,
               'updatedBy' => $user,
               'updatedDTM' => date('Y-m-d H:i:s')
          ];

          $existingEntry = $this->dutyRosterModel->find($id);
          if (!$existingEntry) {
               return $this->respond(['status' => 404, 'error' => 'Not Found', 'messages' => 'Duty roster entry not found']);
          }

          // Check for duplicate entry excluding the current record
          $duplicateCheck = $this->dutyRosterModel->where('emp_id', $input['emp_id'])
               ->where('attendance_date', $input['attendance_date'])
               ->where('id !=', $id)
               ->first();

          if ($duplicateCheck) {
               return $this->respond(['status' => 409, 'error' => 'Conflict', 'messages' => 'Another duty roster entry with the same employee and date already exists']);
          }

          $this->dutyRosterModel->update($id, $input);

          return $this->respond(['status' => 200, 'error' => null, 'data' => $input]);
     }


     public function updateDutyRosterBulk()
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['user_code'];
               $json = $this->request->getJSON(true);

               if (empty($json['emp_id']) || empty($json['roster']) || !is_array($json['roster'])) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'emp_id and roster array are required'
                    ], 400);
               }

               $emp_id = $json['emp_id'];
               $roster = $json['roster'];
               $success = [];
               $failed = [];

               foreach ($roster as $entry) {
                    $updateData = [
                         'shift_id' => $entry['shift_id'],
                         'attendance_date' => $entry['date'],
                         'custom_weekoff_date' => ($entry['weekoff'] === true) ? $entry['date'] : null,
                         'updatedBy' => $user,
                         'updatedDTM' => date('Y-m-d H:i:s')
                    ];

                    $existing = $this->dutyRosterModel
                         ->where('emp_id', $emp_id)
                         ->where('attendance_date', $entry['date'])
                         ->first();

                    if ($existing) {
                         if ($this->dutyRosterModel->update($existing['id'], $updateData)) {
                              $success[] = ['date' => $entry['date'], 'action' => 'updated'];
                         } else {
                              $failed[] = ['date' => $entry['date'], 'action' => 'update_failed'];
                         }
                    } else {
                         $failed[] = ['date' => $entry['date'], 'action' => 'not_found'];
                    }
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'Duty roster bulk update processed',
                    'success' => $success,
                    'failed' => $failed
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }

     public function getDutyRosters()
     {
          $userDetails = $this->validateAuthorization();
          $data = $this->dutyRosterModel->findAll();
          return $this->respond(['status' => 200, 'error' => null, 'data' => $data]);
     }

     public function getDutyRosterById($id = null)
     {
          $userDetails = $this->validateAuthorization();
          $data = $this->dutyRosterModel->find($id);
          if ($data) {
               return $this->respond(['status' => 200, 'error' => null, 'data' => $data]);
          } else {
               return $this->respond(['status' => 404, 'error' => 'Not Found', 'messages' => 'Duty roster entry not found']);
          }
     }

     public function getDutyRosterByEmpIdAndSelectedMonth($empId = null, $month = null)
     {
          $userDetails = $this->validateAuthorization();
          if (!$empId || !$month) {
               return $this->respond(['status' => 400, 'error' => 'Bad Request', 'messages' => 'emp_id and month are required']);
          }

          // Validate month format YYYY-MM
          if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
               return $this->respond(['status' => 400, 'error' => 'Bad Request', 'messages' => 'Invalid month format. Use YYYY-MM']);
          }

          // Get start and end dates of the month
          $startDate = $month . '-01';
          $endDate = date('Y-m-t', strtotime($startDate)); // Last day of the month

          $data = $this->dutyRosterModel
               ->where('emp_id', $empId)
               ->where('attendance_date >=', $startDate)
               ->where('attendance_date <=', $endDate)
               ->orderBy('attendance_date', 'ASC')
               ->findAll();

          return $this->respond(['status' => 200, 'error' => null, 'data' => $data]);
     }

     // shifts list
     public function getShifts()
     {
          $userDetails = $this->validateAuthorization();
          $data = $this->shiftListsModel->where('status', 'A')->findAll();
          return $this->respond(['status' => 200, 'error' => null, 'data' => $data]);
     }

     public function getEmpByIdNew($id)
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $dutyRosterModel = new DutyRosterModel();
          $user = $dutyRosterModel->getEmpByIdNew($id);

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Users not found'], 404);
          }
     }

     public function getEmployeeAttendance()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['emp_code'];

          $json = $this->request->getJSON(true);

          // Get year and month from query parameters
          $employee_code = $json['employee_code'] ?? null;
          $selectedMonth = $json['selectedMonth'] ?? null;

          if (empty($employee_code) || empty($selectedMonth)) {
               return $this->respond([
                    'status' => false,
                    'message' => 'employee_code and selectedMonth are required'
               ], 400);
          }

          $dutyRosterModel = new DutyRosterModel();
          $attendanceData = $dutyRosterModel->getEmployeeAttendance($employee_code, $selectedMonth);

          if ($attendanceData) {
               return $this->respond([
                    'status' => true,
                    'data' => $attendanceData,
                    'monthYear' => $selectedMonth,
                    'total_records' => count($attendanceData)
               ], 200);
          } else {
               return $this->respond([
                    'status' => false,
                    'message' => "No attendance data found for {$selectedMonth}"
               ], 404);
          }
     }
}
