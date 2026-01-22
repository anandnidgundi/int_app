<?php

namespace App\Controllers;

use App\Models\LeaveModel;
use App\Models\LeaveBalanceModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Files\File;

class LeaveManagementController extends BaseController
{
     use ResponseTrait;
     protected $leaveModel;
     protected $leaveBalanceModel; 

     protected $helpers = ['form', 'url'];

     public function __construct()
     {
          $this->leaveModel = new LeaveModel();
          $this->leaveBalanceModel = new LeaveBalanceModel();
     }

     // GET /leave-balances/{empCode}?year={year}
     // public function getLeaveBalances($empCode = null)
     // {
     //      $year = $this->request->getGet('year');
     //      if (!$empCode || !$year) return $this->failValidationErrors('empCode and year required');
     //      $rows = $this->leaveBalanceModel->where('emp_code', $empCode)->where('year_start', $year)->findAll();
     //      return $this->respond($rows);
     // }

     public function getLeaveBalances($empCode = null)
{
          try {
               $year = $this->request->getGet('year');

               // ✅ Handle both formats: "2025-2026" and "2025"
               if (preg_match('/^\d{4}$/', $year)) {
                    // If just year like "2025", convert to year_start
                    $year_start = $year . '-04-01';
               } elseif (preg_match('/^(\d{4})-(\d{4})$/', $year, $matches)) {
                    // If "2025-2026", extract start year
                    $year_start = $matches[1] . '-04-01';
               } else {
                    // If already in "2025-04-01" format
                    $year_start = $year;
               }

               $rows = $this->leaveBalanceModel
                    ->where('emp_code', $empCode)
                    ->where('year_start', $year_start)
                    ->findAll();
        
        // If we have results, get employee name separately
        if (!empty($rows)) {
            $db = \Config\Database::connect();
            $employee = $db->table('employees')
                ->select('employee_name')
                ->where('employee_code', $empCode)
                ->get()
                ->getRowArray();
            
            // Add employee name to each row
            foreach ($rows as &$row) {
                $row['employee_name'] = $employee['employee_name'] ?? null;
            }
        }
        
        return $this->respond($rows);
    } catch (\Exception $e) {
        log_message('error', 'Error in getLeaveBalances: ' . $e->getMessage());
        return $this->fail('Error fetching leave balances: ' . $e->getMessage(), 500);
    }
}
     public function getAllEmployeeBalances()
          {
          $year = $this->request->getGet('year');
          if (!$year) return $this->failValidationErrors('year required');

          // Convert year to financial year start date
          $year_start = $year . '-04-01';

          $rows = $this->leaveBalanceModel
               ->select('leave_balances.*, employees.employee_name')
               ->join('employees', 'employees.employee_code = leave_balances.emp_code', 'left')
               ->where('leave_balances.year_start', $year_start)
               ->findAll();
          
          return $this->respond($rows);
          }
     // GET /leave-balances/all?year={year}
     // public function getAllEmployeeBalances()
     // {
     //      $year = $this->request->getGet('year');
     //      if (!$year) return $this->failValidationErrors('year required');
     //      $rows = $this->leaveBalanceModel->where('year_start', $year)->findAll();
     //      return $this->respond($rows);
     // }

     // POST /leave-balances/credit
    
// POST /leave-balances/credit
public function creditLeaves()
{
    $data = $this->request->getJSON(true);
    if (!$data || empty($data['emp_code']) || empty($data['leave_type']) || empty($data['year_start']) || empty($data['year_end']) || empty($data['total_leaves'])) {
        return $this->failValidationErrors('emp_code, leave_type, year_start, year_end, total_leaves required');
    }

    // Check if the record already exists
    $existing = $this->leaveBalanceModel
        ->where('emp_code', $data['emp_code'])
        ->where('leave_type', $data['leave_type'])
        ->where('year_start', $data['year_start'])
        ->first();

    if ($existing) {
        // Update existing record instead of inserting
        $updateData = [
            'total_leaves' => $data['total_leaves'],
            'remaining_leaves' => $data['total_leaves'] - ($existing['used_leaves'] ?? 0)
        ];
        $ok = $this->leaveBalanceModel->update($existing['id'], $updateData);
        return $ok 
            ? $this->respond(['id' => $existing['id'], 'message' => 'Leave balance updated'])
            : $this->fail('Failed to update leave balance');
    }

    // Insert new record
    $id = $this->leaveBalanceModel->addLeaveBalance(
        $data['emp_code'], 
        $data['leave_type'], 
        $data['year_start'], 
        $data['year_end'], 
        $data['total_leaves']
    );
    
    return $id 
        ? $this->respondCreated(['id' => $id, 'message' => 'Leave balance created']) 
        : $this->fail('Failed to credit leaves');
}

