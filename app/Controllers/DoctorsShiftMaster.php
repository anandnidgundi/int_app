<?php

namespace App\Controllers;

use App\Models\DoctorsShiftMasterModel;
use App\Controllers\BaseController;

class DoctorsShiftMaster extends BaseController
{
     protected $model;

     public function __construct()
     {
          $this->model = new DoctorsShiftMasterModel();
     }


     public function getDoctorShifts()
     {
          $shifts = $this->model
               ->where('status', 'A')
               ->where('split_shift', 'N')
               ->findAll();
          return $this->response->setJSON([
               'status' => true,
               'data' => $shifts
          ]);
     }

     public function getDoctorsSplitShifts()
     {
          $shifts = $this->model
               ->where('status', 'A')
               ->where('split_shift', 'Y')
               ->findAll();
          return $this->response->setJSON([
               'status' => true,
               'data' => $shifts
          ]);
     }

     public function createDoctorShift()
     {
          $auth = $this->validateAuthorization();
          if (!$auth) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Unauthorized'
               ])->setStatusCode(401);
          }
          $user = $auth['emp_code'];
          $json = $this->request->getJSON(true);

          // Validate required fields
          if (empty($json['shift_name']) || empty($json['in_time']) || empty($json['out_time'])) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'shift_name, in_time, and out_time are required'
               ])->setStatusCode(400);
          }

          // Convert total_hours from "8:00" to integer hours if needed
          $total_hours = $json['total_hours'] ?? null;
          if ($total_hours && strpos($total_hours, ':') !== false) {
               list($h, $m) = explode(':', $total_hours);
               $total_hours = intval($h) + intval($m) / 60;
          }

          $data = [
               'shift_name'      => $json['shift_name'],
               'in_time'         => $json['in_time'],
               'out_time'        => $json['out_time'],
               'total_hours'     => $total_hours,
               'total_minutes'   => $json['total_minutes'] ?? null,
               'grace_in'        => $json['grace_in'] ?? null,
               'grace_out'       => $json['grace_out'] ?? null,
               'exemption_limit' => $json['exemption_limit'] ?? null,
               'status'          => $json['status'] ?? 'A',
               'split_shift'     => $json['split_shift'] ?? 'N',
               'created_on'      => date('Y-m-d H:i:s'),
               'created_by'      => $user,
          ];

          if ($this->model->insert($data)) {
               return $this->response->setJSON([
                    'status' => true,
                    'message' => 'Doctor shift created successfully'
               ]);
          }
          return $this->response->setJSON([
               'status' => false,
               'message' => 'Failed to create doctor shift',
               'errors' => $this->model->errors()
          ]);
     }

     public function getDoctorShiftById($id)
     {
          $shift = $this->model->find($id);
          if ($shift) {
               return $this->response->setJSON([
                    'status' => true,
                    'data' => $shift
               ]);
          }
          return $this->response->setJSON([
               'status' => false,
               'message' => 'Doctor shift not found'
          ]);
     }

     public function updateDoctorShiftById($id)
     {
          $auth = $this->validateAuthorization();
          if (!$auth) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Unauthorized'
               ])->setStatusCode(401);
          }
          $user = $auth['emp_code'];

          $data = $this->request->getJSON(true);
          if ($this->model->update($id, $data)) {
               return $this->response->setJSON([
                    'status' => true,
                    'message' => 'Doctor shift updated successfully'
               ]);
          }
          return $this->response->setJSON([
               'status' => false,
               'message' => 'Failed to update doctor shift',
               'errors' => $this->model->errors()
          ]);
     }

     public function deleteDoctorShiftById($id)
     {
          $auth = $this->validateAuthorization();
          if (!$auth) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Unauthorized'
               ])->setStatusCode(401);
          }
          $user = $auth['emp_code'];
          // change status to 'I' for inactive instead of deleting
          if ($this->model->update($id, ['status' => 'I', 'modified_by' => $user])) {
               return $this->response->setJSON([
                    'status' => true,
                    'message' => 'Doctor shift deleted successfully'
               ]);
          }

          return $this->response->setJSON([
               'status' => false,
               'message' => 'Failed to delete doctor shift'
          ]);
     }
}
