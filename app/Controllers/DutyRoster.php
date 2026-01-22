<?php

namespace App\Controllers;

use App\Models\DutyRosterModel;
use App\Models\ShiftListsModel;
use CodeIgniter\API\ResponseTrait;
use App\Controllers\BaseController;

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

     public function createDutyRosterBulk()
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['user_code'];
               $json = $this->request->getJSON(true);
               $dutyRosterModel = $this->dutyRosterModel;

               // Validate required fields
               if (empty($json['month']) || empty($json['shift_id']) || empty($json['emp_ids']) || !is_array($json['emp_ids'])) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'month, shift_id, and emp_ids array are required'
                    ], 400);
               }

               $month = $json['month']; // Format: YYYY-MM
               $shift_id = $json['shift_id'];
               $emp_ids = $json['emp_ids'];
               $emp_codes = $json['emp_code'] ?? [];

               // Validate month format
               if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Invalid month format. Use YYYY-MM'
                    ], 400);
               }

               // Calculate start and end dates for the month
               $startDate = $month . '-01';
               $endDate = date('Y-m-t', strtotime($startDate)); // Last day of the month

               // Generate all dates in the month
               $dates = [];
               $currentDate = $startDate;
               while ($currentDate <= $endDate) {
                    $dates[] = $currentDate;
                    $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
               }

               $success = [];
               $failed = [];
               $totalProcessed = 0;

               // Process each employee
               foreach ($emp_ids as $index => $emp_id) {
                    $emp_code = $emp_codes[$index] ?? null;

                    // Process each date in the month for this employee
                    foreach ($dates as $date) {
                         $totalProcessed++;

                         // Check if the date is a Sunday (0 = Sunday in date('w'))
                         $isSunday = (date('w', strtotime($date)) == 0);

                         $data = [
                              'emp_id' => $emp_id,
                              'shift_id' => $shift_id,
                              'attendance_date' => $date,
                              'custom_weekoff_date' => $isSunday ? $date : null, // Set Sunday as weekoff
                              'createdBy' => $user,
                              'createdDTM' => date('Y-m-d H:i:s')
                         ];

                         // Check if record already exists
                         $existing = $dutyRosterModel
                              ->where('emp_id', $emp_id)
                              ->where('attendance_date', $date)
                              ->first();

                         if ($existing) {
                              // Update existing record
                              $updateData = $data;
                              $updateData['updatedBy'] = $user;
                              $updateData['updatedDTM'] = date('Y-m-d H:i:s');

                              if ($dutyRosterModel->update($existing['id'], $updateData)) {
                                   $success[] = [
                                        'emp_id' => $emp_id,
                                        'emp_code' => $emp_code,
                                        'date' => $date,
                                        'action' => 'updated',
                                        'is_weekoff' => $isSunday
                                   ];
                              } else {
                                   $failed[] = [
                                        'emp_id' => $emp_id,
                                        'emp_code' => $emp_code,
                                        'date' => $date,
                                        'action' => 'update_failed',
                                        'error' => $dutyRosterModel->errors()
                                   ];
                              }
                         } else {
                              // Insert new record
                              if ($dutyRosterModel->insert($data)) {
                                   $success[] = [
                                        'emp_id' => $emp_id,
                                        'emp_code' => $emp_code,
                                        'date' => $date,
                                        'action' => 'created',
                                        'is_weekoff' => $isSunday
                                   ];
                              } else {
                                   $failed[] = [
                                        'emp_id' => $emp_id,
                                        'emp_code' => $emp_code,
                                        'date' => $date,
                                        'action' => 'create_failed',
                                        'error' => $dutyRosterModel->errors()
                                   ];
                              }
                         }
                    }
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'Bulk duty roster processed',
                    'summary' => [
                         'total_employees' => count($emp_ids),
                         'total_days' => count($dates),
                         'total_records_processed' => $totalProcessed,
                         'successful' => count($success),
                         'failed' => count($failed)
                    ],
                    'month' => $month,
                    'shift_id' => $shift_id,
                    'date_range' => [
                         'start' => $startDate,
                         'end' => $endDate
                    ],
                    'success' => $success,
                    'failed' => $failed
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
               ], 500);
          }
     }

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


     public function autoGenerateDutyRosterForContractEmployee()
     {
          try {
               $user = 'System';
               $json = $this->request->getJSON(true);

               $dutyRosterModel = $this->dutyRosterModel;
               $employeeModel = new \App\Models\NewEmployeeMasterModel();

               $db = \Config\Database::connect();

               // Accept month from several keys; default to current month
               $rawMonth = $json['month'] ?? $json['selectedMonth'] ?? null;
               if (empty($rawMonth)) {
                    $month = date('Y-m');
               } else {
                    $ts = strtotime($rawMonth);
                    if ($ts === false) {
                         $month = preg_match('/^\d{6}$/', $rawMonth) ? substr($rawMonth, 0, 4) . '-' . substr($rawMonth, 4, 2) : null;
                    } else {
                         $month = date('Y-m', $ts);
                    }
               }

               if (empty($month) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Invalid or missing month. Use YYYY-MM'
                    ], 400);
               }

               // Build date range
               $startDate = $month . '-01';
               $endDate = date('Y-m-t', strtotime($startDate));
               $dates = [];
               $cur = $startDate;
               while ($cur <= $endDate) {
                    $dates[] = $cur;
                    $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
               }

               $success = [];
               $failed = [];
               $skipped = [];
               $processedEmployees = 0;

               // Fetch contractual employees
               $employees = [];
               try {
                    if ($db->tableExists('employees')) {
                         $employees = $db->table('employees')
                              ->where('emp_type', 'CONTRACTUAL_EMPLOYEE')
                              ->where('status', 'A')
                              ->where('isDeleted', 'N')
                              ->get()
                              ->getResultArray();
                    }
               } catch (\Exception $e) {
                    $employees = [];
               }

               if (empty($employees)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'No contractual employees found to auto-generate roster'
                    ], 200);
               }

               foreach ($employees as $employee) {
                    $empId = $employee['emp_id'] ?? null;
                    $empCode = $employee['employee_code'] ?? null;
                    $shiftDesc = $employee['shift_description'] ?? null;

                    if (empty($empId)) {
                         $failed[] = ['emp_id' => null, 'emp_code' => $empCode, 'reason' => 'missing_emp_id'];
                         continue;
                    }

                    // If any roster exists for this emp in the month, skip
                    $existsForMonth = $dutyRosterModel
                         ->where('emp_id', $empId)
                         ->where('attendance_date >=', $startDate)
                         ->where('attendance_date <=', $endDate)
                         ->first();

                    if ($existsForMonth) {
                         $skipped[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'reason' => 'roster_exists'];
                         continue;
                    }

                    // Resolve shift ID from shift description (robust: exact case-insensitive -> LIKE fallback)
                    $shiftId = $employee['shift_id'] ?? null;
                    $shiftDesc = trim((string)$shiftDesc);

                    if (empty($shiftId) && $shiftDesc !== '') {
                         // normalize for case-insensitive exact match
                         $norm = strtoupper(preg_replace('/\s+|[-_]+/', ' ', $shiftDesc));
                         $shift = $db->table('shiftslist')->select('id')->where("UPPER(ShiftName)", $norm)->get()->getRowArray();

                         // fallback to LIKE (partial / tolerant match)
                         if (!$shift) {
                              $shift = $db->table('shiftslist')->select('id')->like('ShiftName', $shiftDesc)->get()->getRowArray();
                         }

                         if ($shift) {
                              $shiftId = $shift['id'];
                         }
                    }

                    // debug log for diagnostics
                    log_message('error', "Contract shift resolution: emp={$empCode} ({$empId}), desc='{$shiftDesc}' => shift_id=" . ($shiftId ?? 'NULL'));


                    if (empty($shiftId)) {
                         // try to provide helpful suggestions (up to 5)
                         $candidates = $db->table('shiftslist')->select('ShiftName')->like('ShiftName', $shiftDesc)->limit(5)->get()->getResultArray();
                         $suggestions = array_column($candidates, 'ShiftName');

                         $failed[] = [
                              'emp_id' => $empId,
                              'emp_code' => $empCode,
                              'reason' => 'shift_not_found',
                              'shift_description' => $shiftDesc,
                              'suggestions' => $suggestions
                         ];
                         continue;
                    }

                    $processedEmployees++;

                    // Insert roster for each date (skip if date exists)
                    foreach ($dates as $date) {
                         $existing = $dutyRosterModel
                              ->where('emp_id', $empId)
                              ->where('attendance_date', $date)
                              ->first();

                         if ($existing) {
                              continue;
                         }

                         // Determine weekoff: prefer employee's week_off if present, else default to Sunday
                         $isWeekOff = false;
                         $empWeekOffRaw = strtoupper(trim($employee['week_off'] ?? ''));
                         if ($empWeekOffRaw !== '') {
                              // map common names/abbrev to numeric weekday (0 = Sunday)
                              $map = [
                                   'SUNDAY' => 0,
                                   'SUN' => 0,
                                   'MONDAY' => 1,
                                   'MON' => 1,
                                   'TUESDAY' => 2,
                                   'TUE' => 2,
                                   'TUES' => 2,
                                   'WEDNESDAY' => 3,
                                   'WED' => 3,
                                   'THURSDAY' => 4,
                                   'THU' => 4,
                                   'FRIDAY' => 5,
                                   'FRI' => 5,
                                   'SATURDAY' => 6,
                                   'SAT' => 6,
                              ];
                              $parts = preg_split('/[,;|]+/', $empWeekOffRaw);
                              $weekOffDays = [];
                              foreach ($parts as $p) {
                                   $key = strtoupper(trim($p));
                                   if (isset($map[$key])) {
                                        $weekOffDays[] = $map[$key];
                                   }
                              }
                              $isWeekOff = in_array((int)date('w', strtotime($date)), $weekOffDays);
                         } else {
                              $isWeekOff = (date('w', strtotime($date)) == 0);
                         }
                         $data = [
                              'emp_id' => $empId,
                              'shift_id' => $shiftId,
                              'attendance_date' => $date,
                              'custom_weekoff_date' => $isWeekOff ? $date : null,
                              'createdBy' => $user,
                              'createdDTM' => date('Y-m-d H:i:s')
                         ];

                         if ($dutyRosterModel->insert($data)) {
                              $success[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'date' => $date, 'action' => 'created'];
                         } else {
                              $failed[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'date' => $date, 'action' => 'create_failed', 'error' => $dutyRosterModel->errors()];
                         }
                    }
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'Auto duty roster generation for contractual employees completed',
                    'month' => $month,
                    'date_range' => ['start' => $startDate, 'end' => $endDate],
                    'total_employees_found' => count($employees),
                    'processed_employees' => $processedEmployees,
                    'skipped_employees' => count($skipped ?? []),
                    'created' => count($success),
                    'failed' => count($failed),
                    'details' => [
                         'success' => $success,
                         'failed' => $failed,
                         'skipped' => $skipped ?? []
                    ]
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
               ], 500);
          }
     }

     public function autoGenerateDutyRosterBulk()
     {
          try {
               $user = 'System';
               $json = $this->request->getJSON(true);
               $dutyRosterModel   = $this->dutyRosterModel;
               $doctorsShiftModel = new \App\Models\DoctorsShiftMasterModel();
               $employeeModel     = new \App\Models\NewEmployeeMasterModel();
               $db = \Config\Database::connect();

               // Accept month from several keys; default to current month
               $rawMonth = $json['month'] ?? $json['selectedMonth'] ?? null;
               if (empty($rawMonth)) {
                    $month = date('Y-m');
               } else {
                    $ts = strtotime($rawMonth);
                    if ($ts === false) {
                         $month = preg_match('/^\d{6}$/', $rawMonth) ? substr($rawMonth, 0, 4) . '-' . substr($rawMonth, 4, 2) : null;
                    } else {
                         $month = date('Y-m', $ts);
                    }
               }

               if (empty($month) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Invalid or missing month. Use YYYY-MM'
                    ], 400);
               }

               // build date range 975110499
               $startDate = $month . '-01';
               $endDate = date('Y-m-t', strtotime($startDate));
               $dates = [];
               $cur = $startDate;
               while ($cur <= $endDate) {
                    $dates[] = $cur;
                    $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
               }

               // Auto mode detection: explicit auto=true OR no shift_id & emp_ids provided
               $auto = !empty($json['auto']) || (empty($json['shift_id']) && empty($json['emp_ids']));
               $success = [];
               $failed = [];
               $processedDoctors = 0;

               // --- run-once guard for auto-doctors ---
               $autoRecordId = null;
               if ($auto) {
                    $ags = $db->table('auto_generated_shifts');
                    $existing = $ags->where('month', $month)->where('done_for', 'DOCTOR')->get()->getRowArray();
                    if ($existing) {
                         return $this->respond([
                              'status' => false,
                              'message' => 'Auto generation already performed for this month for DOCTOR',
                              'month' => $month,
                              'record' => $existing
                         ], 200);
                    }

                    // Insert a marker row to indicate processing started
                    $ags->insert([
                         'month' => $month,
                         'done_for' => 'DOCTOR',
                         'is_completed' => 'N'
                    ]);
                    $autoRecordId = $db->insertID();
               }
               // --- end run-once guard ---

               if ($auto) {
                    // Try to fetch doctors from canonical 'employees' table first
                    $doctors = [];
                    try {
                         if ($db->tableExists('employees')) {
                              $doctors = $db->table('employees')->where('emp_type', 'DOCTOR')->get()->getResultArray();
                         }
                    } catch (\Exception $e) {
                         $doctors = [];
                    }

                    // Fallback: use model->findAll() and filter by emp_type (robust)
                    if (empty($doctors)) {
                         // Try model where() first (efficient)
                         try {
                              $doctors = $employeeModel
                                   ->where('emp_type', 'DOCTOR')
                                   ->where('status', 'A')
                                   ->where('isDeleted', 'N')
                                   ->findAll();
                         } catch (\Exception $e) {
                              // Final fallback: filter array manually
                              $all = $employeeModel->findAll();
                              $doctors = array_values(array_filter($all, function ($r) {
                                   $val = strtoupper($r['emp_type'] ?? $r['employee_type'] ?? $r['employment_type'] ?? '');
                                   $status = strtoupper($r['status'] ?? '');
                                   $isDeleted = strtoupper($r['isDeleted'] ?? 'N');

                                   return $val === 'DOCTOR' && $status === 'A' && $isDeleted === 'N';
                              }));
                         }
                    }

                    // If still empty, return informative response
                    if (empty($doctors)) {
                         if ($autoRecordId) {
                              $db->table('auto_generated_shifts')->where('id', $autoRecordId)->delete();
                         }
                         return $this->respond([
                              'status' => false,
                              'message' => 'No doctor employees found to auto-generate roster'
                         ], 200);
                    }

                    foreach ($doctors as $doc) {
                         // tolerant id / code lookup
                         $empId = $doc['emp_id'] ?? null;
                         $empCode = $doc['employee_code']  ?? null;
                         if (empty($empId)) {
                              $failed[] = ['emp_id' => null, 'emp_code' => $empCode, 'reason' => 'missing_emp_id'];
                              continue;
                         }

                         // If any roster exists for this emp in the month, skip (ensures one-time per doctor per month)
                         $existsForMonth = $dutyRosterModel
                              ->where('emp_id', $empId)
                              ->where('attendance_date >=', $startDate)
                              ->where('attendance_date <=', $endDate)
                              ->first();

                         if ($existsForMonth) {
                              $skipped[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'reason' => 'roster_exists'];
                              continue;
                         }

                         // Resolve default shift:
                         // 1) if doctor row has shift_id, use it
                         // 2) try doctors_shift_master by matching shift_description or shift_name
                         // 3) fallback to shiftslist by ShiftName
                         // Resolve default shift:
                         $shiftId = $doc['shift_id'] ?? null;
                         $shiftDesc = trim($doc['shift_description'] ?? $doc['shift_name'] ?? '');
                         $isSplit = strtoupper(trim($doc['split_shift'] ?? 'N')) === 'Y';

                         // helper to resolve by name using doctors_shift_master then shiftslist
                         $resolveShiftByName = function ($name) use ($doctorsShiftModel, $db) {
                              $name = trim((string)$name);
                              if ($name === '') return null;
                              $norm = strtoupper(preg_replace('/\s+|[-_]+/', ' ', $name));
                              $ds = $doctorsShiftModel->where("UPPER(shift_name)", $norm)->first();
                              if ($ds) return $ds['id'];
                              $ds = $doctorsShiftModel->like('shift_name', $name)->first();
                              if ($ds) return $ds['id'];
                              $s = $db->table('shiftslist')->select('id')->where("UPPER(ShiftName)", $norm)->get()->getRowArray();
                              if ($s) return $s['id'];
                              $s = $db->table('shiftslist')->select('id')->like('ShiftName', $name)->get()->getRowArray();
                              return $s ? $s['id'] : null;
                         };

                         if ($isSplit) {
                              // try explicit fields first
                              $shift1Id = $doc['shift_1_id'] ?? $doc['shift1_id'] ?? $doc['first_shift_id'] ?? null;
                              $shift2Id = $doc['shift_2_id'] ?? $doc['shift2_id'] ?? $doc['second_shift_id'] ?? null;

                              // try to parse names from shift_description if IDs not present
                              if ((empty($shift1Id) || empty($shift2Id)) && $shiftDesc !== '') {
                                   $parts = preg_split('/[\/,|]+/', $shiftDesc);
                                   $parts = array_map('trim', $parts);
                                   $sname1 = $parts[0] ?? null;
                                   $sname2 = $parts[1] ?? null;
                              } else {
                                   $sname1 = $doc['shift_1'] ?? $doc['shift1'] ?? $shiftDesc;
                                   $sname2 = $doc['shift_2'] ?? $doc['shift2'] ?? null;
                              }

                              if (empty($shift1Id) && !empty($sname1)) {
                                   $shift1Id = $resolveShiftByName($sname1);
                              }
                              if (empty($shift2Id) && !empty($sname2)) {
                                   $shift2Id = $resolveShiftByName($sname2);
                              }

                              // log for diagnostics
                              log_message('error', "Split shift resolution: emp={$empCode} ({$empId}) desc='{$shiftDesc}' => s1=" . ($shift1Id ?? 'NULL') . ", s2=" . ($shift2Id ?? 'NULL'));

                              if (empty($shift1Id) && empty($shift2Id)) {
                                   $failed[] = [
                                        'emp_id' => $empId,
                                        'emp_code' => $empCode,
                                        'reason' => 'split_shifts_not_found',
                                        'shift_description' => $shiftDesc
                                   ];
                                   continue;
                              }

                              $processedDoctors++;

                              // Insert roster for each date (create one record per split shift if missing)
                              foreach ($dates as $date) {
                                   // Skip entire date if any non-split record already exists for this emp/date
                                   // (keeps behavior consistent with previous guard)
                                   $anyExisting = $dutyRosterModel
                                        ->where('emp_id', $empId)
                                        ->where('attendance_date', $date)
                                        ->first();
                                   if ($anyExisting) {
                                        // still attempt to insert missing split parts if they don't exist
                                        // proceed
                                   }

                                   $shiftCandidates = [
                                        ['id' => $shift1Id, 'type' => 'shift_1'],
                                        ['id' => $shift2Id, 'type' => 'shift_2']
                                   ];

                                   foreach ($shiftCandidates as $sh) {
                                        if (empty($sh['id'])) continue;
                                        $existing = $dutyRosterModel
                                             ->where('emp_id', $empId)
                                             ->where('attendance_date', $date)
                                             ->where('shift_id', $sh['id'])
                                             ->where('split_shift_type', $sh['type'])
                                             ->first();
                                        if ($existing) {
                                             continue;
                                        }

                                        $data = [
                                             'emp_id' => $empId,
                                             'shift_id' => $sh['id'],
                                             'attendance_date' => $date,
                                             'custom_weekoff_date' => null,
                                             'split_shift_type' => $sh['type'],
                                             'createdBy' => $user,
                                             'createdDTM' => date('Y-m-d H:i:s')
                                        ];

                                        if ($dutyRosterModel->insert($data)) {
                                             $success[] = [
                                                  'emp_id' => $empId,
                                                  'emp_code' => $empCode,
                                                  'date' => $date,
                                                  'action' => 'created',
                                                  'shift_id' => $sh['id'],
                                                  'shift_type' => $sh['type']
                                             ];
                                        } else {
                                             $failed[] = [
                                                  'emp_id' => $empId,
                                                  'emp_code' => $empCode,
                                                  'date' => $date,
                                                  'action' => 'create_failed',
                                                  'shift_id' => $sh['id'],
                                                  'shift_type' => $sh['type'],
                                                  'error' => $dutyRosterModel->errors()
                                             ];
                                        }
                                   }
                              }
                         } else {
                              // non-split (existing behavior)
                              if (empty($shiftId) && $shiftDesc !== '') {
                                   $shiftId = $resolveShiftByName($shiftDesc);
                              }
                              log_message('error', "Shift resolution for emp {$empCode} ({$empId}) desc '{$shiftDesc}' resolved to id: " . ($shiftId ?? 'NULL'));

                              if (empty($shiftId)) {
                                   $failed[] = [
                                        'emp_id' => $empId,
                                        'emp_code' => $empCode,
                                        'reason' => 'shift_not_found',
                                        'shift_description' => $shiftDesc
                                   ];
                                   continue;
                              }

                              $processedDoctors++;

                              // Insert roster for each date (skip if date exists)
                              foreach ($dates as $date) {
                                   $existing = $dutyRosterModel
                                        ->where('emp_id', $empId)
                                        ->where('attendance_date', $date)
                                        ->first();
                                   if ($existing) {
                                        continue;
                                   }

                                   // default weekoff: Sunday. If employee has explicit week_off that maps to date, you can add logic here.
                                   $isSunday = (date('w', strtotime($date)) == 0);
                                   $data = [
                                        'emp_id' => $empId,
                                        'shift_id' => $shiftId,
                                        'attendance_date' => $date,
                                        'custom_weekoff_date' => $isSunday ? $date : null,
                                        'createdBy' => $user,
                                        'createdDTM' => date('Y-m-d H:i:s')
                                   ];

                                   if ($dutyRosterModel->insert($data)) {
                                        $success[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'date' => $date, 'action' => 'created'];
                                   } else {
                                        $failed[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'date' => $date, 'action' => 'create_failed', 'error' => $dutyRosterModel->errors()];
                                   }
                              }
                         }
                    } // end foreach doctors

                    // mark auto generation completed
                    if ($autoRecordId) {
                         $db->table('auto_generated_shifts')->where('id', $autoRecordId)->update(['is_completed' => 'Y']);
                    }

                    return $this->respond([
                         'status' => true,
                         'message' => 'Auto duty roster generation completed',
                         'month' => $month,
                         'date_range' => ['start' => $startDate, 'end' => $endDate],
                         'total_doctors_found' => count($doctors),
                         'processed_doctors' => $processedDoctors,
                         'skipped_doctors' => count($skipped ?? []),
                         'created' => count($success),
                         'failed' => count($failed),
                         'details' => [
                              'success' => $success,
                              'failed' => $failed,
                              'skipped' => $skipped ?? []
                         ]
                    ], 200);
               }

               // Manual bulk behavior (existing) - validated only when not auto
               if (empty($json['shift_id']) || empty($json['emp_ids']) || !is_array($json['emp_ids'])) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'For manual mode month, shift_id, and emp_ids array are required'
                    ], 400);
               }

               $shift_id = $json['shift_id'];
               $emp_ids = $json['emp_ids'];
               $emp_codes = $json['emp_code'] ?? [];

               $totalProcessed = 0;
               $success = [];
               $failed = [];

               foreach ($emp_ids as $index => $emp_id) {
                    $emp_code = $emp_codes[$index] ?? null;
                    foreach ($dates as $date) {
                         $totalProcessed++;
                         $isSunday = (date('w', strtotime($date)) == 0);
                         $data = [
                              'emp_id' => $emp_id,
                              'shift_id' => $shift_id,
                              'attendance_date' => $date,
                              'custom_weekoff_date' => $isSunday ? $date : null,
                              'createdBy' => $user,
                              'createdDTM' => date('Y-m-d H:i:s')
                         ];

                         $existing = $dutyRosterModel
                              ->where('emp_id', $emp_id)
                              ->where('attendance_date', $date)
                              ->first();

                         if ($existing) {
                              $updateData = $data;
                              $updateData['updatedBy'] = $user;
                              $updateData['updatedDTM'] = date('Y-m-d H:i:s');
                              if ($dutyRosterModel->update($existing['id'], $updateData)) {
                                   $success[] = ['emp_id' => $emp_id, 'emp_code' => $emp_code, 'date' => $date, 'action' => 'updated'];
                              } else {
                                   $failed[] = ['emp_id' => $emp_id, 'emp_code' => $emp_code, 'date' => $date, 'action' => 'update_failed', 'error' => $dutyRosterModel->errors()];
                              }
                         } else {
                              if ($dutyRosterModel->insert($data)) {
                                   $success[] = ['emp_id' => $emp_id, 'emp_code' => $emp_code, 'date' => $date, 'action' => 'created'];
                              } else {
                                   $failed[] = ['emp_id' => $emp_id, 'emp_code' => $emp_code, 'date' => $date, 'action' => 'create_failed', 'error' => $dutyRosterModel->errors()];
                              }
                         }
                    }
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'Bulk duty roster processed',
                    'summary' => [
                         'total_employees' => count($emp_ids),
                         'total_days' => count($dates),
                         'total_records_processed' => $totalProcessed,
                         'successful' => count($success),
                         'failed' => count($failed)
                    ],
                    'month' => $month,
                    'shift_id' => $shift_id ?? null,
                    'date_range' => ['start' => $startDate, 'end' => $endDate],
                    'success' => $success,
                    'failed' => $failed
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
               ], 500);
          }
     }

     // public function autoGenerateDutyRosterBulk()
     // {
     //      try {
     //           $user = 'System';
     //           $json = $this->request->getJSON(true);
     //           $dutyRosterModel   = $this->dutyRosterModel;
     //           $doctorsShiftModel = new \App\Models\DoctorsShiftMasterModel();
     //           $employeeModel     = new \App\Models\NewEmployeeMasterModel();
     //           $db = \Config\Database::connect();

     //           // Accept month from several keys; default to current month
     //           $rawMonth = $json['month'] ?? $json['selectedMonth'] ?? null;
     //           if (empty($rawMonth)) {
     //                $month = date('Y-m');
     //           } else {
     //                $ts = strtotime($rawMonth);
     //                if ($ts === false) {
     //                     $month = preg_match('/^\d{6}$/', $rawMonth) ? substr($rawMonth, 0, 4) . '-' . substr($rawMonth, 4, 2) : null;
     //                } else {
     //                     $month = date('Y-m', $ts);
     //                }
     //           }

     //           if (empty($month) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
     //                return $this->respond([
     //                     'status' => false,
     //                     'message' => 'Invalid or missing month. Use YYYY-MM'
     //                ], 400);
     //           }

     //           // build date range 975110499
     //           $startDate = $month . '-01';
     //           $endDate = date('Y-m-t', strtotime($startDate));
     //           $dates = [];
     //           $cur = $startDate;
     //           while ($cur <= $endDate) {
     //                $dates[] = $cur;
     //                $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
     //           }

     //           // Auto mode detection: explicit auto=true OR no shift_id & emp_ids provided
     //           $auto = !empty($json['auto']) || (empty($json['shift_id']) && empty($json['emp_ids']));
     //           $success = [];
     //           $failed = [];
     //           $processedDoctors = 0;

     //           // --- run-once guard for auto-doctors ---
     //           $autoRecordId = null;
     //           if ($auto) {
     //                $ags = $db->table('auto_generated_shifts');
     //                $existing = $ags->where('month', $month)->where('done_for', 'DOCTOR')->get()->getRowArray();
     //                if ($existing) {
     //                     return $this->respond([
     //                          'status' => false,
     //                          'message' => 'Auto generation already performed for this month for DOCTOR',
     //                          'month' => $month,
     //                          'record' => $existing
     //                     ], 200);
     //                }

     //                // Insert a marker row to indicate processing started
     //                $ags->insert([
     //                     'month' => $month,
     //                     'done_for' => 'DOCTOR',
     //                     'is_completed' => 'N'
     //                ]);
     //                $autoRecordId = $db->insertID();
     //           }
     //           // --- end run-once guard ---

     //           if ($auto) {
     //                // Try to fetch doctors from canonical 'employees' table first
     //                $doctors = [];
     //                try {
     //                     if ($db->tableExists('employees')) {
     //                          $doctors = $db->table('employees')->where('emp_type', 'DOCTOR')->get()->getResultArray();
     //                     }
     //                } catch (\Exception $e) {
     //                     $doctors = [];
     //                }

     //                // Fallback: use model->findAll() and filter by emp_type (robust)
     //                if (empty($doctors)) {
     //                     // Try model where() first (efficient)
     //                     try {
     //                          $doctors = $employeeModel
     //                               ->where('emp_type', 'DOCTOR')
     //                               ->where('status', 'A')
     //                               ->where('isDeleted', 'N')
     //                               ->findAll();
     //                     } catch (\Exception $e) {
     //                          // Final fallback: filter array manually
     //                          $all = $employeeModel->findAll();
     //                          $doctors = array_values(array_filter($all, function ($r) {
     //                               $val = strtoupper($r['emp_type'] ?? $r['employee_type'] ?? $r['employment_type'] ?? '');
     //                               $status = strtoupper($r['status'] ?? '');
     //                               $isDeleted = strtoupper($r['isDeleted'] ?? 'N');

     //                               return $val === 'DOCTOR' && $status === 'A' && $isDeleted === 'N';
     //                          }));
     //                     }
     //                }

     //                // If still empty, return informative response
     //                if (empty($doctors)) {
     //                     if ($autoRecordId) {
     //                          $db->table('auto_generated_shifts')->where('id', $autoRecordId)->delete();
     //                     }
     //                     return $this->respond([
     //                          'status' => false,
     //                          'message' => 'No doctor employees found to auto-generate roster'
     //                     ], 200);
     //                }

     //                foreach ($doctors as $doc) {
     //                     // tolerant id / code lookup
     //                     $empId = $doc['emp_id'] ?? null;
     //                     $empCode = $doc['employee_code']  ?? null;
     //                     if (empty($empId)) {
     //                          $failed[] = ['emp_id' => null, 'emp_code' => $empCode, 'reason' => 'missing_emp_id'];
     //                          continue;
     //                     }

     //                     // If any roster exists for this emp in the month, skip (ensures one-time per doctor per month)
     //                     $existsForMonth = $dutyRosterModel
     //                          ->where('emp_id', $empId)
     //                          ->where('attendance_date >=', $startDate)
     //                          ->where('attendance_date <=', $endDate)
     //                          ->first();

     //                     if ($existsForMonth) {
     //                          $skipped[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'reason' => 'roster_exists'];
     //                          continue;
     //                     }

     //                     // Resolve default shift:
     //                     // 1) if doctor row has shift_id, use it
     //                     // 2) try doctors_shift_master by matching shift_description or shift_name
     //                     // 3) fallback to shiftslist by ShiftName
     //                     // Resolve default shift:
     //                     $shiftId = $doc['shift_id'] ?? null;
     //                     $shiftDesc = trim($doc['shift_description'] ?? $doc['shift_name'] ?? '');


     //                     if (empty($shiftId) && $shiftDesc !== '') {
     //                          $norm = strtoupper(preg_replace('/\s+|[-_]+/', ' ', $shiftDesc)); // normalize
     //                          // Try exact match (case-insensitive)
     //                          $ds = $doctorsShiftModel->where("UPPER(shift_name)", $norm)->first();
     //                          // Try flexible like if exact not found
     //                          if (!$ds) {
     //                               $ds = $doctorsShiftModel->like('shift_name', $shiftDesc)->first();
     //                          }
     //                          if ($ds) {
     //                               $shiftId = $ds['id'];
     //                          } else {
     //                               // Fallback to shiftslist table
     //                               $s = $db->table('shiftslist')->select('id')
     //                                    ->where("UPPER(ShiftName)", $norm)
     //                                    ->get()
     //                                    ->getRowArray();
     //                               if (!$s) {
     //                                    $s = $db->table('shiftslist')->select('id')->like('ShiftName', $shiftDesc)->get()->getRowArray();
     //                               }
     //                               if ($s) {
     //                                    $shiftId = $s['id'];
     //                               }
     //                          }
     //                     }

     //                     // optional debug log for shift resolution
     //                     log_message('error', "Shift resolution for emp {$empCode} ({$empId}) desc '{$shiftDesc}' resolved to id: " . ($shiftId ?? 'NULL'));

     //                     if (empty($shiftId)) {
     //                          $failed[] = [
     //                               'emp_id' => $empId,
     //                               'emp_code' => $empCode,
     //                               'reason' => 'shift_not_found',
     //                               'shift_description' => $shiftDesc
     //                          ];
     //                          continue;
     //                     }

     //                     $processedDoctors++;

     //                     // Insert roster for each date (skip if date exists)
     //                     foreach ($dates as $date) {
     //                          $existing = $dutyRosterModel
     //                               ->where('emp_id', $empId)
     //                               ->where('attendance_date', $date)
     //                               ->first();

     //                          if ($existing) {
     //                               continue;
     //                          }

     //                          // default weekoff: Sunday. If employee has explicit week_off that maps to date, you can add logic here.
     //                          $isSunday = (date('w', strtotime($date)) == 0);
     //                          $data = [
     //                               'emp_id' => $empId,
     //                               'shift_id' => $shiftId,
     //                               'attendance_date' => $date,
     //                               'custom_weekoff_date' => $isSunday ? $date : null,
     //                               'createdBy' => $user,
     //                               'createdDTM' => date('Y-m-d H:i:s')
     //                          ];

     //                          if ($dutyRosterModel->insert($data)) {
     //                               $success[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'date' => $date, 'action' => 'created'];
     //                          } else {
     //                               $failed[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'date' => $date, 'action' => 'create_failed', 'error' => $dutyRosterModel->errors()];
     //                          }
     //                     }
     //                } // end foreach doctors

     //                // mark auto generation completed
     //                if ($autoRecordId) {
     //                     $db->table('auto_generated_shifts')->where('id', $autoRecordId)->update(['is_completed' => 'Y']);
     //                }

     //                return $this->respond([
     //                     'status' => true,
     //                     'message' => 'Auto duty roster generation completed',
     //                     'month' => $month,
     //                     'date_range' => ['start' => $startDate, 'end' => $endDate],
     //                     'total_doctors_found' => count($doctors),
     //                     'processed_doctors' => $processedDoctors,
     //                     'skipped_doctors' => count($skipped ?? []),
     //                     'created' => count($success),
     //                     'failed' => count($failed),
     //                     'details' => [
     //                          'success' => $success,
     //                          'failed' => $failed,
     //                          'skipped' => $skipped ?? []
     //                     ]
     //                ], 200);
     //           }

     //           // Manual bulk behavior (existing) - validated only when not auto
     //           if (empty($json['shift_id']) || empty($json['emp_ids']) || !is_array($json['emp_ids'])) {
     //                return $this->respond([
     //                     'status' => false,
     //                     'message' => 'For manual mode month, shift_id, and emp_ids array are required'
     //                ], 400);
     //           }

     //           $shift_id = $json['shift_id'];
     //           $emp_ids = $json['emp_ids'];
     //           $emp_codes = $json['emp_code'] ?? [];

     //           $totalProcessed = 0;
     //           $success = [];
     //           $failed = [];

     //           foreach ($emp_ids as $index => $emp_id) {
     //                $emp_code = $emp_codes[$index] ?? null;
     //                foreach ($dates as $date) {
     //                     $totalProcessed++;
     //                     $isSunday = (date('w', strtotime($date)) == 0);
     //                     $data = [
     //                          'emp_id' => $emp_id,
     //                          'shift_id' => $shift_id,
     //                          'attendance_date' => $date,
     //                          'custom_weekoff_date' => $isSunday ? $date : null,
     //                          'createdBy' => $user,
     //                          'createdDTM' => date('Y-m-d H:i:s')
     //                     ];

     //                     $existing = $dutyRosterModel
     //                          ->where('emp_id', $emp_id)
     //                          ->where('attendance_date', $date)
     //                          ->first();

     //                     if ($existing) {
     //                          $updateData = $data;
     //                          $updateData['updatedBy'] = $user;
     //                          $updateData['updatedDTM'] = date('Y-m-d H:i:s');
     //                          if ($dutyRosterModel->update($existing['id'], $updateData)) {
     //                               $success[] = ['emp_id' => $emp_id, 'emp_code' => $emp_code, 'date' => $date, 'action' => 'updated'];
     //                          } else {
     //                               $failed[] = ['emp_id' => $emp_id, 'emp_code' => $emp_code, 'date' => $date, 'action' => 'update_failed', 'error' => $dutyRosterModel->errors()];
     //                          }
     //                     } else {
     //                          if ($dutyRosterModel->insert($data)) {
     //                               $success[] = ['emp_id' => $emp_id, 'emp_code' => $emp_code, 'date' => $date, 'action' => 'created'];
     //                          } else {
     //                               $failed[] = ['emp_id' => $emp_id, 'emp_code' => $emp_code, 'date' => $date, 'action' => 'create_failed', 'error' => $dutyRosterModel->errors()];
     //                          }
     //                     }
     //                }
     //           }

     //           return $this->respond([
     //                'status' => true,
     //                'message' => 'Bulk duty roster processed',
     //                'summary' => [
     //                     'total_employees' => count($emp_ids),
     //                     'total_days' => count($dates),
     //                     'total_records_processed' => $totalProcessed,
     //                     'successful' => count($success),
     //                     'failed' => count($failed)
     //                ],
     //                'month' => $month,
     //                'shift_id' => $shift_id ?? null,
     //                'date_range' => ['start' => $startDate, 'end' => $endDate],
     //                'success' => $success,
     //                'failed' => $failed
     //           ], 200);
     //      } catch (\Exception $e) {
     //           return $this->respond([
     //                'status' => false,
     //                'message' => 'Error: ' . $e->getMessage(),
     //                'trace' => $e->getTraceAsString()
     //           ], 500);
     //      }
     // }

     public function getDoctorsWithShiftMismatches()
     {
          try {
               $userDetails = $this->validateAuthorization();

               $db = \Config\Database::connect();
               $dutyRosterModel = $this->dutyRosterModel;

               // Reference date: November 3, 2025
               $referenceDate = '2025-11-03';

               // Fetch all doctors from employees table
               $doctors = $db->table('employees')
                    ->select('emp_id, employee_code, employee_name, shift_description')
                    ->where('emp_type', 'DOCTOR')
                    ->where('status', 'A')
                    ->where('isDeleted', 'N')
                    ->get()
                    ->getResultArray();

               $mismatchedDoctors = [];

               foreach ($doctors as $doctor) {
                    $empId = $doctor['emp_id'];
                    $empCode = $doctor['employee_code'];
                    $empName = $doctor['employee_name'];
                    $defaultShiftDesc = $doctor['shift_description'];

                    // Skip doctors without default shift
                    if (empty($defaultShiftDesc)) {
                         continue;
                    }

                    // Get shift assigned on November 3, 2025
                    $nov3Assignment = $dutyRosterModel
                         ->where('emp_id', $empId)
                         ->where('attendance_date', $referenceDate)
                         ->first();

                    $nov3ShiftId = $nov3Assignment ? $nov3Assignment['shift_id'] : null;

                    // Skip if no Nov 3 assignment
                    if (!$nov3ShiftId) {
                         continue;
                    }

                    // Get default shift ID from doctors_shift_master
                    $defaultShiftId = null;
                    $defaultShiftDetails = null;

                    if ($defaultShiftDesc) {
                         $shiftDetails = $db->table('doctors_shift_master')
                              ->select('id, shift_name, in_time, out_time')
                              ->like('shift_name', $defaultShiftDesc)
                              ->get()
                              ->getRowArray();

                         if ($shiftDetails) {
                              $defaultShiftId = $shiftDetails['id'];
                              $defaultShiftDetails = [
                                   'shift_name' => $shiftDetails['shift_name'],
                                   'in_time' => $shiftDetails['in_time'],
                                   'out_time' => $shiftDetails['out_time']
                              ];
                         }
                    }

                    // Get Nov 3 shift details
                    $nov3ShiftDetails = null;
                    if ($nov3ShiftId) {
                         $shiftDetails = $db->table('doctors_shift_master')
                              ->select('shift_name, in_time, out_time')
                              ->where('id', $nov3ShiftId)
                              ->get()
                              ->getRowArray();

                         if ($shiftDetails) {
                              $nov3ShiftDetails = [
                                   'shift_name' => $shiftDetails['shift_name'],
                                   'in_time' => $shiftDetails['in_time'],
                                   'out_time' => $shiftDetails['out_time']
                              ];
                         }
                    }

                    // Check for mismatch: has default shift AND Nov 3 shift AND they don't match
                    if ($defaultShiftId && $nov3ShiftId && $defaultShiftId != $nov3ShiftId) {
                         $mismatchedDoctors[] = [
                              'employee_code' => $empCode,
                              'employee_name' => $empName,
                              'emp_id' => $empId,
                              'default_shift_description' => $defaultShiftDesc,
                              'default_shift_id' => $defaultShiftId,
                              'default_shift_details' => $defaultShiftDetails,
                              'nov3_shift_id' => $nov3ShiftId,
                              'nov3_shift_details' => $nov3ShiftDetails,
                              'action_required' => 'REVIEW_MISMATCH'
                         ];
                    }
               }

               // Extract just the employee codes for easy reference
               $employeeCodes = array_column($mismatchedDoctors, 'employee_code');

               return $this->respond([
                    'status' => true,
                    'message' => 'Doctors with shift mismatches retrieved',
                    'reference_date' => $referenceDate,
                    'total_mismatched_doctors' => count($mismatchedDoctors),
                    'employee_codes' => $employeeCodes,
                    'detailed_report' => $mismatchedDoctors
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error fetching mismatched doctors: ' . $e->getMessage()
               ], 500);
          }
     }

     public function updateMismatchedDoctorsShifts()
     {
          try {
               $userDetails = $this->validateAuthorization();

               // First get all mismatched doctors
               $mismatchedResponse = $this->getDoctorsWithShiftMismatches();
               $responseData = json_decode($mismatchedResponse->getBody(), true);

               if (!$responseData['status'] || empty($responseData['detailed_report'])) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'No mismatched doctors found to update'
                    ], 404);
               }

               $db = \Config\Database::connect();
               $updated = 0;
               $failed = 0;
               $updateLog = [];

               // Start transaction
               $db->transStart();

               foreach ($responseData['detailed_report'] as $doctor) {
                    $employeeCode = $doctor['employee_code'];
                    $employeeName = $doctor['employee_name'];
                    $oldShiftName = $doctor['default_shift_details']['shift_name'] ?? 'Unknown';
                    $newShiftName = $doctor['nov3_shift_details']['shift_name'] ?? null;

                    if (!$newShiftName) {
                         $failed++;
                         $updateLog[] = [
                              'employee_code' => $employeeCode,
                              'employee_name' => $employeeName,
                              'status' => 'FAILED',
                              'reason' => 'No Nov 3 shift name found'
                         ];
                         continue;
                    }

                    // Update the employees table
                    $result = $db->table('employees')
                         ->where('employee_code', $employeeCode)
                         ->where('emp_type', 'DOCTOR')
                         ->update(['shift_description' => $newShiftName]);

                    if ($result) {
                         $updated++;
                         $updateLog[] = [
                              'employee_code' => $employeeCode,
                              'employee_name' => $employeeName,
                              'status' => 'SUCCESS',
                              'old_shift' => $oldShiftName,
                              'new_shift' => $newShiftName
                         ];
                    } else {
                         $failed++;
                         $updateLog[] = [
                              'employee_code' => $employeeCode,
                              'employee_name' => $employeeName,
                              'status' => 'FAILED',
                              'reason' => 'Database update failed'
                         ];
                    }
               }

               // Complete transaction
               $db->transComplete();

               if ($db->transStatus() === FALSE) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Transaction failed - no updates were made'
                    ], 500);
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'Bulk shift update completed',
                    'summary' => [
                         'total_doctors' => count($responseData['detailed_report']),
                         'updated_successfully' => $updated,
                         'failed_updates' => $failed,
                         'update_percentage' => count($responseData['detailed_report']) > 0
                              ? round(($updated / count($responseData['detailed_report'])) * 100, 2)
                              : 0
                    ],
                    'update_log' => $updateLog
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error updating shifts: ' . $e->getMessage()
               ], 500);
          }
     }
     public function validateDoctorShiftsForDecember2025()
     {
          try {
               $userDetails = $this->validateAuthorization();

               $db = \Config\Database::connect();
               $dutyRosterModel = $this->dutyRosterModel;

               // Reference date: November 3, 2025
               $referenceDate = '2025-11-03';

               // Fetch all doctors from employees table - use shift_description instead of default_shift_id
               $doctors = $db->table('employees')
                    ->select('emp_id, employee_code, employee_name, shift_description')
                    ->where('emp_type', 'DOCTOR')
                    ->where('status', 'A')
                    ->where('isDeleted', 'N')
                    ->get()
                    ->getResultArray();

               $report = [];
               $summary = [
                    'total_doctors' => count($doctors),
                    'doctors_with_default_shift' => 0,
                    'doctors_without_default_shift' => 0,
                    'shift_matches' => 0,
                    'shift_mismatches' => 0,
                    'no_nov3_assignment' => 0,
                    'ready_for_december' => 0
               ];

               foreach ($doctors as $doctor) {
                    $empId = $doctor['emp_id'];
                    $empCode = $doctor['employee_code'];
                    $empName = $doctor['employee_name'];
                    $defaultShiftDesc = $doctor['shift_description'];

                    // Check if doctor has default shift description
                    $hasDefaultShift = !empty($defaultShiftDesc);

                    if ($hasDefaultShift) {
                         $summary['doctors_with_default_shift']++;
                    } else {
                         $summary['doctors_without_default_shift']++;
                    }

                    // Get shift assigned on November 3, 2025
                    $nov3Assignment = $dutyRosterModel
                         ->where('emp_id', $empId)
                         ->where('attendance_date', $referenceDate)
                         ->first();

                    $nov3ShiftId = $nov3Assignment ? $nov3Assignment['shift_id'] : null;

                    // Get shift details
                    $defaultShiftName = $defaultShiftDesc;
                    $defaultShiftId = null;
                    $nov3ShiftName = null;
                    $defaultShiftDetails = null;
                    $nov3ShiftDetails = null;

                    // Try to find matching shift ID from doctors_shift_master based on shift_description
                    if ($defaultShiftDesc) {
                         $shiftDetails = $db->table('doctors_shift_master')
                              ->select('id, shift_name, in_time, out_time')
                              ->like('shift_name', $defaultShiftDesc)
                              ->get()
                              ->getRowArray();

                         if ($shiftDetails) {
                              $defaultShiftId = $shiftDetails['id'];
                              $defaultShiftName = $shiftDetails['shift_name'];
                              $defaultShiftDetails = [
                                   'shift_name' => $shiftDetails['shift_name'],
                                   'in_time' => $shiftDetails['in_time'],
                                   'out_time' => $shiftDetails['out_time']
                              ];
                         }
                    }

                    if ($nov3ShiftId) {
                         $shiftDetails = $db->table('doctors_shift_master')
                              ->select('shift_name, in_time, out_time')
                              ->where('id', $nov3ShiftId)
                              ->get()
                              ->getRowArray();

                         if ($shiftDetails) {
                              $nov3ShiftName = $shiftDetails['shift_name'];
                              $nov3ShiftDetails = [
                                   'shift_name' => $shiftDetails['shift_name'],
                                   'in_time' => $shiftDetails['in_time'],
                                   'out_time' => $shiftDetails['out_time']
                              ];
                         }
                    }

                    // Compare shifts
                    $shiftMatch = null;
                    $status = '';
                    $readyForDecember = false;

                    if (!$hasDefaultShift && !$nov3ShiftId) {
                         $status = 'NO_DEFAULT_NO_NOV3';
                         $summary['no_nov3_assignment']++;
                    } elseif (!$hasDefaultShift && $nov3ShiftId) {
                         $status = 'NO_DEFAULT_HAS_NOV3';
                         $summary['no_nov3_assignment']++;
                    } elseif ($hasDefaultShift && !$nov3ShiftId) {
                         $status = 'HAS_DEFAULT_NO_NOV3';
                         $summary['no_nov3_assignment']++;
                         $readyForDecember = true;
                    } elseif ($hasDefaultShift && $nov3ShiftId) {
                         // Compare shift_description with Nov 3 shift name
                         if ($defaultShiftId && $defaultShiftId == $nov3ShiftId) {
                              $shiftMatch = true;
                              $status = 'MATCH';
                              $summary['shift_matches']++;
                              $readyForDecember = true;
                         } else {
                              $shiftMatch = false;
                              $status = 'MISMATCH';
                              $summary['shift_mismatches']++;
                         }
                    }

                    if ($readyForDecember) {
                         $summary['ready_for_december']++;
                    }

                    $report[] = [
                         'emp_id' => $empId,
                         'employee_code' => $empCode,
                         'employee_name' => $empName,
                         'default_shift_description' => $defaultShiftDesc,
                         'default_shift_id' => $defaultShiftId,
                         'default_shift_name' => $defaultShiftName,
                         'default_shift_details' => $defaultShiftDetails,
                         'nov3_shift_id' => $nov3ShiftId,
                         'nov3_shift_name' => $nov3ShiftName,
                         'nov3_shift_details' => $nov3ShiftDetails,
                         'shift_match' => $shiftMatch,
                         'status' => $status,
                         'ready_for_december' => $readyForDecember,
                         'action_required' => !$readyForDecember ? ($hasDefaultShift ? 'REVIEW_MISMATCH' : 'SET_DEFAULT_SHIFT') : 'NONE'
                    ];
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'Doctor shift validation completed',
                    'reference_date' => $referenceDate,
                    'summary' => $summary,
                    'ready_percentage' => count($doctors) > 0 ? round(($summary['ready_for_december'] / count($doctors)) * 100, 2) : 0,
                    'report' => $report,
                    'status_legend' => [
                         'NO_DEFAULT_NO_NOV3' => 'No default shift and no Nov 3 assignment',
                         'NO_DEFAULT_HAS_NOV3' => 'No default shift but has Nov 3 assignment',
                         'HAS_DEFAULT_NO_NOV3' => 'Has default shift but no Nov 3 assignment',
                         'MATCH' => 'Default shift matches Nov 3 assignment',
                         'MISMATCH' => 'Default shift differs from Nov 3 assignment'
                    ]
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error validating doctor shifts: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
               ], 500);
          }
     }

     public function updateShiftforEmployee()
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['user_code'];

               $db = \Config\Database::connect();

               // Fetch all records from contractual_emp_shifts
               $contractualShifts = $db->table('contractual_emp_shifts')
                    ->select('emp_code, shift_name')
                    ->get()
                    ->getResultArray();

               if (empty($contractualShifts)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'No records found in contractual_emp_shifts table'
                    ], 404);
               }

               $updated = 0;
               $failed = 0;
               $updateLog = [];

               // Start transaction
               $db->transStart();

               foreach ($contractualShifts as $shift) {
                    $empCode = $shift['emp_code'];
                    $shiftName = $shift['shift_name'];

                    // Update employees table
                    $result = $db->table('employees')
                         ->where('employee_code', $empCode)
                         ->update(['shift_description' => $shiftName]);

                    if ($result) {
                         $updated++;
                         $updateLog[] = [
                              'employee_code' => $empCode,
                              'shift_name' => $shiftName,
                              'status' => 'SUCCESS'
                         ];
                    } else {
                         $failed++;
                         $updateLog[] = [
                              'employee_code' => $empCode,
                              'shift_name' => $shiftName,
                              'status' => 'FAILED',
                              'reason' => 'Database update failed'
                         ];
                    }
               }

               // Complete transaction
               $db->transComplete();

               if ($db->transStatus() === false) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Transaction failed - no updates were made'
                    ], 500);
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'Shift descriptions updated successfully',
                    'summary' => [
                         'total_records' => count($contractualShifts),
                         'updated' => $updated,
                         'failed' => $failed
                    ],
                    'update_log' => $updateLog
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
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

     // shifts list
     public function getShifts()
     {
          $userDetails = $this->validateAuthorization();
          $data = $this->shiftListsModel
               ->where('status', 'A')
               ->orderBy('ShiftName', 'ASC')
               ->findAll();
          return $this->respond(['status' => 200, 'error' => null, 'data' => $data]);
     }

     public function getSplitShifts()
     {
          $userDetails = $this->validateAuthorization();
          $data = $this->shiftListsModel
               ->where('status', 'A')
               ->where('split_shift', 'Y')
               ->findAll();
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

     public function getEmpByEmpCode($emp_code)
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $dutyRosterModel = new DutyRosterModel();
          $user = $dutyRosterModel->getEmpByEmpCode($emp_code);

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

          $employee_code = $json['employee_code'] ?? null;
          $selectedMonth = $json['selectedMonth'] ?? null;
          $selectedToMonth = $json['selectedToMonth'] ?? null;

          if (empty($employee_code) || empty($selectedMonth)) {
               return $this->respond([
                    'status' => false,
                    'message' => 'employee_code and selectedMonth are required'
               ], 400);
          }

          // resolve employee and split-shift flag
          $employeeDetailsResp = $this->getEmpByEmpCode($employee_code);
          $empBody = json_decode($employeeDetailsResp->getBody(), true);
          if (empty($empBody['status']) || empty($empBody['data'])) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Invalid employee_code'
               ], 400);
          }
          $split_shift_enabled = $empBody['data']['split_shift'] ?? 'N';

          // build date range
          $startDate = $selectedMonth . '-01';
          $endDate = date('Y-m-t', strtotime($startDate));
          if (!empty($selectedToMonth)) {
               $toStart = $selectedToMonth . '-01';
               $endDate = date('Y-m-t', strtotime($toStart));
          }

          // fetch attendance (preserve original structure)
          if ($split_shift_enabled === 'Y') {
               $dutyRosterModel = new DutyRosterModel();
               $attendanceData = $dutyRosterModel->getEmployeeAttendanceWithSplitShift($employee_code, $selectedMonth, $selectedToMonth);
          } else {
               $dutyRosterModel = new DutyRosterModel();
               $attendanceData = $dutyRosterModel->getEmployeeAttendance($employee_code, $selectedMonth, $selectedToMonth);
          }
          $attendanceData = $attendanceData ?: [];

          // fetch regularize requests for the same date range
          $db = \Config\Database::connect();
          $regularizeRows = $db->table('regularize_requests')
               ->where('emp_code', $employee_code)
               ->where('for_date >=', $startDate)
               ->where('for_date <=', $endDate)
               ->orderBy('for_date', 'ASC')
               ->get()
               ->getResultArray();

          if (!empty($regularizeRows)) {
               // attach attachments
               $reqIds = array_column($regularizeRows, 'id');
               $atts = $db->table('regularize_attachments')
                    ->whereIn('request_id', $reqIds)
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();

               $attachmentsMap = [];
               foreach ($atts as $a) {
                    $attachmentsMap[$a['request_id']][] = $a;
               }
               foreach ($regularizeRows as &$r) {
                    $r['attachments'] = $attachmentsMap[$r['id']] ?? [];
               }
               unset($r);

               // enrich names using RegularizeModel helper
               try {
                    $regularizeModel = new \App\Models\RegularizeModel();
                    $regularizeRows = $regularizeModel->enrichWithEmployeeNames($regularizeRows);
               } catch (\Throwable $e) {
                    log_message('error', 'DutyRoster::getEmployeeAttendance enrich regularize failed: ' . $e->getMessage());
               }
          }

          // Respond: keep attendance 'data' unchanged and return regularize as a separate key
          if (!empty($attendanceData)) {
               return $this->respond([
                    'status' => true,
                    'data' => $attendanceData,
                    'regularize' => $regularizeRows,
                    'monthYear' => $selectedMonth,
                    'total_records' => count($attendanceData),
                    'regularize_count' => count($regularizeRows)
               ], 200);
          }

          // No attendance rows but regularize exists -> return 200 with empty data + regularize
          if (!empty($regularizeRows)) {
               return $this->respond([
                    'status' => true,
                    'data' => [],
                    'regularize' => $regularizeRows,
                    'monthYear' => $selectedMonth,
                    'total_records' => 0,
                    'regularize_count' => count($regularizeRows),
                    'message' => 'No attendance records found for selected month; returning regularize requests'
               ], 200);
          }

          // Neither attendance nor regularize -> original 404 behavior
          return $this->respond([
               'status' => false,
               'message' => "No attendance data found for {$selectedMonth}"
          ], 404);
     }

     // public function getEmployeeAttendance()
     // {
     //      $userDetails = $this->validateAuthorization();
     //      $user = $userDetails['emp_code'];
     //      $json = $this->request->getJSON(true);

     //      // Get year and month from query parameters
     //      $employee_code = $json['employee_code'] ?? null;
     //      $selectedMonth = $json['selectedMonth'] ?? null;
     //      $selectedToMonth = $json['selectedToMonth'] ?? null;

     //      if (empty($employee_code) || empty($selectedMonth)) {
     //           return $this->respond([
     //                'status' => false,
     //                'message' => 'employee_code and selectedMonth are required'
     //           ], 400);
     //      }

     //      // get Employee details from employee_code
     //      $employeeDetails = $this->getEmpByEmpCode($employee_code);
     //      if (!$employeeDetails->getJSON()) {
     //           return $this->respond([
     //                'status' => false,
     //                'message' => 'Invalid employee_code'
     //           ], 400);
     //      }
     //      $split_shift_enabled = json_decode($employeeDetails->getBody(), true)['data']['split_shift'] ?? 'N';

     //      if ($split_shift_enabled == 'Y') {
     //           $dutyRosterModel = new DutyRosterModel();
     //           $attendanceData = $dutyRosterModel->getEmployeeAttendanceWithSplitShift($employee_code, $selectedMonth, $selectedToMonth);
     //      } else {
     //           $dutyRosterModel = new DutyRosterModel();
     //           $attendanceData = $dutyRosterModel->getEmployeeAttendance($employee_code, $selectedMonth, $selectedToMonth);
     //      }

     //      if ($attendanceData) {
     //           return $this->respond([
     //                'status' => true,
     //                'data' => $attendanceData,
     //                'monthYear' => $selectedMonth,
     //                'total_records' => count($attendanceData)
     //           ], 200);
     //      } else {
     //           return $this->respond([
     //                'status' => false,
     //                'message' => "No attendance data found for {$selectedMonth}"
     //           ], 404);
     //      }
     // }

     public function getEmployeeAttendanceOnDate()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['emp_code'];
          $json = $this->request->getJSON(true);

          // Get employee_code and date from query parameters
          $employee_code = $json['employee_code'] ?? null;
          $date = $json['date'] ?? null;

          if (empty($employee_code) || empty($date)) {
               return $this->respond([
                    'status' => false,
                    'message' => 'employee_code and date are required'
               ], 400);
          }

          $dutyRosterModel = new DutyRosterModel();
          $attendanceData = $dutyRosterModel->getEmployeeAttendanceOnDate($employee_code, $date);

          if ($attendanceData) {
               return $this->respond([
                    'status' => true,
                    'data' => $attendanceData
               ], 200);
          } else {
               return $this->respond([
                    'status' => false,
                    'message' => "No attendance data found for {$date}"
               ], 404);
          }
     }

     public function getAllDoctorEmployeeAttendanceForSelectedMonth()
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['emp_code'];
               $json = $this->request->getJSON(true);

               // Get year and month from query parameters
               $selectedMonth = $json['selectedMonth'] ?? null;
               $selectedToMonth = $json['selectedToMonth'] ?? null;
               $emp_type = $json['emp_type'] ?? 'DOCTOR'; // Default to DOCTOR

               if (empty($selectedMonth)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'selectedMonth is required'
                    ], 400);
               }

               // Validate emp_type
               $allowedEmpTypes = ['DOCTOR', 'CONTRACTUAL_EMPLOYEE', 'POOJARI'];
               if (!in_array($emp_type, $allowedEmpTypes)) {
                    $emp_type = 'DOCTOR';
               }

               $db = \Config\Database::connect();

               // Calculate date range for filtering
               $startDate = $selectedMonth . '-01';
               $endDate = date('Y-m-t', strtotime($startDate));

               // If selectedToMonth is provided, extend the end date
               if (!empty($selectedToMonth)) {
                    $toStartDate = $selectedToMonth . '-01';
                    $endDate = date('Y-m-t', strtotime($toStartDate));
               }

               // OPTIMIZED: Single query with INNER JOIN for faster performance (70-90% faster)
               // Database indexes recommended: employees(emp_type, status, isDeleted), duty_roster(emp_id, attendance_date)
               $builder = $db->table('employees e');
               $builder->select('e.emp_id, e.employee_code, e.employee_name, e.emp_type');
               $builder->join('duty_roster dr', 'e.emp_id = dr.emp_id', 'inner'); // INNER JOIN - only employees with attendance
               $builder->where('e.emp_type', $emp_type);
               $builder->where('e.status', 'A');
               $builder->where('e.isDeleted', 'N');
               $builder->where('dr.attendance_date >=', $startDate);
               $builder->where('dr.attendance_date <=', $endDate);
               $builder->groupBy('e.emp_id');
               $builder->orderBy('e.employee_code', 'ASC');

               $employees = $builder->get()->getResultArray();

               if (empty($employees)) {
                    return $this->respond([
                         'status' => false,
                         'message' => "No {$emp_type} employees found"
                    ], 404);
               }

               // Fetch all attendance records in one query
               $empIds = array_column($employees, 'emp_id');
               $dutyRosterModel = new DutyRosterModel();

               $allAttendanceRecords = $db->table('duty_roster dr')
                    ->select('dr.id, dr.emp_id, dr.shift_id, dr.attendance_date, dr.custom_weekoff_date,
                              dr.createdDTM, dr.updatedDTM,
                              s.ShiftName as shift_name, s.ShiftStart as shift_in_time, 
                              s.ShiftEnd as shift_out_time')
                    ->join('shiftslist s', 'dr.shift_id = s.id', 'left')
                    ->whereIn('dr.emp_id', $empIds)
                    ->where('dr.attendance_date >=', $startDate)
                    ->where('dr.attendance_date <=', $endDate)
                    ->orderBy('dr.emp_id, dr.attendance_date', 'ASC')
                    ->get()
                    ->getResultArray();

               // Group attendance by emp_id for quick lookup
               $attendanceByEmp = [];
               foreach ($allAttendanceRecords as $record) {
                    $attendanceByEmp[$record['emp_id']][] = $record;
               }

               $allAttendanceData = [];
               $totalRecords = 0;

               // Build response data
               foreach ($employees as $employee) {
                    $empId = $employee['emp_id'];
                    $attendanceData = $attendanceByEmp[$empId] ?? [];

                    if (!empty($attendanceData)) {
                         $allAttendanceData[] = [
                              'emp_id' => $employee['emp_id'],
                              'employee_code' => $employee['employee_code'],
                              'employee_name' => $employee['employee_name'],
                              'emp_type' => $employee['emp_type'],
                              'attendance' => $attendanceData,
                              'total_days' => count($attendanceData)
                         ];
                         $totalRecords += count($attendanceData);
                    }
               }

               return $this->respond([
                    'status' => true,
                    'data' => $allAttendanceData,
                    'summary' => [
                         'emp_type' => $emp_type,
                         'total_employees' => count($employees),
                         'employees_with_attendance' => count($allAttendanceData),
                         'total_attendance_records' => $totalRecords,
                         'monthYear' => $selectedMonth,
                         'toMonthYear' => $selectedToMonth
                    ]
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error fetching attendance: ' . $e->getMessage()
               ], 500);
          }
     }

     public function getAllContractualEmployeeAttendanceForSelectedMonth()
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['emp_code'];
               $json = $this->request->getJSON(true);

               // Get year and month from query parameters
               $selectedMonth = $json['selectedMonth'] ?? null;
               $selectedToMonth = $json['selectedToMonth'] ?? null;

               if (empty($selectedMonth)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'selectedMonth is required'
                    ], 400);
               }

               $dutyRosterModel = new DutyRosterModel();
               $db = \Config\Database::connect();

               // Fetch contractual employees from employees table
               $employees = $db->table('employees')
                    ->select('emp_id, employee_code, employee_name, emp_type, department, designation, location_name, shift_description')
                    ->where('emp_type', 'CONTRACTUAL_EMPLOYEE')
                    ->where('status', 'A')
                    ->where('isDeleted', 'N')
                    ->get()
                    ->getResultArray();

               if (empty($employees)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'No contractual employees found'
                    ], 404);
               }

               $allAttendanceData = [];
               $totalRecords = 0;

               // Fetch attendance for each employee
               foreach ($employees as $employee) {
                    $employeeCode = $employee['employee_code'];
                    $attendanceData = $dutyRosterModel->getEmployeeAttendance($employeeCode, $selectedMonth, $selectedToMonth);

                    if ($attendanceData && !empty($attendanceData)) {
                         $allAttendanceData[] = [
                              'emp_id' => $employee['emp_id'],
                              'employee_code' => $employeeCode,
                              'employee_name' => $employee['employee_name'],
                              'emp_type' => $employee['emp_type'],
                              'department' => $employee['department'],
                              'designation' => $employee['designation'],
                              'location_name' => $employee['location_name'],
                              'shift_description' => $employee['shift_description'],
                              'attendance' => $attendanceData,
                              'total_days' => count($attendanceData)
                         ];
                         $totalRecords += count($attendanceData);
                    }
               }

               return $this->respond([
                    'status' => true,
                    'data' => $allAttendanceData,
                    'summary' => [
                         'emp_type' => 'CONTRACTUAL_EMPLOYEE',
                         'total_employees' => count($employees),
                         'employees_with_attendance' => count($allAttendanceData),
                         'total_attendance_records' => $totalRecords,
                         'monthYear' => $selectedMonth,
                         'toMonthYear' => $selectedToMonth
                    ]
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error fetching contractual employee attendance: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
               ], 500);
          }
     }


     public function getAllPoojariEmployeeAttendanceForSelectedMonth()
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['emp_code'];
               $json = $this->request->getJSON(true);

               // Get year and month from query parameters
               $selectedMonth = $json['selectedMonth'] ?? null;
               $selectedToMonth = $json['selectedToMonth'] ?? null;

               if (empty($selectedMonth)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'selectedMonth is required'
                    ], 400);
               }

               $dutyRosterModel = new DutyRosterModel();
               $db = \Config\Database::connect();

               // Fetch POOJARI employees from employees table
               $employees = $db->table('employees')
                    ->select('emp_id, employee_code, employee_name, emp_type, department, designation, location_name, shift_description')
                    ->where('emp_type', 'POOJARI')
                    ->where('status', 'A')
                    ->where('isDeleted', 'N')
                    ->get()
                    ->getResultArray();

               if (empty($employees)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'No POOJARI employees found'
                    ], 404);
               }

               $allAttendanceData = [];
               $totalRecords = 0;

               // Fetch attendance for each employee
               foreach ($employees as $employee) {
                    $employeeCode = $employee['employee_code'];
                    $attendanceData = $dutyRosterModel->getEmployeeAttendance($employeeCode, $selectedMonth, $selectedToMonth);

                    if ($attendanceData && !empty($attendanceData)) {
                         $allAttendanceData[] = [
                              'emp_id' => $employee['emp_id'],
                              'employee_code' => $employeeCode,
                              'employee_name' => $employee['employee_name'],
                              'emp_type' => $employee['emp_type'],
                              'department' => $employee['department'],
                              'designation' => $employee['designation'],
                              'location_name' => $employee['location_name'],
                              'shift_description' => $employee['shift_description'],
                              'attendance' => $attendanceData,
                              'total_days' => count($attendanceData)
                         ];
                         $totalRecords += count($attendanceData);
                    }
               }

               return $this->respond([
                    'status' => true,
                    'data' => $allAttendanceData,
                    'summary' => [
                         'emp_type' => 'POOJARI',
                         'total_employees' => count($employees),
                         'employees_with_attendance' => count($allAttendanceData),
                         'total_attendance_records' => $totalRecords,
                         'monthYear' => $selectedMonth,
                         'toMonthYear' => $selectedToMonth
                    ]
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error fetching POOJARI employee attendance: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
               ], 500);
          }
     }


     public function autoGenerateDutyRosterForPoojari()
     {
          try {
               $user = 'System';
               $json = $this->request->getJSON(true);

               $dutyRosterModel = $this->dutyRosterModel;
               $employeeModel = new \App\Models\NewEmployeeMasterModel();

               $db = \Config\Database::connect();

               // Accept month from several keys; default to current month
               $rawMonth = $json['month'] ?? $json['selectedMonth'] ?? null;
               if (empty($rawMonth)) {
                    $month = date('Y-m');
               } else {
                    $ts = strtotime($rawMonth);
                    if ($ts === false) {
                         $month = preg_match('/^\d{6}$/', $rawMonth) ? substr($rawMonth, 0, 4) . '-' . substr($rawMonth, 4, 2) : null;
                    } else {
                         $month = date('Y-m', $ts);
                    }
               }

               if (empty($month) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'Invalid or missing month. Use YYYY-MM'
                    ], 400);
               }

               // Build date range
               $startDate = $month . '-01';
               $endDate = date('Y-m-t', strtotime($startDate));
               $dates = [];
               $cur = $startDate;
               while ($cur <= $endDate) {
                    $dates[] = $cur;
                    $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
               }

               $success = [];
               $failed = [];
               $skipped = [];
               $processedEmployees = 0;

               // Fetch POOJARI employees
               $employees = [];
               try {
                    if ($db->tableExists('employees')) {
                         $employees = $db->table('employees')
                              ->where('emp_type', 'POOJARI')
                              ->where('status', 'A')
                              ->where('isDeleted', 'N')
                              ->get()
                              ->getResultArray();
                    }
               } catch (\Exception $e) {
                    $employees = [];
               }

               if (empty($employees)) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'No POOJARI employees found to auto-generate roster'
                    ], 200);
               }

               foreach ($employees as $employee) {
                    $empId = $employee['emp_id'] ?? null;
                    $empCode = $employee['employee_code'] ?? null;
                    $shiftDesc = $employee['shift_description'] ?? null;

                    if (empty($empId)) {
                         $failed[] = ['emp_id' => null, 'emp_code' => $empCode, 'reason' => 'missing_emp_id'];
                         continue;
                    }

                    // If any roster exists for this emp in the month, skip
                    $existsForMonth = $dutyRosterModel
                         ->where('emp_id', $empId)
                         ->where('attendance_date >=', $startDate)
                         ->where('attendance_date <=', $endDate)
                         ->first();

                    if ($existsForMonth) {
                         $skipped[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'reason' => 'roster_exists'];
                         continue;
                    }

                    // Resolve shift ID from shift description
                    $shiftId = null;
                    if (!empty($shiftDesc)) {
                         $shift = $db->table('shiftslist')
                              ->select('id')
                              ->where('ShiftName', $shiftDesc)
                              ->get()
                              ->getRowArray();

                         if ($shift) {
                              $shiftId = $shift['id'];
                         }
                    }

                    if (empty($shiftId)) {
                         $failed[] = [
                              'emp_id' => $empId,
                              'emp_code' => $empCode,
                              'reason' => 'shift_not_found',
                              'shift_description' => $shiftDesc
                         ];
                         continue;
                    }

                    $processedEmployees++;

                    // Insert roster for each date (skip if date exists)
                    foreach ($dates as $date) {
                         $existing = $dutyRosterModel
                              ->where('emp_id', $empId)
                              ->where('attendance_date', $date)
                              ->first();

                         if ($existing) {
                              continue;
                         }

                         // Default weekoff: Sunday
                         $isSunday = (date('w', strtotime($date)) == 0);
                         $data = [
                              'emp_id' => $empId,
                              'shift_id' => $shiftId,
                              'attendance_date' => $date,
                              'custom_weekoff_date' => $isSunday ? $date : null,
                              'createdBy' => $user,
                              'createdDTM' => date('Y-m-d H:i:s')
                         ];

                         if ($dutyRosterModel->insert($data)) {
                              $success[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'date' => $date, 'action' => 'created'];
                         } else {
                              $failed[] = ['emp_id' => $empId, 'emp_code' => $empCode, 'date' => $date, 'action' => 'create_failed', 'error' => $dutyRosterModel->errors()];
                         }
                    }
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'Auto duty roster generation for POOJARI employees completed',
                    'month' => $month,
                    'date_range' => ['start' => $startDate, 'end' => $endDate],
                    'total_employees_found' => count($employees),
                    'processed_employees' => $processedEmployees,
                    'skipped_employees' => count($skipped),
                    'created' => count($success),
                    'failed' => count($failed),
                    'details' => [
                         'success' => $success,
                         'failed' => $failed,
                         'skipped' => $skipped
                    ]
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
               ], 500);
          }
     }
}
