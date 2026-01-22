<?php

namespace App\Models;

use CodeIgniter\Model;

class RegularizeModel extends Model
{
     protected $table = 'regularize_requests';
     protected $primaryKey = 'id';
     protected $allowedFields = [
          'emp_code',
          'for_date',
          'punch_in',
          'punch_out',
          'attachment',
          'reason',
          'status',
          'applied_by',
          'applied_on',
          'approved_by',
          'approved_on',
          'rejected_by',
          'rejected_on',
          'remarks'
     ];
     protected $useTimestamps = true;
     protected $returnType = 'array';

     // public function getPending()
     // {
     //      return $this->where('status', 'Pending')->orderBy('for_date', 'DESC')->findAll();
     // }

     public function getPending()
     {
          $list = $this->where('status', 'Pending')->orderBy('for_date', 'DESC')->findAll();
          return $this->enrichWithEmployeeNames($list);
     }

     public function getByEmployee($empCode)
     {
          $list = $this->where('emp_code', $empCode)->orderBy('for_date', 'DESC')->findAll();
          return $this->enrichWithEmployeeNames($list);
     }

     /**
      * Enrich regularize requests with employee names from new_emp_master (travelapp DB)
      * Fetches names for: emp_code, applied_by, approved_by, rejected_by
      */
     public function enrichWithEmployeeNames(array $list)
     {
          if (empty($list)) {
               return $list;
          }

          // Collect all unique employee codes that need name lookup
          $empCodes = [];
          foreach ($list as $row) {
               if (!empty($row['emp_code'])) $empCodes[] = $row['emp_code'];
               if (!empty($row['applied_by'])) $empCodes[] = $row['applied_by'];
               if (!empty($row['approved_by'])) $empCodes[] = $row['approved_by'];
               if (!empty($row['rejected_by'])) $empCodes[] = $row['rejected_by'];
          }

          $empCodes = array_unique($empCodes);

          if (empty($empCodes)) {
               return $list;
          }

          // Fetch employee names from travelapp DB
          $db = \Config\Database::connect('travelapp');
          $empNames = [];

          try {
               $result = $db->table('new_emp_master')
                    ->select('emp_code, fname, lname')
                    ->whereIn('emp_code', $empCodes)
                    ->get()
                    ->getResultArray();

               foreach ($result as $emp) {
                    $empNames[$emp['emp_code']] = $emp['fname'] . ' ' . $emp['lname'];
               }
          } catch (\Exception $ex) {
               log_message('error', 'RegularizeModel::enrichWithEmployeeNames failed: ' . $ex->getMessage());
          }

          // Enrich the list with employee names
          foreach ($list as &$row) {
               $row['employee_name'] = $empNames[$row['emp_code']] ?? null;
               $row['applied_by_name'] = !empty($row['applied_by']) ? ($empNames[$row['applied_by']] ?? null) : null;
               $row['approved_by_name'] = !empty($row['approved_by']) ? ($empNames[$row['approved_by']] ?? null) : null;
               $row['rejected_by_name'] = !empty($row['rejected_by']) ? ($empNames[$row['rejected_by']] ?? null) : null;
          }
          unset($row);

          return $list;
     }


     // public function getByEmployee($empCode)
     // {
     //      return $this->where('emp_code', $empCode)->orderBy('for_date', 'DESC')->findAll();
     // }

     /**
      * Try to insert/update duty_roster with punches from regularize request.
      * This method is conservative: checks duty_roster columns and updates only if compatible columns exist.
      * Returns array with status and message.
      */


     public function upsertAttendanceFromRequest(array $request)
     {
          $db = \Config\Database::connect();
          $dutyTable = 'duty_roster';

          // find emp_id (if duty_roster uses emp_id)
          $empRow = $db->table('employees')->select('emp_id')->where('employee_code', $request['emp_code'])->get()->getRowArray();
          $emp_id = $empRow['emp_id'] ?? null;

          // Check duty_roster exists
          try {
               $fields = $db->getFieldNames($dutyTable);
          } catch (\Exception $e) {
               return ['status' => false, 'message' => "duty_roster table not found: " . $e->getMessage()];
          }

          // Determine candidate punch columns
          $inCols = array_intersect(['in_time', 'punch_in', 'time_in'], $fields);
          $outCols = array_intersect(['out_time', 'punch_out', 'time_out'], $fields);

          $attendanceDateCol = in_array('attendance_date', $fields, true) ? 'attendance_date' : null;
          $empIdCol = in_array('emp_id', $fields, true) ? 'emp_id' : (in_array('emp_code', $fields, true) ? 'emp_code' : null);

          if (!$attendanceDateCol || !$empIdCol || empty($inCols) && empty($outCols)) {
               return ['status' => false, 'message' => 'Duty roster schema incompatible for automatic upsert (missing columns)'];
          }

          $inCol = $inCols ? reset($inCols) : null;
          $outCol = $outCols ? reset($outCols) : null;

          $builder = $db->table($dutyTable);

          // Build search criteria
          $builder->where($attendanceDateCol, $request['for_date']);
          if ($empIdCol === 'emp_id' && $emp_id !== null) {
               $builder->where('emp_id', $emp_id);
          } else {
               $builder->where('emp_code', $request['emp_code']);
          }

          $existing = $builder->get()->getRowArray();

          $upd = [];
          if ($inCol && !empty($request['punch_in'])) $upd[$inCol] = $request['punch_in'];
          if ($outCol && !empty($request['punch_out'])) $upd[$outCol] = $request['punch_out'];

          if (empty($upd)) {
               return ['status' => false, 'message' => 'No punch values provided to upsert'];
          }

          if ($existing) {
               $idToUpdate = $existing['id'] ?? null;
               if ($idToUpdate) {
                    $res = $db->table($dutyTable)->update($upd, ['id' => $idToUpdate]);
                    return ['status' => (bool)$res, 'message' => $res ? 'attendance updated' : 'attendance update failed'];
               } else {
                    // fallback update by where condition
                    $res = $db->table($dutyTable)->where($attendanceDateCol, $request['for_date'])->where($empIdCol, $emp_id ?? $request['emp_code'])->update($upd);
                    return ['status' => (bool)$res, 'message' => $res ? 'attendance updated' : 'attendance update failed'];
               }
          } else {
               // Insert new row (build insert data)
               $insert = [];
               if ($empIdCol === 'emp_id') $insert['emp_id'] = $emp_id;
               else $insert['emp_code'] = $request['emp_code'];

               $insert[$attendanceDateCol] = $request['for_date'];
               if ($inCol) $insert[$inCol] = $request['punch_in'] ?? null;
               if ($outCol) $insert[$outCol] = $request['punch_out'] ?? null;
               // createdBy/createdDTM fields may exist — attempt to fill if present
               if (in_array('createdBy', $fields, true)) $insert['createdBy'] = $request['applied_by'] ?? null;
               if (in_array('createdDTM', $fields, true)) $insert['createdDTM'] = date('Y-m-d H:i:s');

               $res = $db->table($dutyTable)->insert($insert);
               return ['status' => (bool)$res, 'message' => $res ? 'attendance inserted' : 'attendance insert failed'];
          }
     }
}
