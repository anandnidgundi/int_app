<?php


namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\NewUserModel;        // employees table
use App\Models\LeaveModel;          // leave_requests
use App\Models\DoctorsShiftMasterModel;
use App\Models\DutyRosterModel;

class HodController extends BaseController
{
     use ResponseTrait;

     protected $db;
     protected $payrollDb;
     protected $employeeModel;
     protected $leaveModel;
     protected $doctorsShiftMasterModel;
     protected $dutyRosterModel;

     public function __construct()
     {
          $this->db = \Config\Database::connect();
          // Connect to secondary (payroll) DB if configured
          try {
               $this->payrollDb = \Config\Database::connect('secondary');
          } catch (\Throwable $e) {
               $this->payrollDb = null;
          }
          $this->employeeModel = new NewUserModel();
          $this->leaveModel = new LeaveModel();
          $this->doctorsShiftMasterModel = new DoctorsShiftMasterModel();
          $this->dutyRosterModel = new DutyRosterModel();
     }

     /**
      * GET /hod/doctorSummary
      */

     public function doctorSummary()
     {
          try {

               // --- 1. DATE VALIDATION ---
               $today = date('Y-m-d');
               $selectedDate = $this->request->getGet('date') ?? $today;
               if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
                    $selectedDate = $today;
               }

               // Better for attendance range search
               $startDateTime = $selectedDate . " 00:00:00";
               $endDateTime   = date('Y-m-d H:i:s', strtotime($selectedDate . " +1 day"));

               // --- 2. EMPLOYEE TYPE FILTER ---
               $empType = strtoupper($this->request->getGet('emp_type') ?? '');
               $allowedEmpTypes = ['DOCTOR', 'CONTRACTUAL_EMPLOYEE', 'POOJARI'];
               if (!in_array($empType, $allowedEmpTypes)) {
                    $empType = '';
               }

               // --- 3. FETCH ALL EMPLOYEES IN ONE GO ---
               $employeesQuery = $this->employeeModel
                    ->select('emp_id, employee_code, employee_name, emp_type, city_name, location_name, mobile ')
                    ->where('status', 'A')
                    ->where('isDeleted', 'N');

               if (!empty($empType)) {
                    $employeesQuery->where('emp_type', $empType);
               }

               if ($this->userRole === 'REPORTING_MANAGER' || $this->userRole === 'HOD_DOCTORS') {
                    $employeesQuery->groupStart()
                         ->where('reporting_manager_empcode', $this->userEmpCode)
                         ->orWhere('skip_level_manager_empcode', $this->userEmpCode)
                         ->groupEnd();
               }

               $employees = $employeesQuery->findAll();

               if (empty($employees)) {
                    return $this->respond([
                         'status' => true,
                         'data' => ['message' => 'No employees found']
                    ]);
               }

               // Convert to quick lookup array
               $empCodes = array_column($employees, 'employee_code');

               // --- 4. GET ALL ATTENDANCE FOR THESE EMPLOYEES IN ONE QUERY ---
               $db = \Config\Database::connect('secondary');
               $attendanceRows = $db->table('new_punch_list')
                    ->select('UserId, LogDate')
                    ->whereIn('UserId', $empCodes)
                    ->where('LogDate >=', $startDateTime)
                    ->where('LogDate <', $endDateTime)
                    ->orderBy('UserId ASC, LogDate ASC')
                    ->get()
                    ->getResultArray();

               // Group attendance by employee
               $attendanceMap = [];
               foreach ($attendanceRows as $row) {
                    $attendanceMap[$row['UserId']][] = $row['LogDate'];
               }

               // --- 5. GET DUTY ROSTER FOR ALL EMPLOYEES IN ONE QUERY ---
               $empIds = array_column($employees, 'emp_id');

               $dutyRows = $this->dutyRosterModel
                    ->select("
                duty_roster.*,
                doctors_shift_master.shift_name as doc_shift_name,
                doctors_shift_master.in_time as doc_in,
                doctors_shift_master.out_time as doc_out,
                shiftslist.ShiftName as other_shift_name,
                shiftslist.ShiftStart as other_in,
                shiftslist.ShiftEnd as other_out
            ")
                    ->join('doctors_shift_master', 'duty_roster.shift_id = doctors_shift_master.id', 'left')
                    ->join('shiftslist', 'duty_roster.shift_id = shiftslist.id', 'left')
                    ->whereIn('duty_roster.emp_id', $empIds)
                    ->where('duty_roster.attendance_date', $selectedDate)
                    ->get()
                    ->getResultArray();

               // Convert duty roster to quick lookup
               $dutyMap = [];
               foreach ($dutyRows as $dr) {
                    $dutyMap[$dr['emp_id']] = $dr;
               }

               // --- 6. COMPUTE SUMMARY IN MEMORY (FAST) ---
               $presentCount = 0;
               $absentCount  = 0;
               $details = [];

               foreach ($employees as $emp) {

                    $logs = $attendanceMap[$emp['employee_code']] ?? [];

                    $status    = empty($logs) ? 'absent' : 'present';
                    $punchIn   = empty($logs) ? null : $logs[0];
                    $punchOut  = empty($logs) ? null : end($logs);

                    if ($status === 'present') $presentCount++;
                    else $absentCount++;

                    // Shift details
                    $shift = $dutyMap[$emp['emp_id']] ?? null;

                    if ($shift) {
                         $shiftDetails = [
                              'shift_name' => $emp['emp_type'] === 'DOCTOR'
                                   ? $shift['doc_shift_name']
                                   : $shift['other_shift_name'],
                              'in_time' => $emp['emp_type'] === 'DOCTOR'
                                   ? $shift['doc_in']
                                   : $shift['other_in'],
                              'out_time' => $emp['emp_type'] === 'DOCTOR'
                                   ? $shift['doc_out']
                                   : $shift['other_out'],
                         ];
                    } else {
                         $shiftDetails = null;
                    }

                    $details[] = [
                         'emp_id' => $emp['emp_id'],
                         'employee_code' => $emp['employee_code'],
                         'employee_name' => $emp['employee_name'],
                         'city_name' => $emp['city_name'],
                         'location_name' => $emp['location_name'],
                         'mobile' => $emp['mobile'] ?? null,
                         'status' => $status,
                         'status_date' => $selectedDate,
                         'punch_in' => $punchIn,
                         'punch_out' => $punchOut,
                         'emp_type' => $emp['emp_type'],
                         'shift_details' => $shiftDetails
                    ];
               }

               // Sort by punch_in desc
               usort($details, function ($a, $b) {
                    return strcmp($b['punch_in'] ?? '', $a['punch_in'] ?? '');
               });

               // --- FINAL RESPONSE ---
               return $this->respond([
                    'status' => true,
                    'data' => [
                         'selected_date' => $selectedDate,
                         'emp_type' => $empType,
                         'summary' => [
                              'total' => count($employees),
                              'present' => $presentCount,
                              'absent' => $absentCount,
                              'on_leave' => 0,
                              'duty_not_started' => 0
                         ],
                         'details' => $details
                    ]
               ], 200);
          } catch (\Throwable $e) {
               return $this->failServerError('Error computing summary: ' . $e->getMessage());
          }
     }



