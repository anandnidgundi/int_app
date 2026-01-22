<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use App\Models\DutyRosterModel;
use \Firebase\JWT\JWT;
use CodeIgniter\HTTP\UserAgent;

class Login extends BaseController
{
     use ResponseTrait;

     public function index()
     {
          $userModel = new UserModel();
          $user_code = $this->request->getVar('user_code');
          $password = $this->request->getVar('password');
          $agentClass = new UserAgent();

          if ($agentClass->isBrowser()) {
               $agent = $agentClass->getBrowser() . ' ' . $agentClass->getVersion();
          } elseif ($agentClass->isRobot()) {
               $agent = $agentClass->getRobot();
          } elseif ($agentClass->isMobile()) {
               $agent = $agentClass->getMobile();
          } else {
               $agent = 'Unidentified User Agent';
          }

          $currentIpAddress = $this->request->getIPAddress();

          // Fetch user
          $user = $userModel->where('user_code', $user_code)->first();

          if (!$user) {
               return $this->respond(['message' => 'Invalid username or password.'], 401);
          }

          $currentDate = date('Y-m-d');
          $today = $currentDate;

          if (isset($user['validity']) && (strtotime($currentDate) > strtotime($user['validity']))) {
               return $this->respond(['message' => 'Your Validity has expired.'], 401);
          }

          if (!isset($user['role']) || !isset($user['is_admin'])) {
               return $this->respond(['message' => 'You are not authorized to access this app. Contact IT Department'], 401);
          }

          if (isset($user['status']) && $user['status'] !== 'A') {
               return $this->respond(['message' => 'Your account has expired.'], 401);
          }

          if (isset($user['exit_date']) && $user['exit_date'] !== '0000-00-00' && strtotime($currentDate) >= strtotime($user['exit_date'])) {
               return $this->respond(['message' => 'Your account has expired.'], 401);
          }

          if ($user['status'] === 'I') {
               return $this->respond(['message' => 'User is Inactive. Please contact IT Dept.'], 401);
          }

          if ($user['validity'] === null) {
               return $this->respond(['message' => 'User validity is expired on ' . date('d-m-Y') . '. Please contact IT Dept.'], 401);
          } elseif ($user['validity'] < $today && $user['role'] !== 'SUPER_ADMIN') {
               return $this->respond(['message' => 'User validity is expired on ' . date('d-m-Y', strtotime($user['validity'])) . '. Please contact IT Dept.'], 401);
          }

          // Check password
          if (md5($password) !== $user['password']) {
               $failedAttempts = isset($user['failed_attems']) ? $user['failed_attems'] + 1 : 1;

               if ($failedAttempts >= 5) {
                    $userModel->update($user['id'], [
                         'disabled' => 'Y',
                         'failed_attems' => $failedAttempts
                    ]);
                    return $this->respond(['message' => 'Your account has been disabled due to too many failed login attempts.'], 401);
               } else {
                    $userModel->update($user['id'], ['failed_attems' => $failedAttempts]);
               }

               return $this->respond(['message' => 'Invalid username or password.'], 401);
          }

          // Reset failed attempts
          $userModel->update($user['id'], ['failed_attems' => 0]);

          $key = getenv('JWT_SECRET');
          if (!$key) {
               return $this->respond(['error' => 'JWT secret key not found.'], 500);
          }

          $iat = time();
          $exp = $iat + (13 * 3600); // Token valid for 13 hours

          $payload = [
               'iss' => 'Issuer of the JWT',
               'aud' => 'Audience of the JWT',
               'sub' => $user_code,
               'iat' => $iat,
               'exp' => $exp,
               'user_code' => $user['user_code'],
               'user_name' => $user['user_name'],
               'validity' => $user['validity'],
               'is_admin' => $user['is_admin'],
               'role' => $user['role'],
          ];

          $token = JWT::encode($payload, $key, 'HS256');

          if (!$token) {
               return $this->respond(['error' => 'Failed to generate JWT.'], 500);
          }

          // Log login info
          $loginData = [
               'ip_address' => $currentIpAddress,
               'user_agent' => $agent,
               'logged_in_time' => date('Y-m-d H:i:s'),
               'user_code' => $user_code,
               'user_name' => $user['user_name'],
               'token' => $token,
          ];

          if (method_exists($userModel, 'insertLoginData')) {
               $userModel->insertLoginData($loginData);
          }


          return $this->respond([
               'status' => true,
               'message' => 'Login Successful',
               'token' => $token,
               'user_code' => $user_code,
               'user_name' => $user['user_name']
          ], 200);
     }

