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
          'updatedDTM'
     ];

     public function getEmpByIdNew($id)
     {
          $db = \Config\Database::connect();
          $builder = $db->table('employees');

          $builder->select('
        employees.emp_id, 
        employees.employee_code, 
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
                         'documents' => []
                    ];
               }

               if (!empty($row['document_name'])) {
                    $users[$emp_id]['documents'][] = [
                         'document_name' => $row['document_name'],
                         'document_path' => $row['document_path']
                    ];
               }
          }

          // Return only the first employee (since you query by emp_id)
          return !empty($users) ? reset($users) : [];
     }


     public function getEmployeeAttendance($employee_code, $selectedMonth)
     {
          $db = \Config\Database::connect('secondary');
          $defaultDB = \Config\Database::connect('default');
          $builder = $db->table('new_punch_list');

          if (empty($selectedMonth)) {
               $year = date('Y');
               $month = date('n');
          } else {
               $dateParts = explode('-', $selectedMonth);
               if (count($dateParts) == 2) {
                    $year = (int)$dateParts[0];
                    $month = (int)$dateParts[1];
               } else {
                    $year = date('Y');
                    $month = date('n');
               }
          }

          $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
          $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
          $monthlyTotalDays = date('t', strtotime($startOfMonth));

          // Attendance data
          $query = $builder->select('DATE(LogDate) as date, MIN(LogDate) as punch_in, MAX(LogDate) as punch_out')
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
                    'punch_out' => $row['punch_out']
               ];
          }

          // Generate all dates for the month
          $days = [];
          for ($d = 1; $d <= $monthlyTotalDays; $d++) {
               $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
               if (isset($attendanceDaysMap[$dateStr])) {
                    $days[] = $attendanceDaysMap[$dateStr];
               } else {
                    $days[] = [
                         'date' => $dateStr,
                         'punch_in' => null,
                         'punch_out' => null
                    ];
               }
          }

          $monthlyPresent = count($attendanceDaysRaw);
          $monthlyAbsent = $monthlyTotalDays - $monthlyPresent;

          // Week off
          $employeeData = $defaultDB->table('employees')
               ->select('emp_id, week_off')
               ->where('employee_code', $employee_code)
               ->get()
               ->getRowArray();

          $weekoff = (!empty($employeeData) && !empty($employeeData['week_off']))
               ? $employeeData['week_off']
               : 'Sunday';

          // Holidays
          $holidays = $defaultDB->table('holiday')
               ->select('date as holiday_date, holiday as holiday_name')
               ->where('YEAR(date)', $year)
               ->where('MONTH(date)', $month)
               ->where('status', 'A')
               ->get()
               ->getResultArray();

          // Duty roster data for the employee and date range, join with shiftslist
          $dutyRosterBuilder = $defaultDB->table('duty_roster');
          $dutyRosterBuilder->select('
        duty_roster.attendance_date,
        duty_roster.custom_weekoff_date,
        shiftslist.id as shift_id,
        shiftslist.ShiftName,
        shiftslist.ShiftStart,
        shiftslist.ShiftEnd,
        shiftslist.WorkingsHoursToBeConsiderdFullDay,
        shiftslist.WorkingsHoursToBeConsiderdHalfDay,
        shiftslist.status as shift_status,
        duty_roster.createdBy,
        duty_roster.createdDTM,
        duty_roster.updatedBy,
        duty_roster.updatedDTM
    ');
          $dutyRosterBuilder->join('shiftslist', 'shiftslist.id = duty_roster.shift_id', 'left');
          $dutyRosterBuilder->where('duty_roster.emp_id', $employeeData['emp_id']);
          $dutyRosterBuilder->where('duty_roster.attendance_date >=', $startOfMonth);
          $dutyRosterBuilder->where('duty_roster.attendance_date <=', $endOfMonth);

          $dutyRosterData = $dutyRosterBuilder->get()->getResultArray();

          return [
               'month_range' => [
                    'start' => $startOfMonth,
                    'end'   => $endOfMonth,
                    'year'  => (int)$year,
                    'month' => (int)$month,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
               ],
               'attendance' => [
                    'present_days' => $monthlyPresent,
                    'absent_days'  => $monthlyAbsent,
                    'total_days'   => $monthlyTotalDays,
               ],
               'employee_info' => [
                    'week_off' => $weekoff,
               ],
               'holidays' => $holidays,
               'days' => $days, // Now includes all dates of the month
               'duty_roster' => $dutyRosterData
          ];
     }

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

     public function insertLoginData($data)
     {
          return $this->db->table('login_sessions')->insert($data);
     }
}