     public function doctorCustomSummary()
     {
          try {
               $today = date('Y-m-d');
               $selectedDate = $this->request->getGet('date') ?? $today;
               if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
                    $selectedDate = $today;
               }

               $startDateTime = $selectedDate . ' 00:00:00';
               $endDateTime   = date('Y-m-d H:i:s', strtotime($selectedDate . ' +1 day'));

               $requestedInTime  = $this->request->getGet('in_time') ?? $this->request->getGet('inTime');
               $requestedOutTime = $this->request->getGet('out_time') ?? $this->request->getGet('outTime');

               $parseToMinutes = static function ($raw) {
                    if ($raw === null) return null;
                    $raw = trim((string) $raw);
                    if ($raw === '') return null;

                    $formats = [
                         'H:i:s',
                         'H:i',
                         'h:i:s A',
                         'h:i A',
                         'h:i:s a',
                         'h:i a',
                         'g:i:s A',
                         'g:i A',
                         'g:i:s a',
                         'g:i a',
                    ];

                    foreach ($formats as $fmt) {
                         $dt = \DateTime::createFromFormat($fmt, $raw);
                         if ($dt instanceof \DateTime) {
                              return ((int)$dt->format('H')) * 60 + (int)$dt->format('i');
                         }
                    }

                    if (preg_match('/\b(\d{1,2}):(\d{2})(?::\d{2})?\s*([AaPp][Mm])?\b/', $raw, $m)) {
                         $h = (int)$m[1];
                         $min = (int)$m[2];
                         $ampm = isset($m[3]) ? strtoupper($m[3]) : null;

                         if ($ampm === 'AM' || $ampm === 'PM') {
                              if ($h === 12) $h = 0;
                              if ($ampm === 'PM') $h += 12;
                         }

                         if ($h >= 0 && $h <= 23 && $min >= 0 && $min <= 59) {
                              return ($h * 60) + $min;
                         }
                    }

                    return null;
               };

               $minutesToHHMM = static function ($min) {
                    if ($min === null) return null;
                    $min = $min % 1440;
                    if ($min < 0) $min += 1440;
                    return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
               };

               $buildRanges = static function ($startMin, $endMin) {
                    if ($startMin === null || $endMin === null) return [];
                    if ($startMin <= $endMin) return [[$startMin, $endMin]];
                    return [[$startMin, 1439], [0, $endMin]];
               };

               $rangesOverlap = static function ($s1, $e1, $s2, $e2) use ($buildRanges) {
                    $r1 = $buildRanges($s1, $e1);
                    $r2 = $buildRanges($s2, $e2);

                    foreach ($r1 as [$a1, $a2]) {
                         foreach ($r2 as [$b1, $b2]) {
                              if ($a1 <= $b2 && $a2 >= $b1) return true;
                         }
                    }
                    return false;
               };

               $selectedInMin  = $parseToMinutes($requestedInTime);
               $selectedOutMin = $parseToMinutes($requestedOutTime);

               $selectedInTime  = $minutesToHHMM($selectedInMin);
               $selectedOutTime = $minutesToHHMM($selectedOutMin);

               $employeesQuery = $this->employeeModel
                    ->select('emp_id, employee_code, employee_name, emp_type, city_name, location_name, mobile')
                    ->where('status', 'A')
                    ->where('isDeleted', 'N')
                    ->where('emp_type', 'DOCTOR');

               if ($this->userRole === 'REPORTING_MANAGER' || $this->userRole === 'HOD_DOCTORS') {
                    $employeesQuery->groupStart()
                         ->where('reporting_manager_empcode', $this->userEmpCode)
                         ->orWhere('skip_level_manager_empcode', $this->userEmpCode)
                         ->groupEnd();
               }

               $employees = $employeesQuery->findAll();

               if (empty($employees)) {
                    return $this->respond([
                         'status' => true,
                         'data' => [
                              'selected_date' => $selectedDate,
                              'emp_type' => 'DOCTOR',
                              'filters' => [
                                   'in_time' => $selectedInTime,
                                   'out_time' => $selectedOutTime
                              ],
                              'summary' => [
                                   'total' => 0,
                                   'present' => 0,
                                   'absent' => 0,
                                   'on_leave' => 0,
                                   'duty_not_started' => 0
                              ],
                              'details' => []
                         ]
                    ], 200);
               }

               if (!$this->payrollDb) {
                    return $this->failServerError('Secondary database is not configured.');
               }

               $empCodes = array_column($employees, 'employee_code');
               $empIds   = array_column($employees, 'emp_id');

               $attendanceRows = $this->payrollDb->table('new_punch_list')
                    ->select('UserId, LogDate')
                    ->whereIn('UserId', $empCodes)
                    ->where('LogDate >=', $startDateTime)
                    ->where('LogDate <', $endDateTime)
                    ->orderBy('UserId ASC, LogDate ASC')
                    ->get()
                    ->getResultArray();

               $attendanceMap = [];
               foreach ($attendanceRows as $row) {
                    $attendanceMap[$row['UserId']][] = $row['LogDate'];
               }

               $dutyRows = $this->dutyRosterModel
                    ->select("
                    duty_roster.emp_id,
                    doctors_shift_master.shift_name as doc_shift_name,
                    doctors_shift_master.in_time as doc_in,
                    doctors_shift_master.out_time as doc_out
               ")
                    ->join('doctors_shift_master', 'duty_roster.shift_id = doctors_shift_master.id', 'left')
                    ->whereIn('duty_roster.emp_id', $empIds)
                    ->where('duty_roster.attendance_date', $selectedDate)
                    ->get()
                    ->getResultArray();

               $dutyMap = [];
               foreach ($dutyRows as $dr) {
                    $dutyMap[$dr['emp_id']] = $dr;
               }

               $presentCount = 0;
               $absentCount  = 0;
               $details = [];

               foreach ($employees as $emp) {
                    $shift = $dutyMap[$emp['emp_id']] ?? null;

                    $shiftName = $shift['doc_shift_name'] ?? null;
                    $shiftInMin  = $parseToMinutes($shift['doc_in'] ?? null);
                    $shiftOutMin = $parseToMinutes($shift['doc_out'] ?? null);

                    $shiftIn  = $minutesToHHMM($shiftInMin);
                    $shiftOut = $minutesToHHMM($shiftOutMin);

                    $hasTimeFilter = ($selectedInMin !== null || $selectedOutMin !== null);

                    if ($hasTimeFilter) {
                         if ($shiftInMin === null || $shiftOutMin === null) {
                              continue;
                         }

                         // Normalize overnight shift into comparable end minute
                         $normalizedShiftOut = $shiftOutMin;
                         if ($normalizedShiftOut < $shiftInMin) {
                              $normalizedShiftOut += 1440;
                         }

                         $normalizedSelectedIn = $selectedInMin;
                         $normalizedSelectedOut = $selectedOutMin;

                         if ($normalizedSelectedIn !== null && $normalizedSelectedOut !== null) {
                              if ($normalizedSelectedOut < $normalizedSelectedIn) {
                                   $normalizedSelectedOut += 1440;
                              }

                              // STRICT inside-range match (recommended for your screenshot expectation)
                              if (!($shiftInMin >= $normalizedSelectedIn && $normalizedShiftOut <= $normalizedSelectedOut)) {
                                   continue;
                              }

                              // If you want overlap instead, replace above condition with:
                              // if (!($shiftInMin < $normalizedSelectedOut && $normalizedShiftOut > $normalizedSelectedIn)) continue;
                         } elseif ($normalizedSelectedIn !== null) {
                              if (!($normalizedShiftOut >= $normalizedSelectedIn)) {
                                   continue;
                              }
                         } elseif ($normalizedSelectedOut !== null) {
                              if (!($shiftInMin <= $normalizedSelectedOut)) {
                                   continue;
                              }
                         }
                    }

                    $logs = $attendanceMap[$emp['employee_code']] ?? [];

                    $hasShiftIn = !empty($shiftIn);
                    $status = (!empty($logs) && $hasShiftIn) ? 'present' : 'absent';

                    $punchIn  = ($status === 'present') ? $logs[0] : null;
                    $punchOut = ($status === 'present') ? end($logs) : null;

                    if ($status === 'present') $presentCount++;
                    else $absentCount++;

                    $details[] = [
                         'emp_id' => $emp['emp_id'],
                         'employee_code' => $emp['employee_code'],
                         'employee_name' => $emp['employee_name'],
                         'city_name' => $emp['city_name'],
                         'location_name' => $emp['location_name'],
                         'mobile' => $emp['mobile'] ?? null,
                         'status' => $status,
                         'status_date' => $selectedDate,
                         'punch_in' => $punchIn,
                         'punch_out' => $punchOut,
                         'emp_type' => 'DOCTOR',
                         'shift_details' => [
                              'shift_name' => $shiftName,
                              'in_time' => $shiftIn,
                              'out_time' => $shiftOut
                         ]
                    ];
               }

               usort($details, function ($a, $b) {
                    return strcmp($b['punch_in'] ?? '', $a['punch_in'] ?? '');
               });

               return $this->respond([
                    'status' => true,
                    'data' => [
                         'selected_date' => $selectedDate,
                         'emp_type' => 'DOCTOR',
                         'filters' => [
                              'in_time' => $selectedInTime,
                              'out_time' => $selectedOutTime
                         ],
                         'summary' => [
                              'total' => count($details),
                              'present' => $presentCount,
                              'absent' => $absentCount,
                              'on_leave' => 0,
                              'duty_not_started' => 0
                         ],
                         'details' => $details
                    ]
               ], 200);
          } catch (\Throwable $e) {
               return $this->failServerError('Error computing custom summary: ' . $e->getMessage());
          }
     }