     // POST /leave-balances/bulk-credit
     public function bulkCreditLeaves()
     {
          $bulkData = $this->request->getJSON(true);
          if (!is_array($bulkData)) return $this->failValidationErrors('Bulk data required');
          $results = [];
          foreach ($bulkData as $data) {
               $id = $this->leaveBalanceModel->addLeaveBalance($data['emp_code'], $data['leave_type'], $data['year_start'], $data['year_end'], $data['total_leaves']);
               $results[] = ['emp_code' => $data['emp_code'], 'result' => $id ? 'success' : 'fail'];
          }
          return $this->respond($results);
     }

     // POST /leave-balances/deduct
     public function deductLeaves()
     {
          $data = $this->request->getJSON(true);
          if (!$data || empty($data['emp_code']) || empty($data['leave_type']) || empty($data['year_start']) || empty($data['leavesToDeduct'])) {
               return $this->failValidationErrors('emp_code, leave_type, year_start, leavesToDeduct required');
          }
          $ok = $this->leaveBalanceModel->deductLeaves($data['emp_code'], $data['leave_type'], $data['year_start'], $data['leavesToDeduct']);
          return $ok ? $this->respond(['result' => 'success']) : $this->fail('Not enough leave balance');
     }

     // GET /leave-requests
    public function getLeaveRequests()
     {
     $filters = $this->request->getGet();
     $builder = $this->leaveModel
          ->select('leave_requests.*, employees.employee_name')
          ->join('employees', 'employees.employee_code = leave_requests.emp_code', 'left');
     
     foreach ($filters as $key => $val) {
          if (in_array($key, $this->leaveModel->getAllowedFields())) {
               $builder = $builder->where('leave_requests.' . $key, $val);
          }
     }
     
     $rows = $builder->findAll();
     return $this->respond($rows);
     }

     // POST /leave-requests
     public function createLeaveRequest()
     {
          $data = $this->request->getJSON(true);
          if (!$data || empty($data['emp_code']) || empty($data['leave_type']) || empty($data['start_date']) || empty($data['end_date'])) {
               return $this->failValidationErrors('emp_code, leave_type, start_date, end_date required');
          }
          $data['status'] = 'Pending';
          $data['applied_on'] = date('Y-m-d H:i:s');
          $id = $this->leaveModel->insert($data);
          return $id ? $this->respondCreated(['id' => $id]) : $this->fail('Failed to create leave request');
     }

