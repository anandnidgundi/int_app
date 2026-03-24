<?php

namespace App\Models;

use CodeIgniter\Model;

class SufalamLoginModel extends Model
{
     protected $table            = 'sufalam_login_det';
     protected $primaryKey       = 'id';
     protected $useAutoIncrement = true;
     protected $returnType       = 'array';
     protected $useSoftDeletes   = false;
     protected $protectFields    = true;

     protected $allowedFields = [
          'LogInOutDetailId',
          'UserId',
          'LogInOutStatus',
          'CreatedDate',
     ];

     protected $useTimestamps = false;

     protected $validationRules      = [];
     protected $validationMessages   = [];
     protected $skipValidation       = false;
     protected $cleanValidationRules = true;
}