     // public function doctorSummary_last()
     // {

     //      try {
     //           // Get the selected date or default to today
     //           $today = date('Y-m-d');
     //           $selectedDate = $this->request->getGet('date') ?? $today;
     //           if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
     //                $selectedDate = $today;
     //           }

     //           // Get emp_type from query parameter or default to 'DOCTOR'
     //           $empType = strtoupper($this->request->getGet('emp_type') ?? '');

     //           // Validate emp_type against allowed values
     //           $allowedEmpTypes = ['DOCTOR', 'CONTRACTUAL_EMPLOYEE', 'POOJARI'];
     //           if (!empty($empType) && !in_array($empType, $allowedEmpTypes)) {
     //                $empType = ''; // Reset to empty if invalid
     //           }

     //           // Fetch all active employees based on emp_type
     //           $employeesQuery = $this->employeeModel
     //                ->select('emp_id, employee_code, employee_name, emp_type')
     //                ->where('status', 'A')
     //                ->where('isDeleted', 'N');

     //           // Add emp_type filter only if provided
     //           if (!empty($empType)) {
     //                $employeesQuery->where('emp_type', $empType);
     //           }

     //           // Add condition for REPORTING_MANAGER or HOD_DOCTORS role
     //           if ($this->userRole === 'REPORTING_MANAGER' || $this->userRole === 'HOD_DOCTORS') {
     //                log_message('error', "Entering doctorSummary -- method in HodController {$this->userEmpCode} , {$this->userRole}");
     //                $employeesQuery->groupStart()
     //                     ->where('reporting_manager_empcode', $this->userEmpCode)
     //                     ->orWhere('skip_level_manager_empcode', $this->userEmpCode)
     //                     ->groupEnd();
     //           }

