<?php

namespace App\Controllers;

use App\Models\LeaveModel;
use App\Models\LeaveBalanceModel;
use CodeIgniter\API\ResponseTrait;

class LeaveController extends BaseController
{
     use ResponseTrait;
     protected $leaveModel;
     protected $leaveBalanceModel;
     protected $model;

     protected $helpers = ['form', 'url'];

     public function __construct()
     {
          $this->leaveModel = new LeaveModel();
          $this->leaveBalanceModel = new LeaveBalanceModel();
          $this->model = $this->leaveModel;
     }

     protected $modelName = 'App\Models\LeaveModel';
     protected $format    = 'json';

     /**
      * Apply for leave
      */
     public function apply()
     {
          $payload = $this->request->getJSON(true) ?? $this->request->getPost();

          if (empty($payload['emp_code']) || empty($payload['from_date']) || empty($payload['to_date'])) {
               return $this->failValidationErrors('emp_code, from_date, and to_date are required');
          }

          $start = date('Y-m-d', strtotime($payload['from_date']));
          $end   = date('Y-m-d', strtotime($payload['to_date']));
          if ($start === false || $end === false) {
               return $this->failValidationErrors('Invalid date format');
          }

          // DUPLICATE CHECK: prevent creating same pending leave twice
          $existing = $this->model
               ->where('emp_code', $payload['emp_code'])
               ->where('start_date', $start)
               ->where('end_date', $end)
               ->where('status', 'Pending')
               ->first();

          if ($existing) {
               return $this->failValidationErrors('A pending leave already exists for these dates', 409);
          }

          // compute days
          if (!empty($payload['total_days'])) {
               $days = (float)$payload['total_days'];
          } elseif (!empty($payload['days'])) {
               $days = (float)$payload['days'];
          } else {
               $days = (strtotime($end) >= strtotime($start)) ? (floor((strtotime($end) - strtotime($start)) / 86400) + 1) : 0;
          }

          // normalize leave_type - expanded to include more types
          $allowedLeaveTypes = ['CL', 'EL', 'SL', 'PL', 'LOP', 'ML', 'CO'];

          $map = [
               'casual' => 'CL',
               'cl' => 'CL',
               'casualleave' => 'CL',
               'sick' => 'SL',
               'sl' => 'SL',
               'sickleave' => 'SL',
               'earned' => 'EL',
               'el' => 'EL',
               'earnedleave' => 'EL',
               'privilege' => 'PL',
               'pl' => 'PL',
               'privilegeleave' => 'PL',
               'lop' => 'LOP',
               'lossfpay' => 'LOP',
               'maternity' => 'ML',
               'ml' => 'ML',
               'compensatory' => 'CO',
               'co' => 'CO',
               'comp' => 'CO'
          ];

          $rawType = strtolower(trim($payload['leave_type'] ?? ''));
          $leaveType = $map[$rawType] ?? strtoupper($payload['leave_type'] ?? 'CL');

          // Validate against allowed types, default to CL if invalid
          if (!in_array($leaveType, $allowedLeaveTypes, true)) {
               $leaveType = 'CL';
          }

          $data = [
               'emp_code'   => $payload['emp_code'],
               'leave_type' => $leaveType,
               'start_date' => $start,
               'end_date'   => $end,
               'days'       => $days,
               'reason'     => $payload['reason'] ?? '',
               'status'     => 'Pending',
               'applied_by' => $payload['applied_by'] ?? 'System',
               'applied_on' => date('Y-m-d H:i:s')
          ];

          if ($this->model->insert($data)) {
               return $this->respondCreated([
                    'message' => 'Leave applied successfully',
                    'id' => $this->model->getInsertID(),
                    'leave_type' => $leaveType
               ]);
          }

          return $this->fail($this->model->errors() ?: 'Failed to apply leave');
     }

