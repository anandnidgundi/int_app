<?php

namespace App\Models;

use CodeIgniter\Model;

class DutyRosterModel extends Model
{
     protected $table = 'duty_roster';

     protected $primaryKey = 'id';

     public $timestamps = false;

     protected $allowedFields = [
          'emp_id',
          'shift_id',
          'attendance_date',
          'custom_weekoff_date',
          'createdBy',
          'createdDTM',
          'updatedBy',
          'updatedDTM',
          'split_shift_type',
     ];

     public function getEmpByIdNew($id)
     {
          $db = \Config\Database::connect();
          $builder = $db->table('employees');
          $builder->select('
        employees.emp_id, 
        employees.employee_code, 
        employees.emp_type,
        employees.employee_name as employee_name,
        employees.designation, 
        employees.department, 
        employees.joining_date, 
        employees.employment_type, 
        employees.mobile, 
        employees.email, 
        employees.dob, 
        employees.gender, 
        employees.father_husband_name, 
        employees.marital_status, 
        employees.blood_group, 
        employees.religion, 
        employees.caste, 
        employees.department_category, 
        employees.main_department, 
        employees.sub_department, 
        employees.designation_name, 
        employees.grade_name, 
        employees.position, 
        employees.reporting_manager_name, 
        employees.reporting_manager_empcode, 
        employees.functional_manager_name, 
        employees.skip_level_manager_empcode, 
        employees.shift_description, 
        employees.total_experience, 
        employees.bank_account_name, 
        employees.bank_account_number, 
        employees.ifsc_code, 
        employees.ctc, 
        employees.latest_agreement_valid_date, 
        employees.latest_agreement_end_date, 
        employees.latest_contract_fee_revision_amount, 
        employees.resignation, 
        employees.resignation_date, 
        employees.relieving_date, 
        employees.last_working_date, 
        employees.last_pay_date, 
        employees.separation_status, 
        employees.notice_period, 
        employees.status, 
        employees.week_off,
        employees.created_at, 
        employees.updated_at,
        employees.split_shift,
        employee_documents.document_name, 
        employee_documents.document_path
    ');

          $builder->join('employee_documents', 'employee_documents.emp_id = employees.emp_id', 'left');
          $builder->where('employees.status', 'A');
          $builder->where('employees.emp_id', $id);
          $builder->orderBy('employees.emp_id', 'DESC');

          $result = $builder->get()->getResultArray();

          $users = [];
          foreach ($result as $row) {
               $emp_id = $row['emp_id'];

               if (!isset($users[$emp_id])) {
                    $users[$emp_id] = [
                         'emp_id' => $row['emp_id'],
                         'employee_code' => $row['employee_code'],
                         'emp_type' => $row['emp_type'],
                         'employee_name' => $row['employee_name'],
                         'designation' => $row['designation'],
                         'department' => $row['department'],
                         'joining_date' => $row['joining_date'],
                         'employment_type' => $row['employment_type'],
                         'mobile' => $row['mobile'],
                         'email' => $row['email'],
                         'dob' => $row['dob'],
                         'gender' => $row['gender'],
                         'father_husband_name' => $row['father_husband_name'],
                         'marital_status' => $row['marital_status'],
                         'blood_group' => $row['blood_group'],
                         'religion' => $row['religion'],
                         'caste' => $row['caste'],
                         'department_category' => $row['department_category'],
                         'main_department' => $row['main_department'],
                         'sub_department' => $row['sub_department'],
                         'designation_name' => $row['designation_name'],
                         'grade_name' => $row['grade_name'],
                         'position' => $row['position'],
                         'reporting_manager_name' => $row['reporting_manager_name'],
                         'reporting_manager_empcode' => $row['reporting_manager_empcode'],
                         'functional_manager_name' => $row['functional_manager_name'],
                         'skip_level_manager_empcode' => $row['skip_level_manager_empcode'],
                         'shift_description' => $row['shift_description'],
                         'total_experience' => $row['total_experience'],
                         'bank_account_name' => $row['bank_account_name'],
                         'bank_account_number' => $row['bank_account_number'],
                         'ifsc_code' => $row['ifsc_code'],
                         'ctc' => $row['ctc'],
                         'latest_agreement_valid_date' => $row['latest_agreement_valid_date'],
                         'latest_agreement_end_date' => $row['latest_agreement_end_date'],
                         'latest_contract_fee_revision_amount' => $row['latest_contract_fee_revision_amount'],
                         'resignation' => $row['resignation'],
                         'resignation_date' => $row['resignation_date'],
                         'relieving_date' => $row['relieving_date'],
                         'last_working_date' => $row['last_working_date'],
                         'last_pay_date' => $row['last_pay_date'],
                         'separation_status' => $row['separation_status'],
                         'notice_period' => $row['notice_period'],
                         'status' => $row['status'],
                         'week_off' => $row['week_off'],
                         'created_at' => $row['created_at'],
                         'updated_at' => $row['updated_at'],
                         'split_shift' => $row['split_shift'],
                         'documents' => []
                    ];
               }

               if ($row['document_name']) {
                    $users[$emp_id]['documents'][] = [
                         'document_name' => $row['document_name'],
                         'document_path' => $row['document_path']
                    ];
               }
          }

          // Return only the first employee (since you query by emp_id)
          return !empty($users) ? reset($users) : [];
     }

     public function getEmpByEmpCode($emp_code)
     {
          $db = \Config\Database::connect();
          $builder = $db->table('employees');
          $builder->select('
        employees.emp_id, 
        employees.employee_code, 
        employees.emp_type,
        employees.employee_name as employee_name,
        employees.designation, 
        employees.department, 
        employees.joining_date, 
        employees.employment_type, 
        employees.mobile, 
        employees.email, 
        employees.dob, 
        employees.gender, 
        employees.father_husband_name, 
        employees.marital_status, 
        employees.blood_group, 
        employees.religion, 
        employees.caste, 
        employees.department_category, 
        employees.main_department, 
        employees.sub_department, 
        employees.designation_name, 
        employees.grade_name, 
        employees.position, 
        employees.reporting_manager_name, 
        employees.reporting_manager_empcode, 
        employees.functional_manager_name, 
        employees.skip_level_manager_empcode, 
        employees.shift_description, 
        employees.total_experience, 
        employees.bank_account_name, 
        employees.bank_account_number, 
        employees.ifsc_code, 
        employees.ctc, 
        employees.latest_agreement_valid_date, 
        employees.latest_agreement_end_date, 
        employees.latest_contract_fee_revision_amount, 
        employees.resignation, 
        employees.resignation_date, 
        employees.relieving_date, 
        employees.last_working_date, 
        employees.last_pay_date, 
        employees.separation_status, 
        employees.notice_period, 
        employees.status, 
        employees.week_off,
        employees.created_at, 
        employees.updated_at,
        employees.split_shift,
        employee_documents.document_name, 
        employee_documents.document_path
    ');

          $builder->join('employee_documents', 'employee_documents.emp_id = employees.emp_id', 'left');
          $builder->where('employees.status', 'A');
          $builder->where('employees.employee_code', $emp_code);
          $builder->orderBy('employees.emp_id', 'DESC');

          $result = $builder->get()->getResultArray();

          $users = [];
          foreach ($result as $row) {
               $emp_id = $row['emp_id'];

               if (!isset($users[$emp_id])) {
                    $users[$emp_id] = [
                         'emp_id' => $row['emp_id'],
                         'employee_code' => $row['employee_code'],
                         'emp_type' => $row['emp_type'],
                         'employee_name' => $row['employee_name'],
                         'designation' => $row['designation'],
                         'department' => $row['department'],
                         'joining_date' => $row['joining_date'],
                         'employment_type' => $row['employment_type'],
                         'mobile' => $row['mobile'],
                         'email' => $row['email'],
                         'dob' => $row['dob'],
                         'gender' => $row['gender'],
                         'father_husband_name' => $row['father_husband_name'],
                         'marital_status' => $row['marital_status'],
                         'blood_group' => $row['blood_group'],
                         'religion' => $row['religion'],
                         'caste' => $row['caste'],
                         'department_category' => $row['department_category'],
                         'main_department' => $row['main_department'],
                         'sub_department' => $row['sub_department'],
                         'designation_name' => $row['designation_name'],
                         'grade_name' => $row['grade_name'],
                         'position' => $row['position'],
                         'reporting_manager_name' => $row['reporting_manager_name'],
                         'reporting_manager_empcode' => $row['reporting_manager_empcode'],
                         'functional_manager_name' => $row['functional_manager_name'],
                         'skip_level_manager_empcode' => $row['skip_level_manager_empcode'],
                         'shift_description' => $row['shift_description'],
                         'total_experience' => $row['total_experience'],
                         'bank_account_name' => $row['bank_account_name'],
                         'bank_account_number' => $row['bank_account_number'],
                         'ifsc_code' => $row['ifsc_code'],
                         'ctc' => $row['ctc'],
                         'latest_agreement_valid_date' => $row['latest_agreement_valid_date'],
                         'latest_agreement_end_date' => $row['latest_agreement_end_date'],
                         'latest_contract_fee_revision_amount' => $row['latest_contract_fee_revision_amount'],
                         'resignation' => $row['resignation'],
                         'resignation_date' => $row['resignation_date'],
                         'relieving_date' => $row['relieving_date'],
                         'last_working_date' => $row['last_working_date'],
                         'last_pay_date' => $row['last_pay_date'],
                         'separation_status' => $row['separation_status'],
                         'notice_period' => $row['notice_period'],
                         'status' => $row['status'],
                         'week_off' => $row['week_off'],
                         'created_at' => $row['created_at'],
                         'updated_at' => $row['updated_at'],
                         'split_shift' => $row['split_shift'],
                         'documents' => []
                    ];
               }

               if ($row['document_name']) {
                    $users[$emp_id]['documents'][] = [
                         'document_name' => $row['document_name'],
                         'document_path' => $row['document_path']
                    ];
               }
          }

          // Return only the first employee (since you query by emp_id)
          return !empty($users) ? reset($users) : [];
     }

     public function getEmployeeAttendance($employee_code, $selectedMonth, $selectedToMonth = null)
     {
          $db = \Config\Database::connect('secondary');
          $defaultDB = \Config\Database::connect('default');
          $builder = $db->table('new_punch_list');

          // Parse month range
          if (empty($selectedMonth)) {
               $year = date('Y');
               $month = date('n');
               $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
          } else {
               $dateParts = explode('-', $selectedMonth);
               if (count($dateParts) == 2) {
                    $year = (int)$dateParts[0];
                    $month = (int)$dateParts[1];
                    $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
               } else {
                    $year = date('Y');
                    $month = date('n');
                    $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
               }
          }

          // If $selectedToMonth is provided, use it as end month, else use $selectedMonth
          if (!empty($selectedToMonth)) {
               $toDateParts = explode('-', $selectedToMonth);
               if (count($toDateParts) == 2) {
                    $toYear = (int)$toDateParts[0];
                    $toMonth = (int)$toDateParts[1];
                    $endOfMonth = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $toYear, $toMonth)));
               } else {
                    $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
               }
          } else {
               $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
          }

