<?php

namespace App\Models;

use CodeIgniter\Model;

class LeaveBalanceModel extends Model
{
     protected $table = 'leave_balances';
     protected $primaryKey = 'id';
     protected $allowedFields = [
          'emp_code',
          'leave_type',
          'year_start',
          'year_end', 
          'total_leaves',
          'used_leaves',
          'remaining_leaves',
          'created_at',
          'updated_at'
     ];

     /**
      * Get leave balance for an employee for a specific financial year.
      */
     public function getLeaveBalance($empCode, $leaveType, $yearStart)
     {
          return $this->where('emp_code', $empCode)
               ->where('leave_type', $leaveType)
               ->where('year_start', $yearStart)
               ->first();
     }

     /**
      * Deduct leaves from the balance.
      */
     public function deductLeaves($empCode, $leaveType, $yearStart, $leavesToDeduct)
     {
          $leaveBalance = $this->getLeaveBalance($empCode, $leaveType, $yearStart);

          if (!$leaveBalance) {
               return false; // No leave balance found
          }

          $remainingLeaves = $leaveBalance['remaining_leaves'] - $leavesToDeduct;

          if ($remainingLeaves < 0) {
               return false; // Not enough leaves available
          }

          $this->update($leaveBalance['id'], [
               'used_leaves' => $leaveBalance['used_leaves'] + $leavesToDeduct,
               'remaining_leaves' => $remainingLeaves
          ]);

          return true;
     }

     /**
      * Add leave balance for a new financial year.
      */
     public function addLeaveBalance($empCode, $leaveType, $yearStart, $yearEnd, $totalLeaves)
     {
          return $this->insert([
               'emp_code' => $empCode,
               'leave_type' => $leaveType,
               'year_start' => $yearStart,
               'year_end' => $yearEnd,
               'total_leaves' => $totalLeaves,
               'used_leaves' => 0,
               'remaining_leaves' => $totalLeaves
          ]);
     }
}
