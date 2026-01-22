<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTime;
use Exception;

class DoctorsShiftMasterModel extends Model
{
     protected $table            = 'doctors_shift_master';
     protected $primaryKey       = 'id';
     protected $useAutoIncrement = true;
     protected $returnType       = 'array';
     protected $protectFields    = true;

     // columns that can be set by insert/update
     protected $allowedFields = [
          'shift_name',
          'in_time',
          'out_time',
          'total_hours',
          'total_minutes',
          'grace_in',
          'grace_out',
          'exemption_limit',
          'modified_by',
          'status',
          'created_on',
          'created_by',
          'modified_dtm',
          'split_shift'
     ];

     // use CI4 timestamps but map to your existing column names
     protected $useTimestamps = true;
     protected $dateFormat    = 'datetime';
     protected $createdField  = 'created_on';
     protected $updatedField  = 'modified_dtm';

     // basic validation rules
     protected $validationRules = [
          'shift_name'   => 'required|max_length[100]',
          'in_time'      => 'required',
          'out_time'     => 'required',
          'grace_in'     => 'permit_empty|integer',
          'grace_out'    => 'permit_empty|integer',
          'exemption_limit' => 'permit_empty|decimal',
          'status'       => 'required|in_list[A,I]',
     ];

     protected $skipValidation = false;
}
