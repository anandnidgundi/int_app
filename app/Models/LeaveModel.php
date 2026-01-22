<?php

namespace App\Models;

use CodeIgniter\Model;

class LeaveModel extends Model
{
     protected $table = 'leave_requests';
     protected $primaryKey = 'id';
     protected $allowedFields = [
          'emp_code',
          'leave_type',
          'start_date',
          'end_date',
          'days',
          'reason', 
          'status',
          'applied_by',
          'applied_on',
          'approved_by',
          'approved_on',
          'rejected_by',
          'rejected_on',
          'pulled_back_on',
          'remarks'
     ];

     public function getAllowedFields()
     {
          return $this->allowedFields;
     }
}