          // Calculate total days in range
          $period = new \DatePeriod(
               new \DateTime($startOfMonth),
               new \DateInterval('P1D'),
               (new \DateTime($endOfMonth))->modify('+1 day')
          );
          $allDates = [];
          foreach ($period as $dt) {
               $allDates[] = $dt->format('Y-m-d');
          }
          $totalDays = count($allDates);

          // Attendance data with all punches
          $query = $builder->select('DATE(LogDate) as date, MIN(LogDate) as punch_in, MAX(LogDate) as punch_out, GROUP_CONCAT(LogDate ORDER BY LogDate ASC) as all_punches')
               ->where('UserId', $employee_code)
               ->where('DATE(LogDate) >=', $startOfMonth)
               ->where('DATE(LogDate) <=', $endOfMonth)
               ->where('status', '1')
               ->groupBy('DATE(LogDate)')
               ->orderBy('DATE(LogDate)', 'ASC')
               ->get();

          $attendanceDaysRaw = $query->getResultArray();

          // Map attendance data by date for quick lookup
          $attendanceDaysMap = [];
          foreach ($attendanceDaysRaw as $row) {
               $attendanceDaysMap[$row['date']] = [
                    'date' => $row['date'],
                    'punch_in' => $row['punch_in'],
                    'punch_out' => $row['punch_out'],
                    'all_punches' => $row['all_punches'] ? explode(',', $row['all_punches']) : []
               ];
          }

          // Employee data
          $employeeData = $defaultDB->table('employees')
               ->select('emp_id, week_off, emp_type, split_shift')
               ->where('employee_code', $employee_code)
               ->get()
               ->getRowArray();

          $weekoff = (!empty($employeeData) && !empty($employeeData['week_off']))
               ? $employeeData['week_off']
               : 'Sunday';
          $emp_type = $employeeData['emp_type'] ?? 'CONTRACTUAL_EMPLOYEE';