     /**
      * Update leave application (only pending requests)
      */
     public function update($id = null)
     {
          // determine id
          if (empty($id)) {
               $id = $this->request->getVar('id') ?? $this->request->getUri()->getSegment(3);
               if (empty($id)) {
                    return $this->failValidationErrors('id is required');
               }
          }

          // fetch existing
          $existing = $this->model->find($id);
          if (empty($existing)) {
               return $this->failNotFound('Leave request not found');
          }

          // allow update only when pending
          if (!empty($existing['status']) && strtoupper($existing['status']) !== 'PENDING') {
               return $this->fail('Only pending leave requests can be updated');
          }

          $payload = $this->request->getJSON(true) ?? $this->request->getPost();

          $updateData = [];

          if (!empty($payload['from_date'])) {
               $ts = strtotime($payload['from_date']);
               if ($ts === false) return $this->failValidationErrors('Invalid from_date');
               $updateData['start_date'] = date('Y-m-d', $ts);
          }
          if (!empty($payload['to_date'])) {
               $ts = strtotime($payload['to_date']);
               if ($ts === false) return $this->failValidationErrors('Invalid to_date');
               $updateData['end_date'] = date('Y-m-d', $ts);
          }

          // recompute days if dates or total_days provided
          if (isset($payload['total_days'])) {
               $updateData['days'] = (float)$payload['total_days'];
          } elseif (isset($updateData['start_date']) || isset($updateData['end_date'])) {
               $start = $updateData['start_date'] ?? $existing['start_date'];
               $end = $updateData['end_date'] ?? $existing['end_date'];
               $days = (strtotime($end) >= strtotime($start)) ? (floor((strtotime($end) - strtotime($start)) / 86400) + 1) : 0;
               $updateData['days'] = $days;
          }

          if (isset($payload['leave_type'])) {
               $map = [
                    'casual' => 'CL',
                    'cl' => 'CL',
                    'casualleave' => 'CL',
                    'sick'   => 'SL',
                    'sl' => 'SL',
                    'earned' => 'EL',
                    'el' => 'EL'
               ];
               $rawType = strtolower(trim($payload['leave_type']));
               $lt = $map[$rawType] ?? strtoupper($payload['leave_type']);
               $updateData['leave_type'] = in_array($lt, ['CL', 'EL', 'SL'], true) ? $lt : $existing['leave_type'];
          }

          if (array_key_exists('reason', $payload)) {
               $updateData['reason'] = $payload['reason'];
          }

          // do not modify status/applied_by here
          if (empty($updateData)) {
               return $this->failValidationErrors('No valid fields provided for update');
          }

          if ($this->model->update($id, $updateData)) {
               return $this->respond(['message' => 'Leave request updated successfully']);
          }

          return $this->fail($this->model->errors() ?: 'Failed to update leave request');
     }


     /**
      * Approve leave
      */
     public function approve($id = null)
     {
          $auth = $this->validateAuthorization();
          $approvedBy =   $auth['user_code'];
          if (!$approvedBy) {
               return $this->failValidationErrors('approved_by...... is required');
          }

          if (!isset($id) || empty($id)) {
               $id = $this->request->getVar('id') ?? $this->request->getUri()->getSegment(3);
               if (empty($id)) {
                    return $this->failValidationErrors('id is required');
               }
          }

          if (!$approvedBy) {
               return $this->failValidationErrors('approved_by is required');
          }
          $data = [
               'status' => 'Approved',
               'approved_by' => $approvedBy,
               'approved_on' => date('Y-m-d H:i:s')
          ];
          if ($this->model->update($id, $data)) {
               return $this->respond(['message' => 'Leave approved']);
          }
     }


     // public function approve($id = null)
     // {
     //      $auth = $this->validateAuthorization();
     //      $approvedBy = $auth['user_code'];

     //      if (!$approvedBy) {
     //           return $this->failValidationErrors('approved_by is required');
     //      }

     //      if (!isset($id) || empty($id)) {
     //           $id = $this->request->getVar('id') ?? $this->request->getUri()->getSegment(3);
     //           if (empty($id)) {
     //                return $this->failValidationErrors('id is required');
     //           }
     //      }

     //      $leaveRequest = $this->model->find($id);

     //      if (!$leaveRequest) {
     //           return $this->failNotFound('Leave request not found');
     //      }



     //      $yearStart = date('Y-04-01', strtotime($leaveRequest['start_date']));
     //      $yearEnd = date('Y-03-31', strtotime('+1 year', strtotime($yearStart)));

     //      $deducted = $this->leaveBalanceModel->deductLeaves(
     //           $leaveRequest['emp_code'],
     //           $leaveRequest['leave_type'],
     //           $yearStart,
     //           $leaveRequest['days']
     //      );

     //      if (!$deducted) {
     //           return $this->fail('Not enough leave balance');
     //      }

     //      $data = [
     //           'status' => 'Approved',
     //           'approved_by' => $approvedBy,
     //           'approved_on' => date('Y-m-d H:i:s')
     //      ];

     //      if ($this->model->update($id, $data)) {
     //           return $this->respond(['message' => 'Leave approved']);
     //      }

     //      return $this->fail('Failed to approve leave');
     // }