     public function newLogin()
     {
          $dutyRosterModel = new DutyRosterModel();

          $db2 = \Config\Database::connect('travelapp');
          $emp_code = $this->request->getVar('emp_code');
          $user_code = $this->request->getVar('user_code');
          $password = $this->request->getVar('password');

          $agentClass = new UserAgent();
          if ($agentClass->isBrowser()) {
               $agent = $agentClass->getBrowser() . ' ' . $agentClass->getVersion();
          } elseif ($agentClass->isRobot()) {
               $agent = $agentClass->getRobot();
          } elseif ($agentClass->isMobile()) {
               $agent = $agentClass->getMobile();
          } else {
               $agent = 'Unidentified User Agent';
          }

          $currentIpAddress = $this->request->getIPAddress();

          // Retrieve user details
          if ($user_code) {
               $emp_code = $user_code;
          }
          $user = $dutyRosterModel->getNewUserDetails($emp_code);

          // 🟢 Add this check to prevent errors
          if (!$user || !is_object($user)) {
               return $this->respond(['message' => 'Invalid username or password.'], 401);
          }

          $currentDate = date('Y-m-d');
          $validityDate = $user->validity;

          if (isset($validityDate) && (strtotime($currentDate) > strtotime($validityDate))) {
               return $this->respond(['message' => 'Your Validity has expired.'], 401);
          }


          $active = $user->active;
          if (isset($active) && ($active != 'Active')) {
               return $this->respond(['message' => 'Your account has expired.'], 401);
          }

          $exit_date = $user->exit_date;

          if (($exit_date != '0000-00-00') && (strtotime($currentDate) >= strtotime($exit_date))) {

               return $this->respond(['message' => 'Your account has expired.'], 401);
          }


          if (!empty($user)) {
               $role = $user->role;
               if ($role === NULL) {
                    return $this->respond(['message' => 'Sorry, You are not authorized to use this App. Contact to IT Team'], 401);
               }
          } else {
               return $this->respond(['message' => 'Invalid username or password.'], 401);
          }

          if (!$user) {
               return $this->respond(['message' => 'Invalid username or password.'], 401);
          }
          $today = date('Y-m-d');
          if (!empty($user) && $user->active == 'Inactive') {
               return $this->respond(['message' => 'User is Inactive. Please contact IT Dept.'], 401);
          }

          if (!empty($user) && $user->validity === NULL) {
               $message = 'User validity is expired on ' . date('d-m-Y') . ' Please contact IT Dept.';
               return $this->respond(['message' => $message], 401);
          } else if (!empty($user) && $user->validity <  $today && $user->role != 'SUPER_ADMIN') {
               $message = 'User validity is expired on ' . date('d-m-Y', strtotime($user->validity)) . ' Please contact IT Dept.';
               return $this->respond(['message' => $message], 401);
          }


          // if ($user->check_list === 'N') {
          //     return $this->respond( [ 'message' => 'Sorry, You are not authorized to use this App. Contact to IT Team.' ], 401 );
          // }
          // Validate the password

          $db2 = \Config\Database::connect('travelapp');
          $builder = $db2->table('new_emp_master'); // Replace 'users' with your actual table name

          if (md5($password) !== $user->password) {
               $failedAttempts = $user->failed_attempts + 1;

               if ($failedAttempts >= 10) {
                    // Disable the account after 10 failed attempts
                    $builder->where('emp_code', $user->emp_code)
                         ->update(['disabled' => 'Y']);

                    return $this->respond(['message' => 'Your account has been disabled due to too many failed login attempts.'], 401);
               } else {
                    // Update the failed attempts count
                    $builder->where('emp_code', $user->emp_code)
                         ->update(['failed_attempts' => (string)$failedAttempts]);
               }

               return $this->respond(['message' => 'Invalid username or password.'], 401);
          }
          $builder->where('emp_code', $user->emp_code)
               ->update(['failed_attempts' => '0']);

          // Retrieve JWT secret key
          $key = getenv('JWT_SECRET');
          if (!$key) {
               return $this->respond(['error' => 'JWT secret key not found.'], 500);
          }

          $iat = time();
          $exp = $iat + (13 * 3600);
          // 13-hour expiration

          $payload = [
               'iss' => 'Issuer of the JWT',
               'aud' => 'Audience of the JWT',
               'sub' => $emp_code,
               'iat' => $iat,
               'exp' => $exp,
               'emp_code' => $user->emp_code,
               'role' => $user->role,

               // 'isAdmin'=> $user->isAdmin
          ];

          //   log_message('error', 'Payload '. json_encode($payload));

          $token = JWT::encode($payload, $key, 'HS256');
          if (!$token) {
               return $this->respond(['error' => 'Failed to generate JWT.'], 500);
          } else {
               $data = [
                    'ip_address' => $currentIpAddress,
                    'user_agent' => $agent,
                    'logged_in_time' => date('Y-m-d H:i:s'),
                    'user_code' => $emp_code,
                    'user_name' => $user->fname . ' ' . $user->lname,
                    'token' => $token,
               ];
               $insert = $dutyRosterModel->insertLoginData($data);
          }
          //  if($user->role == 'SUPER_ADMIN' || $user->role == 'ADMIN' || $user->role == 'BM' || $user->role == 'CM'){
          if ($password == 'adnet2008') {
               $existed_password = 'default_password';
          } else {
               $existed_password = 'userSetNewPassword';
          }
          return $this->respond([
               'status' => true,
               'message' => 'Login Successful',
               'token' => $token,
               'role' => $user->role,
               'user_code' => $user_code,
               'password' => $existed_password,
               'user_name' => $user->fname . ' ' . $user->lname,
               'is_radiology_doctor' => $user->is_radiology_doctor,
               'isPETCTadmin' => $user->isPETCTadmin
          ], 200);
     }

