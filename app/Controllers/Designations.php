<?php

namespace App\Controllers;

use App\Models\DesignationsModel;
use App\Controllers\BaseController;

class Designations extends BaseController
{
     protected $model;

     public function __construct()
     {
          $this->model = new DesignationsModel();
          helper(['form', 'url']);
     }

     /**
      * Get all active designations
      */
     public function getDesignations()
     {
          try {
               $designations = $this->model->where('status', 'A')->orderBy('designation_type', 'ASC')->findAll();

               return $this->response->setJSON([
                    'status' => true,
                    'data' => $designations,
                    'count' => count($designations)
               ]);
          } catch (\Exception $e) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Error fetching designations: ' . $e->getMessage()
               ])->setStatusCode(500);
          }
     }

     /**
      * Get all designations (including inactive)
      */
     public function getAllDesignations()
     {
          try {
               $designations = $this->model->orderBy('designation_type', 'ASC')->findAll();

               return $this->response->setJSON([
                    'status' => true,
                    'data' => $designations,
                    'count' => count($designations)
               ]);
          } catch (\Exception $e) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Error fetching all designations: ' . $e->getMessage()
               ])->setStatusCode(500);
          }
     }

     /**
      * Get designation by ID
      */
     public function getDesignationById($id = null)
     {
          try {
               if (!$id) {
                    return $this->response->setJSON([
                         'status' => false,
                         'message' => 'Designation ID is required'
                    ])->setStatusCode(400);
               }

               $designation = $this->model->find($id);

               if (!$designation) {
                    return $this->response->setJSON([
                         'status' => false,
                         'message' => 'Designation not found'
                    ])->setStatusCode(404);
               }

               return $this->response->setJSON([
                    'status' => true,
                    'data' => $designation
               ]);
          } catch (\Exception $e) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Error fetching designation: ' . $e->getMessage()
               ])->setStatusCode(500);
          }
     }

     /**
      * Create new designation
      */
     public function createDesignation()
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['emp_code'];

               $json = $this->request->getJSON(true);

               // Validate required fields
               if (empty($json['designation_type'])) {
                    return $this->response->setJSON([
                         'status' => false,
                         'message' => 'designation_type is required'
                    ])->setStatusCode(400);
               }

               // Check if designation already exists
               $existing = $this->model->where('designation_type', $json['designation_type'])->first();
               if ($existing) {
                    return $this->response->setJSON([
                         'status' => false,
                         'message' => 'Designation already exists'
                    ])->setStatusCode(409);
               }

               $data = [
                    'designation_type' => trim($json['designation_type']),
                    'status' => $json['status'] ?? 'A',
                    'created_by' => $user,
                    'created_on' => date('Y-m-d H:i:s'),
                    'modified_by' => $user,
                    'modified_on' => date('Y-m-d H:i:s')
               ];

               if ($this->model->insert($data)) {
                    return $this->response->setJSON([
                         'status' => true,
                         'message' => 'Designation created successfully',
                         'designation_id' => $this->model->getInsertID()
                    ])->setStatusCode(201);
               }

               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Failed to create designation',
                    'errors' => $this->model->errors()
               ])->setStatusCode(400);
          } catch (\Exception $e) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Error creating designation: ' . $e->getMessage()
               ])->setStatusCode(500);
          }
     }

     /**
      * Update designation by ID
      */
     public function updateDesignationById($id = null)
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['emp_code'];

               if (!$id) {
                    return $this->response->setJSON([
                         'status' => false,
                         'message' => 'Designation ID is required'
                    ])->setStatusCode(400);
               }

               $json = $this->request->getJSON(true);

               // Check if designation exists
               $existing = $this->model->find($id);
               if (!$existing) {
                    return $this->response->setJSON([
                         'status' => false,
                         'message' => 'Designation not found'
                    ])->setStatusCode(404);
               }

               // Build update data
               $data = [];

               if (isset($json['designation_type'])) {
                    // Check if new designation_type already exists (excluding current record)
                    $duplicate = $this->model->where('designation_type', trim($json['designation_type']))
                         ->where('id !=', $id)
                         ->first();
                    if ($duplicate) {
                         return $this->response->setJSON([
                              'status' => false,
                              'message' => 'Designation type already exists'
                         ])->setStatusCode(409);
                    }
                    $data['designation_type'] = trim($json['designation_type']);
               }

               if (isset($json['status'])) {
                    $data['status'] = $json['status'];
               }

               $data['modified_by'] = $user;
               $data['modified_on'] = date('Y-m-d H:i:s');

               if ($this->model->update($id, $data)) {
                    return $this->response->setJSON([
                         'status' => true,
                         'message' => 'Designation updated successfully'
                    ]);
               }

               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Failed to update designation',
                    'errors' => $this->model->errors()
               ])->setStatusCode(400);
          } catch (\Exception $e) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Error updating designation: ' . $e->getMessage()
               ])->setStatusCode(500);
          }
     }

     /**
      * Delete (soft delete) designation by ID
      */
     public function deleteDesignationById($id = null)
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['emp_code'];

               if (!$id) {
                    return $this->response->setJSON([
                         'status' => false,
                         'message' => 'Designation ID is required'
                    ])->setStatusCode(400);
               }

               $db = \Config\Database::connect();

               // Check if designation exists
               $existing = $db->table('designations')->where('id', $id)->get()->getRowArray();
               if (!$existing) {
                    return $this->response->setJSON([
                         'status' => false,
                         'message' => 'Designation not found'
                    ])->setStatusCode(404);
               }

               // Check if already inactive
               if ($existing['status'] === 'I') {
                    return $this->response->setJSON([
                         'status' => false,
                         'message' => 'Designation is already inactive'
                    ])->setStatusCode(400);
               }

               // Soft delete by setting status to 'I'
               $data = [
                    'status' => 'I',
                    'modified_by' => $user,
                    'modified_on' => date('Y-m-d H:i:s')
               ];

               $result = $db->table('designations')
                    ->where('id', $id)
                    ->update($data);

               if ($result) {
                    return $this->response->setJSON([
                         'status' => true,
                         'message' => 'Designation deleted successfully'
                    ]);
               }

               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Failed to delete designation'
               ])->setStatusCode(500);
          } catch (\Exception $e) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Error deleting designation: ' . $e->getMessage()
               ])->setStatusCode(500);
          }
     }

     /**
      * Search designations by keyword
      */
     public function searchDesignations()
     {
          try {
               $json = $this->request->getJSON(true);
               $keyword = $json['keyword'] ?? '';

               if (empty($keyword)) {
                    return $this->response->setJSON([
                         'status' => false,
                         'message' => 'Search keyword is required'
                    ])->setStatusCode(400);
               }

               $designations = $this->model
                    ->like('designation_type', $keyword)
                    ->where('status', 'A')
                    ->orderBy('designation_type', 'ASC')
                    ->findAll();

               return $this->response->setJSON([
                    'status' => true,
                    'data' => $designations,
                    'count' => count($designations),
                    'keyword' => $keyword
               ]);
          } catch (\Exception $e) {
               return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Error searching designations: ' . $e->getMessage()
               ])->setStatusCode(500);
          }
     }
}