     public function reject($id = null)
     {
          if (!isset($id) || empty($id)) {
               $id = $this->request->getVar('id') ?? $this->request->getUri()->getSegment(3);
               if (empty($id)) {
                    return $this->failValidationErrors('id is required');
               }
          }
          $rejectedBy = $this->request->getVar('rejected_by');
          $remarks = $this->request->getVar('remarks');
          if (!$rejectedBy) {
               return $this->failValidationErrors('rejected_by is required');
          }
          $data = [
               'status' => 'Rejected',
               'rejected_by' => $rejectedBy,
               'rejected_on' => date('Y-m-d H:i:s'),
               'remarks' => $remarks
          ];
          if ($this->model->update($id, $data)) {
               return $this->respond(['message' => 'Leave rejected']);
          }
     }

     public function pullBack($id = null)
     {
          if (!isset($id) || empty($id)) {
               $id = $this->request->getVar('id') ?? $this->request->getUri()->getSegment(3);
               if (empty($id)) {
                    return $this->failValidationErrors('id is required');
               }
          }
          $data = [
               'status' => 'PulledBack',
               'pulled_back_on' => date('Y-m-d H:i:s')
          ];
          if ($this->model->update($id, $data)) {
               return $this->respond(['message' => 'Leave request pulled back']);
          }
          return $this->fail('Failed to pull back leave');
     }


     /**
      * Fetch all leave requests of employee
      */
     public function myLeaves($emp_code)
     {
          if (empty($emp_code)) {
               return $this->failValidationErrors('emp_code is required');
          }
          return $this->respond($this->model->where('emp_code', $emp_code)->findAll());
     }


     public function doc_pending()
     {
          $auth = $this->validateAuthorization();
          if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) {
               return $auth;
          }
          $user = is_array($auth) ? $auth : [];
          $db = \Config\Database::connect();
          $empTable = 'employees';

          // detect employee table fields
          try {
               $fields = $db->getFieldNames($empTable);
          } catch (\Exception $e) {
               $fields = [];
          }

          // detect columns
          $empNameCol = null;
          foreach (['employee_name', 'emp_name', 'name', 'first_name'] as $c) {
               if (in_array($c, $fields, true)) {
                    $empNameCol = $c;
                    break;
               }
          }

          $empCodeCol = null;
          foreach (['emp_code', 'employee_code', 'employee_id', 'emp_id', 'id'] as $c) {
               if (in_array($c, $fields, true)) {
                    $empCodeCol = $c;
                    break;
               }
          }

          $empTypeCol = in_array('emp_type', $fields, true) ? 'emp_type' : null;

          // If we cannot determine emp_type column, try to fallback by returning empty
          if (empty($empTypeCol)) {
               return $this->respond([], 200);
          }

          // Build query
          $leaveBuilder = $this->model->builder();
          // sort so Pending rows come first (use a string for direction and disable escaping)
          $leaveBuilder->orderBy("CASE WHEN leave_requests.status = 'Pending' THEN 0 ELSE 1 END", 'ASC', false);
          $leaveBuilder->orderBy('leave_requests.created_at', 'DESC');
          // If we can join employees, select name and emp_type and filter by emp_type = 'DOCTOR'
          if (!empty($empCodeCol) && !empty($empNameCol)) {
               // select leave fields + employee name + emp_type
               if ($empNameCol === 'first_name') {
                    $lastExists = in_array('last_name', $fields, true);
                    $nameExpr = $lastExists ? "CONCAT(e.first_name, ' ', e.last_name)" : "e.first_name";
                    $leaveBuilder->select("leave_requests.*, {$nameExpr} as employee_name, e.{$empTypeCol} as emp_type", false);
               } else {
                    $leaveBuilder->select("leave_requests.*, e.{$empNameCol} as employee_name, e.{$empTypeCol} as emp_type", false);
               }

               $leaveBuilder->join("{$empTable} e", "e.{$empCodeCol} = leave_requests.emp_code", 'left');
               $leaveBuilder->where("e.{$empTypeCol}", 'DOCTOR');
          } else {
               // fallback: fetch emp_code list from employees where emp_type='DOCTOR' then filter leave_requests
               $codes = $db->table($empTable)->select($empCodeCol ?? 'employee_code')->where($empTypeCol, 'DOCTOR')->get()->getResultArray();
               $codeColName = $empCodeCol ?? 'employee_code';
               $empCodes = array_column($codes, $codeColName);
               if (empty($empCodes)) return $this->respond([], 200);
               $leaveBuilder->select('leave_requests.*');
               $leaveBuilder->whereIn('leave_requests.emp_code', $empCodes);
          }

          $rows = $leaveBuilder->get()->getResultArray();

          // ensure employee_name / emp_type present in response
          foreach ($rows as &$r) {
               if (!isset($r['employee_name']) || $r['employee_name'] === null) $r['employee_name'] = $r['emp_code'];
               if (!isset($r['emp_type']) || $r['emp_type'] === null) $r['emp_type'] = 'DOCTOR';
          }

          return $this->respond($rows);
     }
}