     //           $employees = $employeesQuery->findAll();

     //           $totalEmployees = count($employees);
     //           $presentCount = 0;
     //           $absentCount = 0;
     //           $details = [];

     //           // Use the 'new_punch_list' table for attendance
     //           $db = \Config\Database::connect('secondary');
     //           $builder = $db->table('new_punch_list');

     //           // Iterate through employees and check attendance
     //           foreach ($employees as $employee) {
     //                $empId = $employee['employee_code']; // Use employee_code as the identifier
     //                $attendance = $builder
     //                     ->select('LogDate')
     //                     ->where('UserId', $empId) // Match attendance records by UserId
     //                     ->where('DATE(LogDate)', $selectedDate) // Match the selected date
     //                     ->orderBy('LogDate', 'ASC') // Order by time to get first and last punches
     //                     ->get()
     //                     ->getResultArray();

     //                $status = 'absent';
     //                $punchIn = null;
     //                $punchOut = null;

     //                if (!empty($attendance)) {
     //                     $status = 'present';
     //                     $punchIn = $attendance[0]['LogDate']; // First log as punch-in
     //                     $punchOut = $attendance[count($attendance) - 1]['LogDate']; // Last log as punch-out
     //                }

     //                // Increment counters
     //                if ($status === 'present') {
     //                     $presentCount++;
     //                } else {
     //                     $absentCount++;
     //                }

