<?php

namespace App\Models;

use CodeIgniter\Model;

class NewEmployeeMasterModel extends Model
{
     // use db group 'travelapp' from .env
     // For development, use the default DB group. Change to 'travelapp' in production.
     // protected $DBGroup = 'travelapp';

     protected $DBGroup = 'travelapp';

     protected $table = 'new_emp_master';
     protected $primaryKey = 'id';

     protected $allowedFields = [
          'emp_code',
          'fname',
          'lname',
          'mname',
          'comp_name',
          'doj',
          'dob',
          'gender',
          'mail_id',
          'report_mngr',
          'function_mngr',
          'ou_name',
          'dept_name',
          'location_name',
          'designation_name',
          'grade',
          'region',
          'country',
          'city',
          'position',
          'cost_center',
          'pay_group',
          'emp_status',
          'active',
          'disabled',
          'effective_from',
          'created_on',
          'created_by',
          'modified_on',
          'modified_by',
          'mobile',
          'depend1',
          'depend2',
          'depend3',
          'depend4',
          'depend5',
          'depend6',
          'exit_date',
          'password',
          'validity',
          'is_admin',
          'is_it_admin',
          'is_manager_approval',
          'is_traveldesk',
          'is_hotelinfo',
          'is_audit_approval',
          'is_finance_approval',
          'is_travelmanager_approved',
          'is_hotelmanager_approved',
          'updated_at',
          'failed_attempts',
          'bank_name',
          'bank_acnum',
          'ifsc_code',
          'vdcapp_admin',
          'vdcapp_super_admin',
          'driver_access_given',
          'check_list',
          'reminder',
          'travel_store',
          'tv',
          'is_docpay_access_given',
          'is_uniform_access_given',
          'is_dmg',
          'is_ticket',
          'is_osb',
          'is_phhr',
          'is_ot',
          'is_randr',
          'isPETCTadmin',
          'is_radiology_doctor',
          'is_QAD'
     ];

     protected $useTimestamps = false;
     protected $createdField  = 'created_on';
     protected $updatedField  = 'updated_at';

     protected $beforeInsert = ['fillDefaults'];
     protected $beforeUpdate = ['fillDefaults'];


     protected function fillDefaults(array $data)
     {
          $fields = &$data['data'];

          $defaults = [
               'bank_name'               => '',
               'bank_acnum'              => '',
               'ifsc_code'               => '',
               'failed_attempts'         => 0,
               'session_token'           => '',
               'session_admin_token'     => '',
               'check_list'              => 'N',
               'reminder'                => 'N',
               'is_radiology_doctor'     => $fields['is_radiology_doctor'] ?? 'N',
               'isPETCTadmin'            => $fields['isPETCTadmin'] ?? 'N',
               'vdcapp_admin'            => $fields['vdcapp_admin'] ?? 'N',
               'vdcapp_super_admin'      => $fields['vdcapp_super_admin'] ?? 'N',
               'driver_access_given'     => $fields['driver_access_given'] ?? 'N',
          ];

          foreach ($defaults as $col => $val) {
               if (!in_array($col, $this->allowedFields, true)) {
                    continue;
               }
               if (!array_key_exists($col, $fields) || $fields[$col] === null) {
                    $fields[$col] = $val;
               }
          }

          // Ensure created_on is set if not provided
          if (array_key_exists('created_on', $fields) === false || $fields['created_on'] === null) {
               $fields['created_on'] = date('Y-m-d H:i:s');
          }

          return $data;
     }

     public function getEmployeeByCode($empCode)
     {
          $defaultDB = \Config\Database::connect('default');
          // First, try to get from new_emp_master_hrms (default DB - complete data)
          $userDetails = $defaultDB->table('new_emp_master_hrms')->where('emp_code', $empCode)->get()->getRowArray();

          if (!$userDetails) {
               // Fallback to travelapp DB's new_emp_master (legacy/incomplete)
               $travelappDB = \Config\Database::connect('travelapp');
               $userDetails = $travelappDB->table('new_emp_master')
                    ->where('emp_code', $empCode)
                    ->get()
                    ->getRowArray();
          }

          // Remove password from response for security
          if ($userDetails && isset($userDetails['password'])) {
               unset($userDetails['password']);
          }

          return $userDetails;
     }

     public function getEmployeeByCodeNew($empCode, $role)
     {
          $defaultDB = \Config\Database::connect('default');

          if ($role != 'EMPLOYEE') {
               // Fallback to travelapp DB's new_emp_master (legacy/incomplete)
               $travelappDB = \Config\Database::connect('travelapp');
               $userDetails = $travelappDB->table('new_emp_master')
                    ->where('emp_code', $empCode)
                    ->get()
                    ->getRowArray();
          } else {
               // fetch from employee table in default DB, join employee_documents, employee_experience,  table, get list of documents, experience details
               $userDetails = $defaultDB->table('employees as emp')
                    ->where('emp.employee_code', $empCode)
                    ->get()
                    ->getRowArray();

               $emp_id = isset($userDetails['emp_id']) ? $userDetails['emp_id'] : null;
               if ($emp_id) {
                    // Get list of documents
                    $documents = $defaultDB->table('employee_documents')
                         ->where('emp_id', $emp_id)
                         ->get()
                         ->getResultArray();
                    $userDetails['documents'] = $documents;
                    // Get experience details
                    $experience = $defaultDB->table('employee_experience')
                         ->where('emp_id', $emp_id)
                         ->get()
                         ->getResultArray();
                    $userDetails['experience'] = $experience;

                    // Get qualification details
                    $qualification = $defaultDB->table('employee_qualifications')
                         ->where('emp_id', $emp_id)
                         ->get()
                         ->getResultArray();
                    $userDetails['qualification'] = $qualification;
               } else {
                    $userDetails['documents'] = [];
                    $userDetails['experience'] = [];
                    $userDetails['qualification'] = [];
               }
          }

          // Remove password from response for security
          if ($userDetails && isset($userDetails['password'])) {
               unset($userDetails['password']);
          }

          return $userDetails;
     }
}
