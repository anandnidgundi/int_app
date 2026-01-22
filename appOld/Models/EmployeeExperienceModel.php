<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeExperienceModel extends Model
{
     protected $table = 'employee_experience';
     protected $primaryKey = 'id';
     protected $useAutoIncrement = true;
     protected $returnType = 'array';
     protected $useSoftDeletes = false;
     protected $protectFields = true;

     protected $allowedFields = [
          'emp_id',
          'previous_company',
          'previous_designation',
          'experience_years',
          'status'
     ];

     protected $useTimestamps = false;
     protected $createdField = null;
     protected $updatedField = null;
     protected $deletedField = null;

     protected $validationRules = [
          'emp_id' => 'required',
          'previous_company' => 'permit_empty|max_length[100]',
          'previous_designation' => 'permit_empty|max_length[100]',
          'experience_years' => 'permit_empty|max_length[10]',
          'status' => 'required|max_length[1]|in_list[A,I]'
     ];

     protected $validationMessages = [
          'emp_id' => [
               'required' => 'Employee ID is required'
          ],
          'previous_company' => [
               'required' => 'Previous company is required'
          ],
          'previous_designation' => [
               'required' => 'Previous designation is required'
          ]
     ];

     protected $skipValidation = false;
     protected $cleanValidationRules = true;

     /**
      * Get experience records for a specific employee
      */
     public function getEmployeeExperience($empId)
     {
          return $this->where('emp_id', $empId)
               ->where('status', 'A')
               ->findAll();
     }

     /**
      * Add experience record from payload format
      */
     public function addExperienceFromPayload($empId, $experienceData)
     {
          $data = [
               'emp_id' => $empId,
               'previous_company' => $experienceData['previous_company'] ?? '',
               'previous_designation' => $experienceData['previous_designation'] ?? '',
               'experience_years' => $experienceData['previous_experience_years'] ?? '0',
               'status' => 'A'
          ];
          return $this->insert($data);
     }

     /**
      * Add multiple experiences from payload
      */
     public function addMultipleExperiences($empId, $experiences)
     {
          $results = [];
          foreach ($experiences as $experience) {
               $results[] = $this->addExperienceFromPayload($empId, $experience);
          }
          return $results;
     }

     /**
      * Add experience record for employee
      */
     public function addExperience($data)
     {
          $data['status'] = $data['status'] ?? 'A';
          return $this->insert($data);
     }

     /**
      * Update experience record
      */
     public function updateExperience($id, $data)
     {
          return $this->update($id, $data);
     }

     /**
      * Delete experience record (soft delete)
      */
     public function deleteExperience($id)
     {
          return $this->update($id, ['status' => 'I']);
     }

     /**
      * Get total experience years for employee
      */
     public function getTotalExperience($empId)
     {
          $experiences = $this->getEmployeeExperience($empId);
          $totalYears = 0;
          foreach ($experiences as $exp) {
               $totalYears += floatval($exp['experience_years']);
          }
          return $totalYears;
     }

     /**
      * Get active experience records
      */
     public function getActiveExperience()
     {
          return $this->where('status', 'A')->findAll();
     }
}