     // PUT /leave-requests/{id}/approve
    public function approveLeaveRequest($id = null)
     {
     if (!$id) return $this->failValidationErrors('Leave request ID required');

     // Get leave request details
     $leaveRequest = $this->leaveModel->find($id);
     if (!$leaveRequest) {
          return $this->fail('Leave request not found');
     }

     // Check if already processed
     if ($leaveRequest['status'] !== 'Pending') {
          return $this->fail('Leave request already processed');
     }

     $empCode = $leaveRequest['emp_code'];
     $leaveType = $leaveRequest['leave_type'];
     $days = $leaveRequest['days'];
     $startDate = $leaveRequest['start_date'];
     
     // Get financial year for the leave start date
     $startYear = date('Y', strtotime($startDate));
     $startMonth = date('m', strtotime($startDate));
     
     // Calculate financial year (April to March)
     if ($startMonth >= 4) {
          $yearStart = $startYear . '-04-01';
          $yearEnd = ($startYear + 1) . '-03-31';
     } else {
          $yearStart = ($startYear - 1) . '-04-01';
          $yearEnd = $startYear . '-03-31';
     }

     // Only check balance for PL, CL, SL (skip LOP)
     if (in_array($leaveType, ['PL', 'CL', 'SL'])) {
          // Check leave balance
          $leaveBalance = $this->leaveBalanceModel
               ->where('emp_code', $empCode)
               ->where('leave_type', $leaveType)
               ->where('year_start', $yearStart)
               ->first();

          if (!$leaveBalance) {
               return $this->fail('No leave balance found for this employee and leave type');
          }

          $remainingLeaves = $leaveBalance['remaining_leaves'];
          
          if ($remainingLeaves < $days) {
               return $this->fail("Insufficient leave balance. Available: {$remainingLeaves}, Requested: {$days}");
          }

          // Deduct leaves from balance
          $deductSuccess = $this->leaveBalanceModel->deductLeaves(
               $empCode,
               $leaveType,
               $yearStart,
               $days
          );

          if (!$deductSuccess) {
               return $this->fail('Failed to deduct leaves from balance');
          }

          // Record transaction in leave_transactions
          $db = \Config\Database::connect();
          $db->table('leave_transactions')->insert([
               'emp_code' => $empCode,
               'leave_type' => $leaveType,
               'transaction_date' => date('Y-m-d H:i:s'),
               'transaction_type' => 'APPROVED',
               'leaves_deducted' => $days,
               'remarks' => "Leave request ID: {$id} approved"
          ]);
     }

     // Update leave request status
     $remarks = $this->request->getJSON(true)['remarks'] ?? '';
     $data = [
          'status' => 'Approved',
          'approved_by' => $this->request->user_code ?? 'System',
          'approved_on' => date('Y-m-d H:i:s'),
          'remarks' => $remarks
     ];
     
     $ok = $this->leaveModel->update($id, $data);
     
     return $ok 
          ? $this->respond([
               'result' => 'success',
               'message' => 'Leave approved and balance updated'
          ]) 
          : $this->fail('Failed to approve leave request');
     }

     // PUT /leave-requests/{id}/reject
     public function rejectLeaveRequest($id = null)
     {
          $remarks = $this->request->getJSON(true)['remarks'] ?? '';
          $data = [
               'status' => 'Rejected',
               'rejected_by' => $this->request->user_code ?? 'System',
               'rejected_on' => date('Y-m-d H:i:s'),
               'remarks' => $remarks
          ];
          $ok = $this->leaveModel->update($id, $data);
          return $ok ? $this->respond(['result' => 'success']) : $this->fail('Failed to reject');
     }

     // PUT /leave-requests/{id}/cancel
     public function cancelLeaveRequest($id = null)
     {
          $reason = $this->request->getJSON(true)['reason'] ?? '';
          $data = [
               'status' => 'PulledBack',
               'pulled_back_on' => date('Y-m-d H:i:s'),
               'remarks' => $reason
          ];
          $ok = $this->leaveModel->update($id, $data);
          return $ok ? $this->respond(['result' => 'success']) : $this->fail('Failed to cancel');
     }

     // GET /leave-requests/history/{empCode}?year={year}
     public function getLeaveHistory($empCode = null)
     {
          $year = $this->request->getGet('year');
          if (!$empCode || !$year) return $this->failValidationErrors('empCode and year required');
          $rows = $this->leaveModel->where('emp_code', $empCode)->where('YEAR(start_date)', $year)->findAll();
          return $this->respond($rows);
     }

     // GET /leave-requests/pending/{managerId}
     public function getPendingRequests($managerId = null)
     {
          if (!$managerId) return $this->failValidationErrors('managerId required');
          $rows = $this->leaveModel->where('status', 'Pending')->where('approved_by', $managerId)->findAll();
          return $this->respond($rows);
     }