     //                // Get shift details based on emp_type
     //                $dutyRoster = null;
     //                if ($employee['emp_type'] === 'DOCTOR') {
     //                     // Use doctors_shift_master for DOCTOR
     //                     $dutyRoster = $this->dutyRosterModel
     //                          ->select('duty_roster.*, doctors_shift_master.shift_name, doctors_shift_master.in_time, doctors_shift_master.out_time')
     //                          ->join('doctors_shift_master', 'duty_roster.shift_id = doctors_shift_master.id', 'left')
     //                          ->where('duty_roster.emp_id', $employee['emp_id'])
     //                          ->where('duty_roster.attendance_date', $selectedDate)
     //                          ->first();
     //                } else {
     //                     // Use shiftslist for other employee types (CONTRACTUAL_EMPLOYEE, POOJARI)
     //                     $dutyRoster = $this->dutyRosterModel
     //                          ->select('duty_roster.*, shiftslist.ShiftName as shift_name, shiftslist.ShiftStart as in_time, shiftslist.ShiftEnd as out_time')
     //                          ->join('shiftslist', 'duty_roster.shift_id = shiftslist.id', 'left')
     //                          ->where('duty_roster.emp_id', $employee['emp_id'])
     //                          ->where('duty_roster.attendance_date', $selectedDate)
     //                          ->first();
     //                }