          // Holidays
          $holidays = $defaultDB->table('holiday')
               ->select('date as holiday_date, holiday as holiday_name')
               ->where('date >=', $startOfMonth)
               ->where('date <=', $endOfMonth)
               ->where('status', 'A')
               ->get()
               ->getResultArray();

          // Duty roster data
          $dutyRosterBuilder = $defaultDB->table('duty_roster');
          if ($emp_type === 'DOCTOR') {
               $dutyRosterBuilder->select('
            duty_roster.attendance_date,
            duty_roster.custom_weekoff_date,
            doctors_shift_master.id as shift_id,
            doctors_shift_master.shift_name,
            doctors_shift_master.in_time,
            doctors_shift_master.out_time,
            doctors_shift_master.total_hours,
            doctors_shift_master.total_minutes,
            doctors_shift_master.grace_in,
            doctors_shift_master.grace_out,
            doctors_shift_master.exemption_limit,
            doctors_shift_master.status as shift_status,
            duty_roster.createdBy,
            duty_roster.createdDTM,
            duty_roster.updatedBy,
            duty_roster.updatedDTM
        ');
               $dutyRosterBuilder->join('doctors_shift_master', 'doctors_shift_master.id = duty_roster.shift_id', 'left');
          } elseif ($emp_type === 'CONTRACTUAL_EMPLOYEE' || $emp_type === 'POOJARI') {
               $dutyRosterBuilder->select('
            duty_roster.attendance_date,
            duty_roster.custom_weekoff_date,
            shiftslist.id as shift_id,
            shiftslist.ShiftName as shift_name,
            shiftslist.ShiftStart as in_time,
            shiftslist.ShiftEnd as out_time,
            shiftslist.WorkingsHoursToBeConsiderdFullDay as total_hours,
            shiftslist.WorkingsHoursToBeConsiderdHalfDay,
            shiftslist.late_login_applicable,
            shiftslist.emp_type,
            shiftslist.status as shift_status,
            duty_roster.createdBy,
            duty_roster.createdDTM,
            duty_roster.updatedBy,
            duty_roster.updatedDTM
        ');
               $dutyRosterBuilder->join('shiftslist', 'shiftslist.id = duty_roster.shift_id', 'left');
          }
          $dutyRosterBuilder->where('duty_roster.emp_id', $employeeData['emp_id']);
          $dutyRosterBuilder->where('duty_roster.attendance_date >=', $startOfMonth);
          $dutyRosterBuilder->where('duty_roster.attendance_date <=', $endOfMonth);
          $dutyRosterData = $dutyRosterBuilder->get()->getResultArray();

          // Create roster map for quick lookup
          $rosterMap = [];
          foreach ($dutyRosterData as $roster) {
               $rosterMap[$roster['attendance_date']] = $roster;
          }

          // Generate all dates for the range with shortfall calculation
          $days = [];
          foreach ($allDates as $dateStr) {
               $dayData = [
                    'date' => $dateStr,
                    'punch_in' => null,
                    'punch_out' => null,
                    'all_punches' => [],
                    'shift_total_minutes' => 0,
                    'worked_seconds' => 0,
                    'shortfall_seconds' => 0,
                    'shortfall_hours' => '00:00:00',
                    'has_shortfall' => false
               ];

               // Add attendance data if exists
               if (isset($attendanceDaysMap[$dateStr])) {
                    $dayData['punch_in'] = $attendanceDaysMap[$dateStr]['punch_in'];
                    $dayData['punch_out'] = $attendanceDaysMap[$dateStr]['punch_out'];
                    $dayData['all_punches'] = $attendanceDaysMap[$dateStr]['all_punches'];
               }

               // Calculate shortfall if punch data and roster exist
               if ($dayData['punch_in'] && $dayData['punch_out'] && isset($rosterMap[$dateStr])) {
                    $roster = $rosterMap[$dateStr];

                    // Calculate worked seconds (not minutes)
                    $punchIn = new \DateTime($dayData['punch_in']);
                    $punchOut = new \DateTime($dayData['punch_out']);
                    $workedSeconds = $punchOut->getTimestamp() - $punchIn->getTimestamp();

                    // Get shift total seconds
                    $shiftTotalSeconds = 0;
                    if ($emp_type === 'DOCTOR') {
                         $shiftTotalMinutes = (int)($roster['total_minutes'] ?? 0);
                         $shiftTotalSeconds = $shiftTotalMinutes * 60;
                    } else {
                         // For contractual employees, convert hours to seconds
                         $totalHours = (float)($roster['total_hours'] ?? 0);
                         $shiftTotalSeconds = $totalHours * 3600;
                    }

                    // Calculate shortfall in seconds
                    $shortfallSeconds = max(0, $shiftTotalSeconds - $workedSeconds);

                    // Add shortfall data
                    $dayData['shift_total_minutes'] = $shiftTotalMinutes ?? 0;
                    $dayData['worked_seconds'] = (int)$workedSeconds;
                    $dayData['shortfall_seconds'] = (int)$shortfallSeconds;
                    $dayData['shortfall_hours'] = $this->minutesToHMS($shortfallSeconds);
                    $dayData['has_shortfall'] = $shortfallSeconds > 0;
               }

               $days[] = $dayData;
          }

          $monthlyPresent = count($attendanceDaysRaw);
          $monthlyAbsent = $totalDays - $monthlyPresent;

          return [
               'month_range' => [
                    'start' => $startOfMonth,
                    'end'   => $endOfMonth,
                    'start_year'  => (int)date('Y', strtotime($startOfMonth)),
                    'start_month' => (int)date('n', strtotime($startOfMonth)),
                    'end_year'    => (int)date('Y', strtotime($endOfMonth)),
                    'end_month'   => (int)date('n', strtotime($endOfMonth)),
                    'start_month_name' => date('F', strtotime($startOfMonth)),
                    'end_month_name'   => date('F', strtotime($endOfMonth)),
               ],
               'attendance' => [
                    'present_days' => $monthlyPresent,
                    'absent_days'  => $monthlyAbsent,
                    'total_days'   => $totalDays,
               ],
               'employee_info' => [
                    'week_off' => $weekoff,
                    'emp_type' => $emp_type
               ],
               'holidays' => $holidays,
               'days' => $days, // Now includes all_punches array
               'duty_roster' => $dutyRosterData
          ];
     }



     public function getEmployeeAttendanceWithSplitShift($employee_code, $selectedMonth, $selectedToMonth = null)
     {
          $db = \Config\Database::connect('secondary');
          $defaultDB = \Config\Database::connect('default');
          $builder = $db->table('new_punch_list');

          // Parse month range
          if (empty($selectedMonth)) {
               $year = date('Y');
               $month = date('n');
               $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
          } else {
               $dateParts = explode('-', $selectedMonth);
               if (count($dateParts) == 2) {
                    $year = (int)$dateParts[0];
                    $month = (int)$dateParts[1];
                    $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
               } else {
                    $year = date('Y');
                    $month = date('n');
                    $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
               }
          }

          if (!empty($selectedToMonth)) {
               $toDateParts = explode('-', $selectedToMonth);
               if (count($toDateParts) == 2) {
                    $toYear = (int)$toDateParts[0];
                    $toMonth = (int)$toDateParts[1];
                    $endOfMonth = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $toYear, $toMonth)));
               } else {
                    $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
               }
          } else {
               $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
          }

          // Calculate total days in range
          $period = new \DatePeriod(
               new \DateTime($startOfMonth),
               new \DateInterval('P1D'),
               (new \DateTime($endOfMonth))->modify('+1 day')
          );
          $allDates = [];
          foreach ($period as $dt) {
               $allDates[] = $dt->format('Y-m-d');
          }
          $totalDays = count($allDates);

          // Attendance data with all punches (for the whole day)
          $query = $builder->select('DATE(LogDate) as date, LogDate')
               ->where('UserId', $employee_code)
               ->where('DATE(LogDate) >=', $startOfMonth)
               ->where('DATE(LogDate) <=', $endOfMonth)
               ->where('status', '1')
               ->orderBy('LogDate', 'ASC')
               ->get();

          $attendanceRaw = $query->getResultArray();

          // Map punches by date for quick lookup
          $attendanceDaysMap = [];
          foreach ($attendanceRaw as $row) {
               $attendanceDaysMap[$row['date']][] = $row['LogDate'];
          }

          // Employee data
          $employeeData = $defaultDB->table('employees')
               ->select('emp_id, week_off, emp_type, split_shift')
               ->where('employee_code', $employee_code)
               ->get()
               ->getRowArray();

          $weekoff = (!empty($employeeData) && !empty($employeeData['week_off']))
               ? $employeeData['week_off']
               : 'Sunday';
          $emp_type = $employeeData['emp_type'] ?? 'CONTRACTUAL_EMPLOYEE';

          // Holidays
          $holidays = $defaultDB->table('holiday')
               ->select('date as holiday_date, holiday as holiday_name')
               ->where('date >=', $startOfMonth)
               ->where('date <=', $endOfMonth)
               ->where('status', 'A')
               ->get()
               ->getResultArray();

          // Duty roster data (fetch all split shifts for the month)
          $dutyRosterData = $defaultDB->table('duty_roster')
               ->select('
            duty_roster.attendance_date,
            duty_roster.custom_weekoff_date,
            duty_roster.shift_id,
            duty_roster.split_shift_type,
            doctors_shift_master.shift_name,
            doctors_shift_master.in_time,
            doctors_shift_master.out_time,
            doctors_shift_master.total_hours,
            doctors_shift_master.total_minutes,
            doctors_shift_master.grace_in,
            doctors_shift_master.grace_out,
            doctors_shift_master.exemption_limit,
            doctors_shift_master.status as shift_status,
            duty_roster.createdBy,
            duty_roster.createdDTM,
            duty_roster.updatedBy,
            duty_roster.updatedDTM
        ')
               ->join('doctors_shift_master', 'doctors_shift_master.id = duty_roster.shift_id', 'left')
               ->where('duty_roster.emp_id', $employeeData['emp_id'])
               ->where('duty_roster.attendance_date >=', $startOfMonth)
               ->where('duty_roster.attendance_date <=', $endOfMonth)
               ->orderBy('duty_roster.attendance_date', 'ASC')
               ->orderBy('duty_roster.split_shift_type', 'ASC')
               ->get()
               ->getResultArray();

          // Group roster by date, then by split_shift_type
          $rosterMap = [];
          foreach ($dutyRosterData as $roster) {
               $date = $roster['attendance_date'];
               $type = $roster['split_shift_type'] ?? 'main';
               if (!isset($rosterMap[$date])) $rosterMap[$date] = [];
               $rosterMap[$date][$type] = $roster;
          }

          // Generate all dates for the range with split shift calculation
          $days = [];
          foreach ($allDates as $dateStr) {
               $dayShifts = [];
               $punches = $attendanceDaysMap[$dateStr] ?? [];

               // For each split shift type on this date
               if (isset($rosterMap[$dateStr]) && is_array($rosterMap[$dateStr])) {
                    foreach ($rosterMap[$dateStr] as $splitType => $roster) {
                         $shiftIn = $roster['in_time'] ? $dateStr . ' ' . $roster['in_time'] : null;
                         $shiftOut = $roster['out_time'] ? $dateStr . ' ' . $roster['out_time'] : null;

                         // apply grace minutes if available to expand the window
                         $graceIn = isset($roster['grace_in']) ? (int)$roster['grace_in'] : 0;
                         $graceOut = isset($roster['grace_out']) ? (int)$roster['grace_out'] : 0;

                         $shiftStartTs = $shiftIn ? strtotime($shiftIn) - ($graceIn * 60) : null;
                         $shiftEndTs   = $shiftOut ? strtotime($shiftOut) + ($graceOut * 60) : null;

                         // Use processShiftPunches to get punches and worked seconds (pass timestamps)
                         $shiftPunchResult = $this->processShiftPunches($punches, $shiftStartTs, $shiftEndTs);

                         // Assign up to 4 punches for this shift
                         $allPunches = $shiftPunchResult['all_punches'];
                         $punch_in  = $allPunches[0] ?? null;
                         $punch_out = $allPunches[1] ?? null;
                         $punch_in2 = $allPunches[2] ?? null;
                         $punch_out2 = $allPunches[3] ?? null;
                         $workedSeconds = $shiftPunchResult['worked_seconds'];
                         // Get shift total seconds
                         $shiftTotalMinutes = (int)($roster['total_minutes'] ?? 0);
                         $shiftTotalSeconds = $shiftTotalMinutes * 60;

                         // Calculate shortfall in seconds
                         $shortfallSeconds = max(0, $shiftTotalSeconds - $workedSeconds);

                         $dayShifts[] = [
                              'split_shift_type' => $splitType,
                              'shift_id' => $roster['shift_id'],
                              'shift_name' => $roster['shift_name'],
                              'in_time' => $roster['in_time'],
                              'out_time' => $roster['out_time'],
                              'shift_total_minutes' => $shiftTotalMinutes,
                              'punch_in' => $punch_in,
                              'punch_out' => $punch_out,
                              'punch_in2' => $punch_in2,
                              'punch_out2' => $punch_out2,
                              'all_punches' => $allPunches,
                              'paired_punches' => $shiftPunchResult['paired_punches'],
                              'missed_punch' => $shiftPunchResult['missed_punch'],
                              'worked_seconds' => (int)$workedSeconds,
                              'shortfall_seconds' => (int)$shortfallSeconds,
                              'shortfall_hours' => $this->minutesToHMS($shortfallSeconds),
                              'has_shortfall' => $shortfallSeconds > 0,
                              'custom_weekoff_date' => $roster['custom_weekoff_date'],
                         ];
                    }
               }

               // If no shifts, still return the date with empty shifts array
               $days[] = [
                    'date' => $dateStr,
                    'shifts' => $dayShifts
               ];
          }

          // Count present days (at least one shift with punch_in)
          $monthlyPresent = 0;
          foreach ($days as $d) {
               $present = false;
               foreach ($d['shifts'] as $shift) {
                    if ($shift['punch_in']) {
                         $present = true;
                         break;
                    }
               }
               if ($present) $monthlyPresent++;
          }
          $monthlyAbsent = $totalDays - $monthlyPresent;

          return [
               'month_range' => [
                    'start' => $startOfMonth,
                    'end'   => $endOfMonth,
                    'start_year'  => (int)date('Y', strtotime($startOfMonth)),
                    'start_month' => (int)date('n', strtotime($startOfMonth)),
                    'end_year'    => (int)date('Y', strtotime($endOfMonth)),
                    'end_month'   => (int)date('n', strtotime($endOfMonth)),
                    'start_month_name' => date('F', strtotime($startOfMonth)),
                    'end_month_name'   => date('F', strtotime($endOfMonth)),
               ],
               'attendance' => [
                    'present_days' => $monthlyPresent,
                    'absent_days'  => $monthlyAbsent,
                    'total_days'   => $totalDays,
               ],
               'employee_info' => [
                    'week_off' => $weekoff,
                    'emp_type' => $emp_type,
                    'split_shift' => $employeeData['split_shift'] ?? 'N'
               ],
               'holidays' => $holidays,
               'days' => $days, // Each day contains an array of shifts
               'duty_roster' => $dutyRosterData
          ];
     }

     // Helper for formatting seconds to H:M:S
     private function minutesToHMS($seconds)
     {
          $hours = floor($seconds / 3600);
          $minutes = floor(($seconds % 3600) / 60);
          $secs = $seconds % 60;
          return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
     }


     public function getEmployeeAttendanceOnDate($employee_code, $date)
     {
          $db = \Config\Database::connect('secondary');
          $builder = $db->table('new_punch_list');

          // Attendance data for the specific date
          $query = $builder->select('LogDate')
               ->where('UserId', $employee_code)
               ->where('DATE(LogDate)', $date)
               ->where('status', '1')
               ->orderBy('LogDate', 'ASC')
               ->get();

          $attendanceRaw = $query->getResultArray();

          // Extract punch times
          $punches = [];
          foreach ($attendanceRaw as $row) {
               $punches[] = $row['LogDate'];
          }

          return [
               'date' => $date,
               'all_punches' => $punches
          ];
     }


     //      public function getEmployeeAttendance($employee_code, $selectedMonth)
     //      {
     //           $db = \Config\Database::connect('secondary');
     //           $defaultDB = \Config\Database::connect('default');
     //           $builder = $db->table('new_punch_list');

     //           if (empty($selectedMonth)) {
     //                $year = date('Y');
     //                $month = date('n');
     //           } else {
     //                $dateParts = explode('-', $selectedMonth);
     //                if (count($dateParts) == 2) {
     //                     $year = (int)$dateParts[0];
     //                     $month = (int)$dateParts[1];
     //                } else {
     //                     $year = date('Y');
     //                     $month = date('n');
     //                }
     //           }

     //           $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
     //           $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
     //           $monthlyTotalDays = date('t', strtotime($startOfMonth));

     //           // Attendance data
     //           $query = $builder->select('DATE(LogDate) as date, MIN(LogDate) as punch_in, MAX(LogDate) as punch_out')
     //                ->where('UserId', $employee_code)
     //                ->where('DATE(LogDate) >=', $startOfMonth)
     //                ->where('DATE(LogDate) <=', $endOfMonth)
     //                ->where('status', '1')
     //                ->groupBy('DATE(LogDate)')
     //                ->orderBy('DATE(LogDate)', 'ASC')
     //                ->get();

     //           $attendanceDaysRaw = $query->getResultArray();

     //           // Map attendance data by date for quick lookup
     //           $attendanceDaysMap = [];
     //           foreach ($attendanceDaysRaw as $row) {
     //                $attendanceDaysMap[$row['date']] = [
     //                     'date' => $row['date'],
     //                     'punch_in' => $row['punch_in'],
     //                     'punch_out' => $row['punch_out']
     //                ];
     //           }

     //           // Generate all dates for the month
     //           $days = [];
     //           for ($d = 1; $d <= $monthlyTotalDays; $d++) {
     //                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
     //                if (isset($attendanceDaysMap[$dateStr])) {
     //                     $days[] = $attendanceDaysMap[$dateStr];
     //                } else {
     //                     $days[] = [
     //                          'date' => $dateStr,
     //                          'punch_in' => null,
     //                          'punch_out' => null
     //                     ];
     //                }
     //           }

     //           $monthlyPresent = count($attendanceDaysRaw);
     //           $monthlyAbsent = $monthlyTotalDays - $monthlyPresent;

     //           // Week off
     //           $employeeData = $defaultDB->table('employees')
     //                ->select('emp_id, week_off')
     //                ->where('employee_code', $employee_code)
     //                ->get()
     //                ->getRowArray();

     //           $weekoff = (!empty($employeeData) && !empty($employeeData['week_off']))
     //                ? $employeeData['week_off']
     //                : 'Sunday';

     //           // Holidays
     //           $holidays = $defaultDB->table('holiday')
     //                ->select('date as holiday_date, holiday as holiday_name')
     //                ->where('YEAR(date)', $year)
     //                ->where('MONTH(date)', $month)
     //                ->where('status', 'A')
     //                ->get()
     //                ->getResultArray();

     //           // Duty roster data for the employee and date range, join with shiftslist
     //           $dutyRosterBuilder = $defaultDB->table('duty_roster');
     //           $dutyRosterBuilder->select('
     //         duty_roster.attendance_date,
     //         duty_roster.custom_weekoff_date,
     //         shiftslist.id as shift_id,
     //         shiftslist.ShiftName,
     //         shiftslist.ShiftStart,
     //         shiftslist.ShiftEnd,
     //         shiftslist.WorkingsHoursToBeConsiderdFullDay,
     //         shiftslist.WorkingsHoursToBeConsiderdHalfDay,
     //         shiftslist.status as shift_status,
     //         duty_roster.createdBy,
     //         duty_roster.createdDTM,
     //         duty_roster.updatedBy,
     //         duty_roster.updatedDTM
     //     ');
     //           $dutyRosterBuilder->join('shiftslist', 'shiftslist.id = duty_roster.shift_id', 'left');
     //           $dutyRosterBuilder->where('duty_roster.emp_id', $employeeData['emp_id']);
     //           $dutyRosterBuilder->where('duty_roster.attendance_date >=', $startOfMonth);
     //           $dutyRosterBuilder->where('duty_roster.attendance_date <=', $endOfMonth);

     //           $dutyRosterData = $dutyRosterBuilder->get()->getResultArray();

     //           return [
     //                'month_range' => [
     //                     'start' => $startOfMonth,
     //                     'end'   => $endOfMonth,
     //                     'year'  => (int)$year,
     //                     'month' => (int)$month,
     //                     'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
     //                ],
     //                'attendance' => [
     //                     'present_days' => $monthlyPresent,
     //                     'absent_days'  => $monthlyAbsent,
     //                     'total_days'   => $monthlyTotalDays,
     //                ],
     //                'employee_info' => [
     //                     'week_off' => $weekoff,
     //                ],
     //                'holidays' => $holidays,
     //                'days' => $days, // Now includes all dates of the month
     //                'duty_roster' => $dutyRosterData
     //           ];
     //      }



     // public function getEmployeeAttendance($employee_code, $selectedMonth, $selectedToMonth = null)
     // {
     //      $db = \Config\Database::connect('secondary');
     //      $defaultDB = \Config\Database::connect('default');
     //      $builder = $db->table('new_punch_list');

     //      // Parse month range (existing code remains same)
     //      if (empty($selectedMonth)) {
     //           $year = date('Y');
     //           $month = date('n');
     //           $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
     //      } else {
     //           $dateParts = explode('-', $selectedMonth);
     //           if (count($dateParts) == 2) {
     //                $year = (int)$dateParts[0];
     //                $month = (int)$dateParts[1];
     //                $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
     //           } else {
     //                $year = date('Y');
     //                $month = date('n');
     //                $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
     //           }
     //      }

     //      // If $selectedToMonth is provided, use it as end month, else use $selectedMonth
     //      if (!empty($selectedToMonth)) {
     //           $toDateParts = explode('-', $selectedToMonth);
     //           if (count($toDateParts) == 2) {
     //                $toYear = (int)$toDateParts[0];
     //                $toMonth = (int)$toDateParts[1];
     //                $endOfMonth = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $toYear, $toMonth)));
     //           } else {
     //                $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
     //           }
     //      } else {
     //           $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
     //      }

     //      // Calculate total days in range (existing code remains same)
     //      $period = new \DatePeriod(
     //           new \DateTime($startOfMonth),
     //           new \DateInterval('P1D'),
     //           (new \DateTime($endOfMonth))->modify('+1 day')
     //      );
     //      $allDates = [];
     //      foreach ($period as $dt) {
     //           $allDates[] = $dt->format('Y-m-d');
     //      }
     //      $totalDays = count($allDates);

     //      // Attendance data (existing code remains same)
     //      $query = $builder->select('DATE(LogDate) as date, MIN(LogDate) as punch_in, MAX(LogDate) as punch_out')
     //           ->where('UserId', $employee_code)
     //           ->where('DATE(LogDate) >=', $startOfMonth)
     //           ->where('DATE(LogDate) <=', $endOfMonth)
     //           ->where('status', '1')
     //           ->groupBy('DATE(LogDate)')
     //           ->orderBy('DATE(LogDate)', 'ASC')
     //           ->get();

     //      $attendanceDaysRaw = $query->getResultArray();

     //      // Map attendance data by date for quick lookup
     //      $attendanceDaysMap = [];
     //      foreach ($attendanceDaysRaw as $row) {
     //           $attendanceDaysMap[$row['date']] = [
     //                'date' => $row['date'],
     //                'punch_in' => $row['punch_in'],
     //                'punch_out' => $row['punch_out']
     //           ];
     //      }

     //      // Employee data (existing code remains same)
     //      $employeeData = $defaultDB->table('employees')
     //           ->select('emp_id, week_off, emp_type')
     //           ->where('employee_code', $employee_code)
     //           ->get()
     //           ->getRowArray();

     //      $weekoff = (!empty($employeeData) && !empty($employeeData['week_off']))
     //           ? $employeeData['week_off']
     //           : 'Sunday';
     //      $emp_type = $employeeData['emp_type'] ?? 'CONTRACTUAL_EMPLOYEE';

     //      // Holidays (existing code remains same)
     //      $holidays = $defaultDB->table('holiday')
     //           ->select('date as holiday_date, holiday as holiday_name')
     //           ->where('date >=', $startOfMonth)
     //           ->where('date <=', $endOfMonth)
     //           ->where('status', 'A')
     //           ->get()
     //           ->getResultArray();

     //      // Duty roster data (existing code remains same)
     //      $dutyRosterBuilder = $defaultDB->table('duty_roster');
     //      if ($emp_type === 'DOCTOR') {
     //           $dutyRosterBuilder->select('
     //        duty_roster.attendance_date,
     //        duty_roster.custom_weekoff_date,
     //        doctors_shift_master.id as shift_id,
     //        doctors_shift_master.shift_name,
     //        doctors_shift_master.in_time,
     //        doctors_shift_master.out_time,
     //        doctors_shift_master.total_hours,
     //        doctors_shift_master.total_minutes,
     //        doctors_shift_master.grace_in,
     //        doctors_shift_master.grace_out,
     //        doctors_shift_master.exemption_limit,
     //        doctors_shift_master.status as shift_status,
     //        duty_roster.createdBy,
     //        duty_roster.createdDTM,
     //        duty_roster.updatedBy,
     //        duty_roster.updatedDTM
     //    ');
     //           $dutyRosterBuilder->join('doctors_shift_master', 'doctors_shift_master.id = duty_roster.shift_id', 'left');
     //      } elseif ($emp_type === 'CONTRACTUAL_EMPLOYEE' || $emp_type === 'POOJARI') {
     //           $dutyRosterBuilder->select('
     //        duty_roster.attendance_date,
     //        duty_roster.custom_weekoff_date,
     //        shiftslist.id as shift_id,
     //        shiftslist.ShiftName as shift_name,
     //        shiftslist.ShiftStart as in_time,
     //        shiftslist.ShiftEnd as out_time,
     //        shiftslist.WorkingsHoursToBeConsiderdFullDay as total_hours,
     //        shiftslist.WorkingsHoursToBeConsiderdHalfDay,
     //        shiftslist.late_login_applicable,
     //        shiftslist.emp_type,
     //        shiftslist.status as shift_status,
     //        duty_roster.createdBy,
     //        duty_roster.createdDTM,
     //        duty_roster.updatedBy,
     //        duty_roster.updatedDTM
     //    ');
     //           $dutyRosterBuilder->join('shiftslist', 'shiftslist.id = duty_roster.shift_id', 'left');
     //      }
     //      $dutyRosterBuilder->where('duty_roster.emp_id', $employeeData['emp_id']);
     //      $dutyRosterBuilder->where('duty_roster.attendance_date >=', $startOfMonth);
     //      $dutyRosterBuilder->where('duty_roster.attendance_date <=', $endOfMonth);
     //      $dutyRosterData = $dutyRosterBuilder->get()->getResultArray();
     //      // Create roster map for quick lookup
     //      $rosterMap = [];
     //      foreach ($dutyRosterData as $roster) {
     //           $rosterMap[$roster['attendance_date']] = $roster;
     //      }

     //      // Generate all dates for the range with shortfall calculation
     //      $days = [];
     //      foreach ($allDates as $dateStr) {
     //           $dayData = [
     //                'date' => $dateStr,
     //                'punch_in' => null,
     //                'punch_out' => null,
     //                'shift_total_minutes' => 0,
     //                'worked_seconds' => 0,
     //                'shortfall_seconds' => 0,
     //                'shortfall_hours' => '00:00:00',
     //                'has_shortfall' => false
     //           ];

     //           // Add attendance data if exists
     //           if (isset($attendanceDaysMap[$dateStr])) {
     //                $dayData['punch_in'] = $attendanceDaysMap[$dateStr]['punch_in'];
     //                $dayData['punch_out'] = $attendanceDaysMap[$dateStr]['punch_out'];
     //           }

     //           // Calculate shortfall if punch data and roster exist
     //           if ($dayData['punch_in'] && $dayData['punch_out'] && isset($rosterMap[$dateStr])) {
     //                $roster = $rosterMap[$dateStr];

     //                // Calculate worked seconds (not minutes)
     //                $punchIn = new \DateTime($dayData['punch_in']);
     //                $punchOut = new \DateTime($dayData['punch_out']);
     //                $workedSeconds = $punchOut->getTimestamp() - $punchIn->getTimestamp();

     //                // Get shift total seconds
     //                $shiftTotalSeconds = 0;
     //                if ($emp_type === 'DOCTOR') {
     //                     $shiftTotalMinutes = (int)($roster['total_minutes'] ?? 0);
     //                     $shiftTotalSeconds = $shiftTotalMinutes * 60;
     //                } else {
     //                     // For contractual employees, convert hours to seconds
     //                     $totalHours = (float)($roster['total_hours'] ?? 0);
     //                     $shiftTotalSeconds = $totalHours * 3600;
     //                }

     //                // Calculate shortfall in seconds
     //                $shortfallSeconds = max(0, $shiftTotalSeconds - $workedSeconds);

     //                // Add shortfall data
     //                $dayData['shift_total_minutes'] = $shiftTotalMinutes ?? 0;
     //                $dayData['worked_seconds'] = (int)$workedSeconds;
     //                $dayData['shortfall_seconds'] = (int)$shortfallSeconds;
     //                $dayData['shortfall_hours'] = $this->minutesToHMS($shortfallSeconds);
     //                $dayData['has_shortfall'] = $shortfallSeconds > 0;
     //           }

     //           $days[] = $dayData;
     //      }

     //      $monthlyPresent = count($attendanceDaysRaw);
     //      $monthlyAbsent = $totalDays - $monthlyPresent;

     //      return [
     //           'month_range' => [
     //                'start' => $startOfMonth,
     //                'end'   => $endOfMonth,
     //                'start_year'  => (int)date('Y', strtotime($startOfMonth)),
     //                'start_month' => (int)date('n', strtotime($startOfMonth)),
     //                'end_year'    => (int)date('Y', strtotime($endOfMonth)),
     //                'end_month'   => (int)date('n', strtotime($endOfMonth)),
     //                'start_month_name' => date('F', strtotime($startOfMonth)),
     //                'end_month_name'   => date('F', strtotime($endOfMonth)),
     //           ],
     //           'attendance' => [
     //                'present_days' => $monthlyPresent,
     //                'absent_days'  => $monthlyAbsent,
     //                'total_days'   => $totalDays,
     //           ],
     //           'employee_info' => [
     //                'week_off' => $weekoff,
     //                'emp_type' => $emp_type
     //           ],
     //           'holidays' => $holidays,
     //           'days' => $days, // Now includes shortfall data
     //           'duty_roster' => $dutyRosterData
     //      ];
     // }




     public function getNewUserDetails($emp_code)
     {
          // log_message('error', '  user id ' . $emp_code);
          if ($emp_code > 0) {
               // Connect to travelapp DB for new_emp_master
               $travelDb = \Config\Database::connect('travelapp');
               // Connect to default DB for users
               $defaultDb = \Config\Database::connect('default');

               // Fetch user details from travelapp.new_emp_master
               $empMaster = $travelDb->table('new_emp_master')
                    ->where('emp_code', $emp_code)
                    ->get()
                    ->getRowArray();

               if (!$empMaster) {
                    return false;
               }

               // Fetch role from default.users
               $user = $defaultDb->table('users')
                    ->select('role')
                    ->where('user_code', $emp_code)
                    ->get()
                    ->getRowArray();

               // Merge role into empMaster
               if ($user && isset($user['role'])) {
                    $empMaster['role'] = $user['role'];
               } else {
                    $empMaster['role'] = null;
               }

               // Return as object for compatibility
               return (object)$empMaster;
          }
          return false;
     }


     public function getNewUserDetails2($emp_code)
     {
          if ($emp_code > 0) {
               // Connect to default DB for users
               $defaultDb = \Config\Database::connect('default');
               $payrollDb = \Config\Database::connect('secondary');

               // Fetch user details (including password) from new_emp_master_hrms
               $empMaster = $payrollDb->table('new_emp_master')
                    ->where('emp_code', $emp_code)
                    ->get()
                    ->getRowArray();

               if (!$empMaster) {
                    return false;
               }

               // Fetch only the role from users table
               $user = $defaultDb->table('users')
                    ->select('role')
                    ->where('user_code', $emp_code)
                    ->get()
                    ->getRowArray();

               // Merge role into empMaster
               $empMaster['role'] = $user['role'] ?? null;

               // Return as object for compatibility
               return (object)$empMaster;
          }
          return false;
     }

     public function insertLoginData($data)
     {
          return $this->db->table('login_sessions')->insert($data);
     }

     public function getEmployeeSplitDutyAttendance($employee_code, $selectedMonth, $selectedToMonth = null)
     {
          $db = \Config\Database::connect('secondary');
          $defaultDB = \Config\Database::connect('default');

          // Parse month range
          if (empty($selectedMonth)) {
               $year = date('Y');
               $month = date('n');
               $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
          } else {
               $dateParts = explode('-', $selectedMonth);
               if (count($dateParts) == 2) {
                    $year = (int)$dateParts[0];
                    $month = (int)$dateParts[1];
                    $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
               } else {
                    $year = date('Y');
                    $month = date('n');
                    $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
               }
          }

          if (!empty($selectedToMonth)) {
               $toDateParts = explode('-', $selectedToMonth);
               if (count($toDateParts) == 2) {
                    $toYear = (int)$toDateParts[0];
                    $toMonth = (int)$toDateParts[1];
                    $endOfMonth = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $toYear, $toMonth)));
               } else {
                    $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
               }
          } else {
               $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
          }

          // Get employee info
          $employeeData = $defaultDB->table('employees')
               ->select('emp_id, week_off, emp_type')
               ->where('employee_code', $employee_code)
               ->get()
               ->getRowArray();

          if (!$employeeData) {
               return null;
          }

          $emp_type = $employeeData['emp_type'] ?? 'CONTRACTUAL_EMPLOYEE';

          // Get duty roster with split shifts
          $dutyRosterBuilder = $defaultDB->table('duty_roster');

          if ($emp_type === 'DOCTOR') {
               $dutyRosterBuilder->select('
               duty_roster.id,
               duty_roster.emp_id,
               duty_roster.shift_id,
               duty_roster.attendance_date,
               duty_roster.custom_weekoff_date,
               duty_roster.split_shift_type,
               doctors_shift_master.shift_name,
               doctors_shift_master.in_time,
               doctors_shift_master.out_time,
               doctors_shift_master.total_hours,
               doctors_shift_master.total_minutes
          ');
               $dutyRosterBuilder->join('doctors_shift_master', 'doctors_shift_master.id = duty_roster.shift_id', 'left');
          } else {
               $dutyRosterBuilder->select('
               duty_roster.id,
               duty_roster.emp_id,
               duty_roster.shift_id,
               duty_roster.attendance_date,
               duty_roster.custom_weekoff_date,
               duty_roster.split_shift_type,
               shiftslist.ShiftName as shift_name,
               shiftslist.ShiftStart as in_time,
               shiftslist.ShiftEnd as out_time,
               shiftslist.WorkingsHoursToBeConsiderdFullDay as total_hours
          ');
               $dutyRosterBuilder->join('shiftslist', 'shiftslist.id = duty_roster.shift_id', 'left');
          }

          $dutyRosterBuilder->where('duty_roster.emp_id', $employeeData['emp_id']);
          $dutyRosterBuilder->where('duty_roster.attendance_date >=', $startOfMonth);
          $dutyRosterBuilder->where('duty_roster.attendance_date <=', $endOfMonth);
          $dutyRosterBuilder->orderBy('duty_roster.attendance_date', 'ASC');
          $dutyRosterBuilder->orderBy('duty_roster.split_shift_type', 'ASC');

          $dutyRosterData = $dutyRosterBuilder->get()->getResultArray();

          // Group shifts by date
          $groupedByDate = [];
          foreach ($dutyRosterData as $row) {
               $date = $row['attendance_date'];
               if (!isset($groupedByDate[$date])) {
                    $groupedByDate[$date] = [
                         'date' => $date,
                         'shifts' => [],
                         'is_weekoff' => !empty($row['custom_weekoff_date'])
                    ];
               }
               if ($row['shift_id']) {
                    $groupedByDate[$date]['shifts'][] = [
                         'shift_id' => $row['shift_id'],
                         'shift_name' => $row['shift_name'],
                         'in_time' => $row['in_time'],
                         'out_time' => $row['out_time'],
                         'total_hours' => $row['total_hours'],
                         'total_minutes' => $row['total_minutes'] ?? null,
                         'split_shift_type' => $row['split_shift_type']
                    ];
               }
          }

          return [
               'employee_code' => $employee_code,
               'emp_type' => $emp_type,
               'month_range' => [
                    'start' => $startOfMonth,
                    'end' => $endOfMonth
               ],
               'roster' => array_values($groupedByDate)
          ];
     }

     private function processShiftPunches($allPunches, $shiftStartTs = null, $shiftEndTs = null)
     {
          // Remove duplicates and keep stable order
          $allPunches = array_values(array_unique($allPunches));

          // If window provided, filter by timestamp; else include all punches
          $shiftPunches = [];
          foreach ($allPunches as $p) {
               $pTs = strtotime($p);
               if ($shiftStartTs !== null && $shiftEndTs !== null) {
                    if ($pTs >= $shiftStartTs && $pTs <= $shiftEndTs) {
                         $shiftPunches[] = $p;
                    }
               } else {
                    $shiftPunches[] = $p;
               }
          }

          // Sort by time
          usort($shiftPunches, function ($a, $b) {
               return strtotime($a) <=> strtotime($b);
          });

          // Pair punches as in/out
          $workedSeconds = 0;
          $pairedPunches = [];
          $count = count($shiftPunches);
          for ($i = 0; $i < $count - 1; $i += 2) {
               $in = $shiftPunches[$i];
               $out = $shiftPunches[$i + 1];
               $workedSeconds += max(0, strtotime($out) - strtotime($in));
               $pairedPunches[] = [$in, $out];
          }

          // If odd number of punches, last punch is unpaired (missed punch)
          $missedPunch = ($count % 2 !== 0) ? $shiftPunches[$count - 1] : null;

          return [
               'worked_seconds' => (int)$workedSeconds,
               'paired_punches' => $pairedPunches,
               'missed_punch'   => $missedPunch,
               'all_punches'    => array_values($shiftPunches)
          ];
     }
}
