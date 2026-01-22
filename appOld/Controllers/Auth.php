<?php

namespace App\Controllers;

class Auth extends BaseController
{
     /**
      * Validates JWT token from Authorization header for API use.
      * Use a unique method name to avoid conflict with BaseController::validate().
      *
      * @return \CodeIgniter\HTTP\Response|void
      */
     public function validateToken()
     {
          $result = $this->validateAuthorization();
          // If result is a CI response (error), return it
          if ($result instanceof \CodeIgniter\HTTP\Response) {
               return $result;
          }
          // Otherwise, token is valid
          return $this->response->setJSON([
               'status' => true,
               'message' => 'Token is valid',
               'data' => $result
          ]);
     }
}
