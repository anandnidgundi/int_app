<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeDocumentModel extends Model
{
     protected $table = 'employee_documents';
     protected $primaryKey = 'doc_id';
     protected $useAutoIncrement = true;
     protected $returnType = 'array';
     protected $useSoftDeletes = false;
     protected $protectFields = true;

     protected $allowedFields = [
          'emp_id',
          'document_name',
          'document_path',
          'uploaded_at'
     ];

     protected $useTimestamps = false;
     protected $createdField = null;
     protected $updatedField = null;
     protected $deletedField = null;

     protected $validationRules = [
          'emp_id' => 'required|integer',
          'document_name' => 'permit_empty|string',
          'document_path' => 'permit_empty|string'
     ];

     protected $validationMessages = [
          'emp_id' => [
               'required' => 'Employee ID is required'
          ]
     ];

     protected $skipValidation = false;
     protected $cleanValidationRules = true;

     /**
      * Get documents for a specific employee
      */
     public function getEmployeeDocuments($empId)
     {
          return $this->where('emp_id', $empId)
               ->orderBy('uploaded_at', 'DESC')
               ->findAll();
     }

     /**
      * Upload document for employee
      */
     public function uploadDocument($empId, $documentName, $documentPath)
     {
          $data = [
               'emp_id' => $empId,
               'document_name' => $documentName,
               'document_path' => $documentPath
          ];

          return $this->insert($data);
     }

     /**
      * Delete employee document
      */
     public function deleteDocument($docId)
     {
          return $this->delete($docId);
     }

     /**
      * Get document by ID
      */
     public function getDocumentById($docId)
     {
          return $this->find($docId);
     }

     /**
      * Get document count for employee
      */
     public function getDocumentCount($empId)
     {
          return $this->where('emp_id', $empId)->countAllResults();
     }
}