     // GET /leave-transactions/{empCode}?start={startDate}&end={endDate}
     public function getLeaveTransactions($empCode = null)
     {
          $start = $this->request->getGet('start');
          $end = $this->request->getGet('end');
          if (!$empCode || !$start || !$end) return $this->failValidationErrors('empCode, start, end required');
          $db = \Config\Database::connect();
          $rows = $db->table('leave_transactions')
               ->where('emp_code', $empCode)
               ->where('transaction_date >=', $start)
               ->where('transaction_date <=', $end)
               ->get()->getResultArray();
          return $this->respond($rows);
     }

     // GET /leave-transactions/monthly/{empCode}?year={year}&month={month}
     public function getMonthlyLeaveReport($empCode = null)
     {
          $year = $this->request->getGet('year');
          $month = $this->request->getGet('month');
          
          if (!$empCode || !$year || !$month) {
               return $this->failValidationErrors('empCode, year, month required');
          }
          
          $db = \Config\Database::connect();
          $rows = $db->table('leave_transactions')
               ->where('emp_code', $empCode)
               ->where('YEAR(transaction_date)', $year)
               ->where('MONTH(transaction_date)', $month)
               ->get()->getResultArray();
          
          return $this->respond([
               'status' => true,
               'data' => $rows
          ]);
     }

     // GET /leave-statement/{empCode}?fy={financialYear}
     // public function getLeaveStatement($empCode = null)
     // {
     //      $fy = $this->request->getGet('fy');
     //      if (!$empCode || !$fy) return $this->failValidationErrors('empCode and fy required');
     //      $rows = $this->leaveBalanceModel->where('emp_code', $empCode)->where('year_start', $fy)->findAll();
     //      return $this->respond($rows);
     // }


         public function getLeaveStatement($empCode = null)
     {
          $fy = $this->request->getGet('fy');
          if (!$empCode || !$fy) {
               return $this->failValidationErrors('empCode and fy required');
          }
          
          // Parse financial year "2025-2026" to get start year
          $yearParts = explode('-', $fy);
          $startYear = $yearParts[0];
          
          // Filter by emp_code and year part of year_start
          $rows = $this->leaveBalanceModel
               ->where('emp_code', $empCode)
               ->where('YEAR(year_start)', $startYear)
               ->findAll();
          
          // Debug log
          log_message('error', 'Leave Statement Query: emp_code=' . $empCode . ', year=' . $startYear);
          log_message('error', 'Leave Statement Result: ' . json_encode($rows));
          
          return $this->respond([
               'status' => true,
               'data' => $rows
          ]);
     }

     // GET /leave-types
     public function getLeaveTypes()
     {
          // You may want to fetch from a table, here is a static example
          $types = [
               ['code' => 'CL', 'name' => 'Casual Leave'],
               ['code' => 'EL', 'name' => 'Earned Leave'],
               ['code' => 'SL', 'name' => 'Sick Leave'],
               ['code' => 'LOP', 'name' => 'Loss of Pay']
          ];
          return $this->respond($types);
     }

     // GET /leave-reports/export?format=csv
     public function exportLeaveReport()
     {
          $format = $this->request->getGet('format') ?? 'csv';
          $filters = $this->request->getGet();
          $builder = $this->leaveModel;
          foreach ($filters as $key => $val) {
               if (in_array($key, $this->leaveModel->getAllowedFields())) {
                    $builder = $builder->where($key, $val);
               }
          }
          $rows = $builder->findAll();

          if ($format === 'csv') {
               $csv = [];
               $header = array_keys($rows[0] ?? []);
               $csv[] = implode(',', $header);
               foreach ($rows as $row) {
                    $csv[] = implode(',', array_map(function ($v) {
                         return '"' . str_replace('"', '""', $v) . '"';
                    }, $row));
               }
               $response = implode("\n", $csv);
               return $this->response->setHeader('Content-Type', 'text/csv')->setBody($response);
          }
          // Add Excel or other formats as needed
          return $this->respond($rows);
     }
}
