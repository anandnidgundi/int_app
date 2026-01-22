<?php

namespace App\Models;

use CodeIgniter\Model;

class ShiftListsModel extends Model
{
     protected $table = 'shiftslist';
     protected $primaryKey = 'id';
     protected $allowedFields = [
          'ShiftName',
          'ShiftStart',
          'ShiftEnd',
          'WorkingsHoursToBeConsiderdFullDay',
          'WorkingsHoursToBeConsiderdHalfDay'
     ];
     protected $returnType = 'array';
     public $timestamps = false;
}
