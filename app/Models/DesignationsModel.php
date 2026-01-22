<?php

namespace App\Models;

use CodeIgniter\Model;

class DesignationsModel extends Model
{
     protected $table = 'designations';
     protected $primaryKey = 'id';
     protected $useAutoIncrement = true;
     protected $returnType = 'array';
     protected $useSoftDeletes = false;
     protected $allowedFields = ['designation_type', 'status', 'created_by', 'created_on', 'modified_by', 'modified_on'];
     protected $useTimestamps = false;
     protected $validationRules = [
          'designation_type' => 'required|string',
          'status' => 'required|in_list[A,I]',
          'created_by' => 'required|string|max_length[20]',
          'created_on' => 'required|valid_date',
          'modified_by' => 'string|max_length[20]',
          'modified_on' => 'required|valid_date',
     ];
     protected $validationMessages = [];
     protected $skipValidation = false;
}