     //                // Add to details
     //                $details[] = [
     //                     'emp_id' => $employee['emp_id'],
     //                     'employee_code' => $employee['employee_code'],
     //                     'employee_name' => $employee['employee_name'],
     //                     'status' => $status,
     //                     'status_date' => $selectedDate,
     //                     'punch_in' => $punchIn,
     //                     'punch_out' => $punchOut,
     //                     'emp_type' => $employee['emp_type'],
     //                     'shift_details' => $dutyRoster ? $dutyRoster : null,
     //                ];
     //           }

     //           // Sort details by punch_in in descending order (latest first)
     //           usort($details, function ($a, $b) {
     //                // Handle null punch_in values (absent employees)
     //                if ($a['punch_in'] === null && $b['punch_in'] === null) return 0;
     //                if ($a['punch_in'] === null) return 1;  // null goes to bottom
     //                if ($b['punch_in'] === null) return -1; // null goes to bottom

     //                // Compare punch_in times (descending order)
     //                return strcmp($b['punch_in'], $a['punch_in']);
     //           });

     //           // Build the response
     //           $response = [
     //                'status' => true,
     //                'data' => [
     //                     'selected_date' => $selectedDate,
     //                     'emp_type' => $empType,
     //                     'summary' => [
     //                          'total' => $totalEmployees,
     //                          'present' => $presentCount,
     //                          'on_leave' => 0, // Placeholder for now
     //                          'duty_not_started' => 0, // Placeholder for now
     //                          'absent' => $absentCount,
     //                     ],
     //                     'details' => $details
     //                ]
     //           ];

     //           return $this->respond($response, 200);
     //      } catch (\Throwable $e) {
     //           return $this->failServerError('Error computing employee summary: ' . $e->getMessage());
     //      }
     // }


     // public function doctorSummary()
     // {
     //      log_message('error', "Entering doctorSummary method in HodController {$this->userEmpCode} , {$this->userRole}");
     //      try {
     //           // Get the selected date or default to today
     //           $today = date('Y-m-d');
     //           $selectedDate = $this->request->getGet('date') ?? $today;
     //           if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
     //                $selectedDate = $today;
     //           }

