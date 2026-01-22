<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use App\Models\NewEmployeeMasterModel;
use App\Services\JwtService;

class Profile extends BaseController
{
     use ResponseTrait;
     protected $jwtService;
     protected $userModel;
     protected $newEmployeeMasterModel;

     public function __construct()
     {
          $this->jwtService = new JwtService();
          $this->userModel = new UserModel();
          $this->newEmployeeMasterModel = new NewEmployeeMasterModel();
     }

     public function user_profile()
     {
          $userDetails = $this->validateAuthorization();
          if (isset($userDetails['error'])) {
               return $this->fail($userDetails['error'], $userDetails['status']);
          }
          $emp_code = $userDetails['user_code'];

          $user = $this->newEmployeeMasterModel->getEmployeeByCode($emp_code);
          if (!$user) {
               return $this->failNotFound('User not found');
          }

          return $this->respond([
               'status' => 'success',
               'message' => 'Profile index endpoint',
               'data' => $user
          ]);
     }

     public function user_profile_new()
     {
          $userDetails = $this->validateAuthorization();
          if (isset($userDetails['error'])) {
               return $this->fail($userDetails['error'], $userDetails['status']);
          }
          $emp_code = $userDetails['user_code'];
          $role = $userDetails['role'];
          $user = $this->newEmployeeMasterModel->getEmployeeByCodeNew($emp_code, $role);
          if (!$user) {
               return $this->failNotFound('User not found');
          }
          return $this->respond([
               'status' => 'success',
               'message' => 'Profile index endpoint',
               'data' => $user
          ]);
     }
 
 
     public function index()
     {
          //log_message( 'info', 'Profile API called' );

          // Get the Authorization header
          $authorizationHeader = $this->request->getHeader('Authorization') ? $this->request->getHeader('Authorization')->getValue() : null;

          //log_message( 'info', 'Authorization header: ' . $authorizationHeader );

          // Create an instance of JwtService
          $jwtService = new JwtService();

          // Validate the token
          $result = $jwtService->validateToken($authorizationHeader);

          // Check if there is an error
          if (isset($result['error'])) {
               //log_message( 'error', $result[ 'error' ] );
               return $this->respond(['error' => $result['error']], $result['status']);
          }

          // Get user details from the database
          $userModel = new UserModel();
          $bmid = $result['data']->emp_code;
          log_message('error', 'Profile API called for ' . $bmid);
          // Ensure `getUserDetails()` returns an array or handle `$user` as an object
          $user = $userModel->getUserProfiles($bmid);

          log_message('error', 'user details ' . json_encode($user));
          if (!$user) {
               //log_message( 'error', 'User not found: ' . $bmid );
               return $this->respond(['error' => 'User not found'], 404);
          }

          // Ensure `$user` is handled as an array
          $user = (array) $user;
          // Convert $user to an array if needed

          // Remove password if present
          // unset( $user[ 'password' ] );

          //log_message( 'info', 'User profile returned successfully' );
          return $this->respond(['profile' => $user, 'status' => true], 200);
     }
}
