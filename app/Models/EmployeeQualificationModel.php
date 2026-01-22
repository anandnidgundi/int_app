<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeQualificationModel extends Model
{
     protected $table = 'employee_qualifications';
     protected $primaryKey = 'id';
     protected $useAutoIncrement = true;
     protected $returnType = 'array';
     protected $useSoftDeletes = false;
     protected $protectFields = true;

     protected $allowedFields = [
          'emp_id',
          'qualification',
          'specialization',
          'yearOfPassing',
          'collegeName',
          'status'
     ];

     protected $useTimestamps = false;
     protected $createdField = null;
     protected $updatedField = null;
     protected $deletedField = null;

     protected $validationRules = [
          'emp_id' => 'required',
          'qualification' => 'permit_empty|max_length[50]',
          'specialization' => 'permit_empty|max_length[100]',
          'collegeName' => 'permit_empty|max_length[150]',
          'status' => 'required|max_length[1]|in_list[A,I]'
     ];

     protected $validationMessages = [
          'emp_id' => [
               'required' => 'Employee ID is required'
          ]
     ];

     protected $skipValidation = false;
     protected $cleanValidationRules = true;

     /**
      * Get qualifications for a specific employee
      */
     public function getEmployeeQualifications($empId)
     {
          return $this->where('emp_id', $empId)
               ->where('status', 'A')
               ->orderBy('yearOfPassing', 'DESC')
               ->findAll();
     }

     /**
      * Add qualification record for employee with payload format
      */
     public function addQualificationFromPayload($empId, $educationData)
     {
          $data = [
               'emp_id' => $empId,
               'qualification' => $educationData['highest_qualification'] ?? '',
               'collegeName' => $educationData['university'] ?? '',
               'yearOfPassing' => isset($educationData['passing_year']) ? $educationData['passing_year'] . '-01-01' : null,
               'specialization' => $educationData['specialization'] ?? '',
               'status' => 'A'
          ];
          return $this->insert($data);
     }

     /**
      * Add multiple qualifications from payload
      */
     public function addMultipleQualifications($empId, $educations)
     {
          $results = [];
          foreach ($educations as $education) {
               $results[] = $this->addQualificationFromPayload($empId, $education);
          }
          return $results;
     }

     /**
      * Add qualification record for employee
      */
     public function addQualification($data)
     {
          $data['status'] = $data['status'] ?? 'A';
          return $this->insert($data);
     }

     /**
      * Update qualification record
      */
     public function updateQualification($id, $data)
     {
          return $this->update($id, $data);
     }

     /**
      * Delete qualification record (soft delete)
      */
     public function deleteQualification($id)
     {
          return $this->update($id, ['status' => 'I']);
     }

     /**
      * Get highest qualification for employee
      */
     public function getHighestQualification($empId)
     {
          return $this->where('emp_id', $empId)
               ->where('status', 'A')
               ->orderBy('yearOfPassing', 'DESC')
               ->first();
     }

     /**
      * Get qualifications by type
      */
     public function getQualificationsByType($qualification)
     {
          return $this->where('qualification', $qualification)
               ->where('status', 'A')
               ->findAll();
     }

     /**
      * Get qualification count for employee
      */
     public function getQualificationCount($empId)
     {
          return $this->where('emp_id', $empId)
               ->where('status', 'A')
               ->countAllResults();
     }

     /**
      * Get active qualifications
      */
     public function getActiveQualifications()
     {
          return $this->where('status', 'A')->findAll();
     }
}