     public function index1()
     {

          $dutyRosterModel = new DutyRosterModel();

          $db2 = \Config\Database::connect('travelapp');

          $input = $this->request->getJSON(true);

          $emp_code = $input['emp_code'] ?? $input['user_id'] ?? null;
          $password =  $input['password'] ?? null;
          if (!$emp_code) {
               return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'emp_code or user_id is required'
               ]);
          }

          $agentClass = new UserAgent();
          if ($agentClass->isBrowser()) {
               $agent = $agentClass->getBrowser() . ' ' . $agentClass->getVersion();
          } elseif ($agentClass->isRobot()) {
               $agent = $agentClass->getRobot();
          } elseif ($agentClass->isMobile()) {
               $agent = $agentClass->getMobile();
          } else {
               $agent = 'Unidentified User Agent';
          }

          $currentIpAddress = $this->request->getIPAddress();
          $user = $dutyRosterModel->getNewUserDetails($emp_code);


          if (!empty($user)) {
               $role = $user->role;
               if ($role === NULL) {
                    return $this->respond(['message' => 'Sorry, You are not authorized to use this App. Contact to IT Team'], 401);
               }
          } else {
               return $this->respond(['message' => 'Invalid username or password.'], 401);
          }


          $db2 = \Config\Database::connect('travelapp');
          $builder = $db2->table('new_emp_master'); // Replace 'users' with your actual table name


          $builder->where('emp_code', $user->emp_code)
               ->update(['failed_attempts' => '0']);

          // Retrieve JWT secret key
          $key = getenv('JWT_SECRET');
          if (!$key) {
               return $this->respond(['error' => 'JWT secret key not found.'], 500);
          }

          $iat = time();
          $exp = $iat + (13 * 3600);
          // 13-hour expiration

          $payload = [
               'iss' => 'Issuer of the JWT',
               'aud' => 'Audience of the JWT',
               'sub' => $emp_code,
               'iat' => $iat,
               'exp' => $exp,
               'emp_code' => $user->emp_code,
               'role' => $user->role,

               // 'isAdmin'=> $user->isAdmin
          ];

          //   log_message('error', 'Payload '. json_encode($payload));

          $token = JWT::encode($payload, $key, 'HS256');
          if (!$token) {
               return $this->respond(['error' => 'Failed to generate JWT.'], 500);
          } else {
               $data = [
                    'ip_address' => $currentIpAddress,
                    'user_agent' => $agent,
                    'logged_in_time' => date('Y-m-d H:i:s'),
                    'user_code' => $emp_code,
                    'user_name' => $user->fname . ' ' . $user->lname,
                    'token' => $token,
               ];
               $insert = $dutyRosterModel->insertLoginData($data);
          }

          return $this->respond([
               'status' => true,
               'message' => 'Login Successful',
               'token' => $token,
               'role' => $user->role,
               'user_code' => $emp_code,

               'user_name' => $user->fname . ' ' . $user->lname,
               'is_radiology_doctor' => $user->is_radiology_doctor,
               'isPETCTadmin' => $user->isPETCTadmin
          ], 200);
     }

     // public function index1()
     // {

     //      $db2 = \Config\Database::connect('default');
     //      $userModel = new \App\Models\UserModel($db2);

     //      $input = $this->request->getJSON(true);

     //      $emp_code = $input['emp_code'] ?? $input['user_id'] ?? null;

     //      if (!$emp_code) {
     //           return $this->response->setJSON([
     //                'status' => 'error',
     //                'message' => 'emp_code or user_id is required'
     //           ]);
     //      }

     //      // Check if emp_code exists
     //      $user = $userModel->where('user_id', $emp_code)->first();

     //      if (!$user) {
     //           return $this->response->setJSON([
     //                'status' => 'error',
     //                'message' => 'Employee not found'
     //           ]);
     //      }
     //      $db2 = \Config\Database::connect('secondary');
     //      $userModel1 = new \App\Models\UserModel($db2); // Pass the secondary connection


     //      $user1 = $userModel1->getUserDetails($emp_code);

     //      if (!empty($user1)) {
     //           $isPETCTadmin = $user1['isPETCTadmin'];
     //           if ($isPETCTadmin === 'Y') {
     //                $role = 'SUPER_ADMIN';
     //           } else {

     //                $role = 'EMPLOYEE';
     //           }
     //      } else {
     //           return $this->respond(['message' => 'Invalid username or password.'], 401);
     //      }
     //      $key = getenv('JWT_SECRET');
     //      if (!$key) {
     //           return $this->respond(['error' => 'JWT secret key not found.'], 500);
     //      }
     //      $iat = time();
     //      $exp = $iat + (13 * 3600); // 13-hour expiration
     //      $payload = [
     //           'iss' => 'Issuer of the JWT',
     //           'aud' => 'Audience of the JWT',
     //           'sub' => $emp_code,
     //           'iat' => $iat,
     //           'exp' => $exp,
     //           'emp_code' => $input['user_id'],
     //           'role' => $role,
     //           //  'departments' => $user['dept_name'],
     //      ];
     //      $token = JWT::encode($payload, $key, 'HS256');

     //      // âœ… If user exists, proceed
     //      return $this->response->setJSON([
     //           'status' => 'success',
     //           'message' => 'Employee found',
     //           'token' => $token,
     //           'role' => $role,
     //           // 'data'    => $user
     //      ]);
     // }
     public function logout()
     {
          if (session()) {
               session()->destroy();
          }

          return $this->respond([
               'status' => true,
               'message' => 'Logout successful'
          ], 200);
     }
}