     //           // Fetch all active doctors
     //           $doctorsQuery = $this->employeeModel
     //                ->select('emp_id, employee_code, employee_name, emp_type')
     //                ->where('emp_type', 'DOCTOR')
     //                ->where('status', 'A')
     //                ->where('isDeleted', 'N');

     //           // Add condition for REPORTING_MANAGER role
     //           if ($this->userRole === 'REPORTING_MANAGER' || $this->userRole === 'HOD_DOCTORS') {
     //                $doctorsQuery->groupStart()
     //                     ->where('reporting_manager_empcode', $this->userEmpCode)
     //                     ->orWhere('skip_level_manager_empcode', $this->userEmpCode)
     //                     ->groupEnd();
     //           }

     //           $doctors = $doctorsQuery->findAll();

     //           $totalDoctors = count($doctors);
     //           $presentCount = 0;
     //           $absentCount = 0;
     //           $details = [];

     //           // Use the 'new_punch_list' table for attendance
     //           $db = \Config\Database::connect('secondary');
     //           $builder = $db->table('new_punch_list');

     //           // Iterate through doctors and check attendance
     //           foreach ($doctors as $doctor) {
     //                $empId = $doctor['employee_code']; // Use employee_code as the identifier
     //                $attendance = $builder
     //                     ->select('LogDate')
     //                     ->where('UserId', $empId) // Match attendance records by UserId
     //                     ->where('DATE(LogDate)', $selectedDate) // Match the selected date
     //                     ->orderBy('LogDate', 'ASC') // Order by time to get first and last punches
     //                     ->get()
     //                     ->getResultArray();

     //                $status = 'absent';
     //                $punchIn = null;
     //                $punchOut = null;

     //                if (!empty($attendance)) {
     //                     $status = 'present';
     //                     $punchIn = $attendance[0]['LogDate']; // First log as punch-in
     //                     $punchOut = $attendance[count($attendance) - 1]['LogDate']; // Last log as punch-out
     //                }

     //                // Increment counters
     //                if ($status === 'present') {
     //                     $presentCount++;
     //                } else {
     //                     $absentCount++;
     //                }

     //                // we need to get shift details from duty_roster if assigned by joining doctors_shift_master
     //                $dutyRoster = $this->dutyRosterModel
     //                     ->select('duty_roster.*, doctors_shift_master.shift_name, doctors_shift_master.in_time, doctors_shift_master.out_time')
     //                     ->join('doctors_shift_master', 'duty_roster.shift_id = doctors_shift_master.id', 'left')
     //                     ->where('duty_roster.emp_id', $doctor['emp_id'])
     //                     ->where('duty_roster.attendance_date', $selectedDate) // <-- fixed column name
     //                     ->first();

     //                // Add to details
     //                $details[] = [
     //                     'emp_id' => $doctor['emp_id'],
     //                     'employee_code' => $doctor['employee_code'],
     //                     'employee_name' => $doctor['employee_name'],
     //                     'status' => $status,
     //                     'status_date' => $selectedDate,
     //                     'punch_in' => $punchIn,
     //                     'punch_out' => $punchOut,
     //                     'emp_type' => $doctor['emp_type'],
     //                     'shift_details' => $dutyRoster ? $dutyRoster : null,
     //                ];
     //           }

     //           // Build the response
     //           $response = [
     //                'status' => true,
     //                'data' => [
     //                     'selected_date' => $selectedDate,
     //                     'summary' => [
     //                          'total' => $totalDoctors,
     //                          'present' => $presentCount,
     //                          'on_leave' => 0, // Placeholder for now
     //                          'duty_not_started' => 0, // Placeholder for now
     //                          'absent' => $absentCount,
     //                     ],
     //                     'details' => $details
     //                ]
     //           ];

     //           return $this->respond($response, 200);
     //      } catch (\Throwable $e) {
     //           return $this->failServerError('Error computing doctor summary: ' . $e->getMessage());
     //      }
     // }
}
