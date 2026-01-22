<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use App\Controllers\BaseController;
use CodeIgniter\I18n\Time;

class User extends BaseController
{
     use ResponseTrait;
     protected $userModel;

     public function __construct()
     {
          $this->userModel = new UserModel();
     }

     public function index()
     {
          $users = new UserModel;

          // Check if emp_code is provided in the request
          $empCode = $this->request->getVar('emp_code');

          if ($empCode) {
               // Fetch a single user by emp_code
               $user = $users->where('emp_code', $empCode)->first();

               if (!$user) {
                    return $this->respond(['error' => 'User not found'], 404);
               }

               return $this->respond(['user' => $user, 'STATUS' => true], 200);
          }

          // If emp_code is not provided, return all users
          return $this->respond(['users' => $users->findAll(), 'STATUS' => true], 200);
     }


     public function show($id)
     {
          // Fetch a single user by ID
          $user = $this->userModel->find($id);
          if (!$user) {
               return $this->respond([
                    'status' => false,
                    'message' => 'User not found',
                    'data' => null
               ], 404);
          }
          return $this->respond([
               'status' => true,
               'message' => 'User fetched successfully',
               'data' => $user
          ], 200);
     }
     public function create()
     {
          // Create a new user from JSON body
          $json = $this->request->getJSON(true);
          $data = [
               'doct_code'   => $json['doct_code'] ?? null,
               'doct_name'   => $json['doct_name'] ?? null,
               'password'    => isset($json['password']) ? md5($json['password']) : null,
               'status'      => $json['status'] ?? 'A',
               'disabled'    => $json['disabled'] ?? 'N',
               'validity'    => $json['validity'] ?? null,
               'failed_attems' => $json['failed_attems'] ?? 0,
               'is_admin'    => $json['is_admin'] ?? 'N',
               'exit_date'   => $json['exit_date'] ?? '0000-00-00',
               'role'        => $json['role'] ?? '',
          ];
          if ($this->userModel->insert($data)) {
               $createdUser = $this->userModel->where('doct_code', $data['doct_code'])->first();
               return $this->respond([
                    'status' => true,
                    'message' => 'User created successfully',
                    'data' => $createdUser
               ], 201);
          }
          return $this->failValidationErrors($this->userModel->errors());
     }
     public function update($id)
     {
          // Update an existing user from JSON body
          $json = $this->request->getJSON(true);
          $data = [
               'doct_code'   => $json['doct_code'] ?? null,
               'doct_name'   => $json['doct_name'] ?? null,
               'password'    => isset($json['password']) ? md5($json['password']) : null,
               'status'      => $json['status'] ?? 'A',
               'disabled'    => $json['disabled'] ?? 'N',
               'validity'    => $json['validity'] ?? null,
               'failed_attems' => $json['failed_attems'] ?? 0,
               'is_admin'    => $json['is_admin'] ?? 'N',
               'exit_date'   => $json['exit_date'] ?? '0000-00-00',
               'role'        => $json['role'] ?? '',
          ];
          if ($this->userModel->update($id, $data)) {
               $updatedUser = $this->userModel->find($id);
               return $this->respond([
                    'status' => true,
                    'message' => 'User updated successfully',
                    'data' => $updatedUser
               ], 200);
          }
          return $this->failValidationErrors($this->userModel->errors());
     }
     public function delete($id)
     {
          // Delete a user
          if ($this->userModel->delete($id)) {
               return $this->respondDeleted(['message' => 'User deleted successfully']);
          }
          return $this->failNotFound('User not found');
     }



     public function getUserModes()
     {
          $auth = $this->validateAuthorization();

          $user_code = $auth['user_code'] ?? '';
          $user_name = $auth['user_name'] ?? '';

          // $user = $userDetails->emp_code;

          $userModel = new UserModel();
          $user = $userModel->getUserModes();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Modes not found'], 404);
          }
     }


     public function createUserMode()
     {

          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $mode_type = $request['mode_type'] ?? [];

          $data = [
               "mode_type" => $mode_type,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserMode($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Mode not saved'], 500);
          }
     }


     public function getUserModeById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserModeById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Mode not Found'], 500);
          }
     }



     public function updateUserModeById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $mode_type = $request['mode_type'] ?? [];
          $data = [
               'mode_type' => $mode_type,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserModeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Mode not Updated'], 500);
          }
     }

     public function deleteUserModeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserModeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Mode Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Mode not Deleted'], 500);
          }
     }



     public function getUserDesign()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserDesign();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Design not found'], 404);
          }
     }


     public function createUserDesign()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $designation_type = $request['designation'] ?? [];

          $data = [
               "designation_type" => $designation_type,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserDesign($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Design not saved'], 500);
          }
     }


     public function getUserDesignById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserDesignById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Design not Found'], 500);
          }
     }



     public function updateUserDesignById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $designation_type = $request['designation'] ?? [];
          $data = [
               'designation_type' => $designation_type,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserDesignById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Design not Updated'], 500);
          }
     }

     public function deleteUserDesignModeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserDesignModeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Design Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Design not Deleted'], 500);
          }
     }


     public function getUserPosit()
     {
          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserPosit();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Position not found'], 404);
          }
     }


     public function createUserPosit()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $position_type = $request['position'] ?? [];

          $data = [
               "position_type" => $position_type,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserPosit($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Position not saved'], 500);
          }
     }


     public function getUserPositById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserPositById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Position not Found'], 500);
          }
     }



     public function updateUserPositById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $position_type = $request['position'] ?? [];
          $data = [
               'position_type' => $position_type,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserPositById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Position not Updated'], 500);
          }
     }

     public function deleteUserPositModeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserPositModeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Position Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Position not Deleted'], 500);
          }
     }


     public function getUserCalen()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserCalen();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Calender not found'], 404);
          }
     }


     public function createUserCalen()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $calendar_type = $request['calendar_type'] ?? [];

          $data = [
               "calendar_type" => $calendar_type,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserCalen($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Calender not saved'], 500);
          }
     }


     public function getUserCalenById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserCalenById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Calender not Found'], 500);
          }
     }



     public function updateUserCalenById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $calendar_type = $request['calendar_type'] ?? [];
          $data = [
               'calendar_type' => $calendar_type,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserCalenById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Calender not Updated'], 500);
          }
     }

     public function deleteUserCalenModeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserCalenModeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Calender Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Calender not Deleted'], 500);
          }
     }


     public function getUserDept()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserDept();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Sub Dept not found'], 404);
          }
     }


     public function createUserDept()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $department_name = $request['department_name'] ?? [];

          $data = [
               "department_name" => $department_name,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserDept($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Sub Dept not saved'], 500);
          }
     }


     public function getUserDeptById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserDeptById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Sub Dept not Found'], 500);
          }
     }



     public function updateUserDeptById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $department_name = $request['department_name'] ?? [];
          $data = [
               'department_name' => $department_name,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserDeptById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Sub Dept not Updated'], 500);
          }
     }

     public function deleteUserDeptModeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserDeptModeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Sub Dept Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Sub Dept not Deleted'], 500);
          }
     }

     public function getUserSubDept()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserSubDept();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Sub Dept not found'], 404);
          }
     }


     public function createUserSubDept()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $subdepartment_name = $request['subdepartment_name'] ?? [];

          $data = [
               "subdepartment_name" => $subdepartment_name,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserSubDept($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Sub Dept not saved'], 500);
          }
     }


     public function getUserSubDeptById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserSubDeptById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Sub Dept not Found'], 500);
          }
     }



     public function updateUserSubDeptById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $subdepartment_name = $request['subdepartment_name'] ?? [];
          $data = [
               'subdepartment_name' => $subdepartment_name,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserSubDeptById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Sub Dept not Updated'], 500);
          }
     }

     public function deleteUserSubDeptModeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserSubDeptModeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Sub Dept Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Sub Dept not Deleted'], 500);
          }
     }


     public function getUserPayGroup()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserPayGroup();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Pay Group not found'], 404);
          }
     }


     public function createUserPayGroup()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $paygroup = $request['paygroup'] ?? [];

          $data = [
               "paygroup" => $paygroup,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserPayGroup($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Pay Group not saved'], 500);
          }
     }


     public function getUserPayGroupById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserPayGroupById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Pay Group not Found'], 500);
          }
     }



     public function updateUserPayGroupById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $paygroup = $request['paygroup'] ?? [];
          $data = [
               'paygroup' => $paygroup,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserPayGroupById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Pay Group not Updated'], 500);
          }
     }

     public function deleteUserPayGroupModeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserPayGroupModeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Pay Group Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Pay Group not Deleted'], 500);
          }
     }


     public function getRegion()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getRegion();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Region not found'], 404);
          }
     }

     public function createRegion()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $region = $request['region'] ?? [];

          $data = [
               "region" => $region,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createRegion($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Region not saved'], 500);
          }
     }


     public function getRegionId($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getRegionId($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Region not Found'], 500);
          }
     }



     public function updateRegionId($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $region = $request['region'] ?? [];
          $data = [
               'region' => $region,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateRegionId($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Region not Updated'], 500);
          }
     }


     public function updateCandidateRegionId($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $region = $request['region_name'] ?? [];
          $data = [
               'region_name' => $region
          ];
          $user = $userModel->updateCandidateRegionId($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Region not Updated'], 500);
          }
     }

     public function deleteRegionId($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteRegionId($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Region Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Region not Deleted'], 500);
          }
     }




     public function getUserCurrency()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserCurrency();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Currency not found'], 404);
          }
     }


     public function createUserCurrency()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $currency = $request['currency'] ?? [];

          $data = [
               "currency" => $currency,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserCurrency($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Currency not saved'], 500);
          }
     }


     public function getUserCurrencyById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserCurrencyById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Currency not Found'], 500);
          }
     }



     public function updateUserCurrencyById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $currency = $request['currency'] ?? [];
          $data = [
               'currency' => $currency,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserCurrencyById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Currency not Updated'], 500);
          }
     }

     public function deleteUserCurrencyModeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserCurrencyModeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Currency Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Currency not Deleted'], 500);
          }
     }


     public function getUserJobProfile()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserJobProfile();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Job Profile not found'], 404);
          }
     }


     public function createUserJobProfile()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $jobprofile = $request['jobprofile'] ?? [];

          $data = [
               "jobprofile" => $jobprofile,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserJobProfile($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Job Profile not saved'], 500);
          }
     }


     public function getUserJobProfileById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserJobProfileById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Job Profile not Found'], 500);
          }
     }



     public function updateUserJobProfileById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $jobprofile = $request['jobprofile'] ?? [];
          $data = [
               'jobprofile' => $jobprofile,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserJobProfileById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Job Profile not Updated'], 500);
          }
     }

     public function deleteUserJobProfileById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserJobProfileById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Job Profile Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Job Profile not Deleted'], 500);
          }
     }


     public function getUserPaymentType()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserPaymentType();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Payment not found'], 404);
          }
     }


     public function createUserPaymentType()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $paymenttype = $request['paymenttype'] ?? [];

          $data = [
               "paymenttype" => $paymenttype,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserPaymentType($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Payment not saved'], 500);
          }
     }


     public function getUserPaymentTypeById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserPaymentTypeById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Payment not Found'], 500);
          }
     }



     public function updateUserPaymentTypeById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $paymenttype = $request['paymenttype'] ?? [];
          $data = [
               'paymenttype' => $paymenttype,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserPaymentTypeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Payment not Updated'], 500);
          }
     }

     public function deleteUserPaymentTypeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserPaymentTypeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Payment Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Payment not Deleted'], 500);
          }
     }


     public function getUserBank()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserBank();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Bank not found'], 404);
          }
     }


     public function createUserBank()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $bank = $request['bank'] ?? [];

          $data = [
               "bank" => $bank,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserBank($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Bank not saved'], 500);
          }
     }


     public function getUserBankById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserBankById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Bank not Found'], 500);
          }
     }



     public function updateUserBankById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $bank = $request['bank'] ?? [];
          $data = [
               'bank' => $bank,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserBankById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Bank not Updated'], 500);
          }
     }

     public function deleteUserBankById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserBankById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Bank Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Bank not Deleted'], 500);
          }
     }



     public function getUserCenter()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserCenter();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Center not found'], 404);
          }
     }


     public function createUserCenter()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $center = $request['center'] ?? [];

          $data = [
               "center" => $center,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserCenter($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Center not saved'], 500);
          }
     }


     public function getUserCenterById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserCenterById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Center not Found'], 500);
          }
     }



     public function updateUserCenterById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $center = $request['center'] ?? [];
          $data = [
               'center' => $center,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserCenterById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Center not Updated'], 500);
          }
     }

     public function deleteUserCenterById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserCenterById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Center Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Center not Deleted'], 500);
          }
     }



     public function getUserWorkType()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserWorkType();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Work type not found'], 404);
          }
     }


     public function createUserWorkType()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $worktype = $request['worktype'] ?? [];

          $data = [
               "worktype" => $worktype,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserWorkType($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Work type not saved'], 500);
          }
     }


     public function getUserWorkTypeById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserWorkTypeById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Work type not Found'], 500);
          }
     }



     public function updateUserWorkTypeById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $worktype = $request['worktype'] ?? [];
          $data = [
               'worktype' => $worktype,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserWorkTypeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Work type not Updated'], 500);
          }
     }

     public function deleteUserWorkTypeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserWorkTypeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Work type Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Work type not Deleted'], 500);
          }
     }


     public function getUserMedRegCouncil()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserMedRegCouncil();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Medical Council not found'], 404);
          }
     }


     public function createUserMedRegCouncil()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $medicalregistrationcouncil = $request['medicalregistrationcouncil'] ?? [];

          $data = [
               "medicalregistrationcouncil" => $medicalregistrationcouncil,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserMedRegCouncil($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Medical Council not saved'], 500);
          }
     }


     public function getUserMedRegCouncilById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserMedRegCouncilById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Medical Council not Found'], 500);
          }
     }



     public function updateUserMedRegCouncilById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $medicalregistrationcouncil = $request['medicalregistrationcouncil'] ?? [];
          $data = [
               'medicalregistrationcouncil' => $medicalregistrationcouncil,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserMedRegCouncilById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Medical Council not Updated'], 500);
          }
     }

     public function deleteUserMedRegCouncilById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserMedRegCouncilById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Medical Council Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Medical Council not Deleted'], 500);
          }
     }


     public function getUserQualification()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserQualification();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Qualification not found'], 404);
          }
     }


     public function createUserQualification()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $qualification = $request['qualification'] ?? [];

          $data = [
               "qualification" => $qualification,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserQualification($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Qualification not saved'], 500);
          }
     }


     public function getUserQualificationById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserQualificationById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Qualification not Found'], 500);
          }
     }



     public function updateUserQualificationById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $qualification = $request['qualification'] ?? [];
          $data = [
               'qualification' => $qualification,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserQualificationById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Qualification not Updated'], 500);
          }
     }

     public function deleteUserQualificationById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserQualificationById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Qualification Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Qualification not Deleted'], 500);
          }
     }


     public function getUserSpecialization()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserSpecialization();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Specialization not found'], 404);
          }
     }


     public function createUserSpecialization()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $specialization = $request['specialization'] ?? [];

          $data = [
               "specialization" => $specialization,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserSpecialization($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Specialization not saved'], 500);
          }
     }


     public function getUserSpecializationById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserSpecializationById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Specialization not Found'], 500);
          }
     }



     public function updateUserSpecializationById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $specialization = $request['specialization'] ?? [];
          $data = [
               'specialization' => $specialization,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserSpecializationById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Specialization not Updated'], 500);
          }
     }

     public function deleteUserSpecializationById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserSpecializationById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Specialization Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Specialization not Deleted'], 500);
          }
     }



     public function getUserEarningName()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserEarningName();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Earning not found'], 404);
          }
     }


     public function createUserEarningName()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $earningname = $request['earningname'] ?? [];

          $data = [
               "earningname" => $earningname,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserEarningName($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Earning not saved'], 500);
          }
     }


     public function getUserEarningNameById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserEarningNameById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Earning not Found'], 500);
          }
     }



     public function updateUserEarningNameById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $earningname = $request['earningname'] ?? [];
          $data = [
               'earningname' => $earningname,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserEarningNameById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Earning not Updated'], 500);
          }
     }

     public function deleteUserEarningNameById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserEarningNameById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Earning Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Earning not Deleted'], 500);
          }
     }


     public function getUserAccount()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserAccount();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Account not found'], 404);
          }
     }


     public function createUserAccount()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $account = $request['account'] ?? [];

          $data = [
               "account" => $account,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserAccount($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Account not saved'], 500);
          }
     }


     public function getUserAccountById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserAccountById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Account not Found'], 500);
          }
     }



     public function updateUserAccountById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $account = $request['account'] ?? [];
          $data = [
               'account' => $account,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserAccountById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Account not Updated'], 500);
          }
     }

     public function deleteUserAccountById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserAccountById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Account Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Account not Deleted'], 500);
          }
     }


     public function getUserDeductionName()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserDeductionName();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Deduction not found'], 404);
          }
     }


     public function createUserDeductionName()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $deductionname = $request['deductionname'] ?? [];

          $data = [
               "deductionname" => $deductionname,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserDeductionName($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Deduction not saved'], 500);
          }
     }


     public function getUserDeductionById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserDeductionById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Deduction not Found'], 500);
          }
     }



     public function updateUserDeductionById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $deductionname = $request['deductionname'] ?? [];
          $data = [
               'deductionname' => $deductionname,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserDeductionById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Deduction not Updated'], 500);
          }
     }

     public function deleteUserDeductionById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserDeductionById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Deduction Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Deduction not Deleted'], 500);
          }
     }


     public function getUserLoanType()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserLoanType();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Loan Type not found'], 404);
          }
     }


     public function createUserLoanType()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $loantype = $request['loantype'] ?? [];

          $data = [
               "loantype" => $loantype,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserLoanType($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Loan Type not saved'], 500);
          }
     }


     public function getUserLoanTypeById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserLoanTypeById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Loan Type not Found'], 500);
          }
     }



     public function updateUserLoanTypeById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $loantype = $request['loantype'] ?? [];
          $data = [
               'loantype' => $loantype,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserLoanTypeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Loan Type not Updated'], 500);
          }
     }

     public function deleteUserLoanTypeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserLoanTypeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Loan Type Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Loan Type not Deleted'], 500);
          }
     }


     public function getUserLeaveTemplate()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserLeaveTemplate();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Leave Template not found'], 404);
          }
     }


     public function createUserLeaveTemplate()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $leavetemplate = $request['leavetemplate'] ?? [];

          $data = [
               "leavetemplate" => $leavetemplate,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserLeaveTemplate($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Leave Template not saved'], 500);
          }
     }


     public function getUserLeaveTemplateById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserLeaveTemplateById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Leave Template not Found'], 500);
          }
     }



     public function updateUserLeaveTemplateById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $leavetemplate = $request['leavetemplate'] ?? [];
          $data = [
               'leavetemplate' => $leavetemplate,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserLeaveTemplateById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Leave Template not Updated'], 500);
          }
     }

     public function deleteUserLeaveTemplateById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserLeaveTemplateById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Leave Template Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Leave Template not Deleted'], 500);
          }
     }


     public function getUserAirTicketTemplate()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserAirTicketTemplate();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Air ticket Template not found'], 404);
          }
     }


     public function createUserAirTicketTemplate()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $airtickettemplate = $request['airtickettemplate'] ?? [];

          $data = [
               "airtickettemplate" => $airtickettemplate,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserAirTicketTemplate($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Air ticket Template not saved'], 500);
          }
     }


     public function getUserAirTicketTemplateById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserAirTicketTemplateById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Air ticket Template not Found'], 500);
          }
     }



     public function updateUserAirTicketTemplateById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $airtickettemplate = $request['airtickettemplate'] ?? [];
          $data = [
               'airtickettemplate' => $airtickettemplate,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserAirTicketTemplateById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Air ticket Template not Updated'], 500);
          }
     }

     public function deleteUserAirTicketTemplateById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserAirTicketTemplateById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Calender Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Air ticket Template not Deleted'], 500);
          }
     }


     public function getUserReasonForLeaving()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserReasonForLeaving();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Reason for Leaving not found'], 404);
          }
     }


     public function createUserReasonForLeaving()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $reasonforleaving = $request['reasonforleaving'] ?? [];

          $data = [
               "reasonforleaving" => $reasonforleaving,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserReasonForLeaving($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Reason for Leaving not saved'], 500);
          }
     }


     public function getUserReasonForLeavingById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserReasonForLeavingById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Reason for Leaving not Found'], 500);
          }
     }



     public function updateUserReasonForLeavingById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $reasonforleaving = $request['reasonforleaving'] ?? [];
          $data = [
               'reasonforleaving' => $reasonforleaving,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserReasonForLeavingById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Reason for Leaving not Updated'], 500);
          }
     }

     public function deleteUserReasonForLeavingById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserReasonForLeavingById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User Reason for Leaving Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Reason for Leaving not Deleted'], 500);
          }
     }


     public function getUserState()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserState();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User State not found'], 404);
          }
     }


     public function getUsers()
     {
          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUsers();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User State not found'], 404);
          }
     }


     public function createUserState()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $state = $request['state'] ?? [];

          $data = [
               "state" => $state,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createUserState($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User State not saved'], 500);
          }
     }

     public function resetUserById($id)
     {
          $users = new UserModel();

          // Hash the new password using MD5
          $newPassword = 'adnet2008'; // You should replace this with a secure password input
          $hashedPassword = md5($newPassword);

          // Prepare data for update
          $updateData = [
               'password' => $hashedPassword,
               // Include any other fields you need to update
          ];
          $validityDate = new \DateTime(); // Current date
          $validityDate->modify('+90 days'); // Add 90 days to the current date
          $newValidity = $validityDate->format('Y-m-d');

          $updateValidityData = [
               'validity' => $newValidity,
               // Include any other fields you need to update
          ];

          $updateDisablityData = [
               'disabled' => 'N',
               // Include any other fields you need to update
          ];

          // Update the database entry
          $updated = $users->update_password($id, $updateData);
          $users->update_user_validity($id, $updateValidityData);
          // $users->update_disability($emp_code, $updateDisablityData);

          if ($updated) {
               return $this->respond(['status' => true, 'message' => 'Password updated successfully'], 200);
          } else {
               return $this->respond(['error' => 'Failed to update password'], 500);
          }
     }


     public function getUserStateById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getUserStateById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User State not Found'], 500);
          }
     }



     public function updateUserStateById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $state = $request['state'] ?? [];
          $data = [
               'state' => $state,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateUserStateById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User State not Updated'], 500);
          }
     }

     public function deleteUserStateById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteUserStateById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'User State Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User State not Deleted'], 500);
          }
     }



     public function getSbu()
     {
          //$userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getSbu();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sbu not found'], 404);
          }
     }


     public function createSbu()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $sbu = $request['sbu'] ?? [];

          $data = [
               "sbu" => $sbu,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createSbu($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sbu not saved'], 500);
          }
     }


     public function getSbuById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getSbuById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sbu not Found'], 500);
          }
     }



     public function updateSbuById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $sbu = $request['sbu'] ?? [];
          $data = [
               'sbu' => $sbu,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateSbuById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sbu not Updated'], 500);
          }
     }

     public function deleteSbuById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteSbuById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Sbu Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sbu not Deleted'], 500);
          }
     }


     public function getOrgan()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getOrgan();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Organization not found'], 404);
          }
     }


     public function createOrgan()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $organization = $request['organization'] ?? [];

          $data = [
               "organization" => $organization,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createOrgan($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Organization not saved'], 500);
          }
     }


     public function getOrganById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getOrganById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Organization not Found'], 500);
          }
     }



     public function updateOrganById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $organization = $request['organization'] ?? [];
          $data = [
               'organization' => $organization,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateOrganById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Organization not Updated'], 500);
          }
     }

     public function deleteOrganById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteOrganById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Organization Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Organization not Deleted'], 500);
          }
     }

     public function getCity()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getCity();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'City not found'], 404);
          }
     }


     public function createCity()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $city = $request['city'] ?? [];

          $data = [
               "city" => $city,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createCity($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'City not saved'], 500);
          }
     }


     public function getCityById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getCityById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'City not Found'], 500);
          }
     }



     public function updateCityById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $city = $request['city'] ?? [];
          $data = [
               'city' => $city,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateCityById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'City not Updated'], 500);
          }
     }

     public function deleteCityById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteCityById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'City Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'City not Deleted'], 500);
          }
     }



     public function getCluster()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getCluster();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Cluster not found'], 404);
          }
     }


     public function createCluster()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $cluster = $request['cluster'] ?? [];

          $data = [
               "cluster" => $cluster,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createCluster($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Cluster not saved'], 500);
          }
     }


     public function getClusterById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getClusterById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Cluster not Found'], 500);
          }
     }



     public function updateClusterById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $cluster = $request['cluster'] ?? [];
          $data = [
               'cluster' => $cluster,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateClusterById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Cluster not Updated'], 500);
          }
     }

     public function deleteClusterById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteClusterById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Cluster Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Cluster not Deleted'], 500);
          }
     }


     public function getLocation()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getLocation();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Location not found'], 404);
          }
     }


     public function createLocation()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $location = $request['location'] ?? [];

          $data = [
               "location" => $location,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createLocation($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Location not saved'], 500);
          }
     }


     public function getLocationById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getLocationById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Location not Found'], 500);
          }
     }



     public function updateLocationById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $location = $request['location'] ?? [];
          $data = [
               'location' => $location,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateLocationById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Location not Updated'], 500);
          }
     }

     public function deleteLocationById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteLocationById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Cluster Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Cluster not Deleted'], 500);
          }
     }


     public function getDeptCategory()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getDeptCategory();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Cluster not found'], 404);
          }
     }


     public function createDeptCategory()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $dept_category = $request['deptcategory'] ?? [];

          $data = [
               "dept_category" => $dept_category,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createDeptCategory($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Dept Category not saved'], 500);
          }
     }


     public function getDeptCategoryById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getDeptCategoryById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Dept Category not Found'], 500);
          }
     }



     public function updateDeptCategoryById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $dept_category = $request['dept_category'] ?? [];
          $data = [
               'dept_category' => $dept_category,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateDeptCategoryById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Dept Category not Updated'], 500);
          }
     }

     public function deleteDeptCategoryById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteDeptCategoryById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Dept Category Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Dept Category not Deleted'], 500);
          }
     }


     public function getMainDept()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getMainDept();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Main Dept not found'], 404);
          }
     }


     public function createMainDept()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $main_dept = $request['main_dept'] ?? [];

          $data = [
               "main_dept" => $main_dept,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createMainDept($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Main Dept not saved'], 500);
          }
     }


     public function getMainDeptById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getMainDeptById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Main Dept not Found'], 500);
          }
     }



     public function updateMainDeptById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $main_dept = $request['main_dept'] ?? [];
          $data = [
               'main_dept' => $main_dept,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateMainDeptById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Main Dept not Updated'], 500);
          }
     }

     public function deleteMainDeptById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteMainDeptById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Main Dept Created Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Main Dept not Deleted'], 500);
          }
     }


     public function getSubDept()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getSubDept();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Dept not found'], 404);
          }
     }


     public function createSubDept()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $sub_dept = $request['sub_dept'] ?? [];

          $data = [
               "sub_dept" => $sub_dept,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createSubDept($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Dept not saved'], 500);
          }
     }


     public function getSubDeptById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getSubDeptById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Dept not Found'], 500);
          }
     }



     public function updateSubDeptById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $sub_dept = $request['sub_dept'] ?? [];
          $data = [
               'sub_dept' => $sub_dept,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateSubDeptById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Dept not Updated'], 500);
          }
     }

     public function deleteSubDeptById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteSubDeptById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Sub Dept Created Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Dept not Deleted'], 500);
          }
     }



     public function getBranch()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getBranch();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Branch not found'], 404);
          }
     }


     public function createBranch()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $branch = $request['branch'] ?? [];

          $data = [
               "branch" => $branch,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createBranch($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Branch not saved'], 500);
          }
     }


     public function getBranchById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getBranchById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Branch not Found'], 500);
          }
     }



     public function updateBranchById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $branch = $request['branch'] ?? [];
          $data = [
               'branch' => $branch,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateBranchById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Branch not Updated'], 500);
          }
     }

     public function deleteBranchById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteBranchById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Branch Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Branch not Deleted'], 500);
          }
     }

     public function getGrade()
     {
          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getGrade();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Grade not found'], 404);
          }
     }


     public function createGrade()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $grade = $request['grade'] ?? [];

          $data = [
               "grade" => $grade,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createGrade($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Grade not saved'], 500);
          }
     }


     public function getGradeById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getGradeById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Grade not Found'], 500);
          }
     }



     public function updateGradeById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $grade = $request['grade'] ?? [];
          $data = [
               'grade' => $grade,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateGradeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Grade not Updated'], 500);
          }
     }

     public function deleteGradeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteGradeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Grade Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Grade not Deleted'], 500);
          }
     }


     public function getShiftRoster()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getShiftRoster();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Shift Roster not found'], 404);
          }
     }


     public function createShiftRoster()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array

          $shift_roster = $request['shift_roster'] ?? [];
          $shift_in = $request['shift_in'] ?? [];
          $shift_out = $request['shift_out'] ?? [];
          $total_hours = $request['total_hours'] ?? [];

          $data = [
               "shift_roster" => $shift_roster,
               "shift_in" => $shift_in,
               "shift_out" => $shift_out,
               "total_hours" => $total_hours,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createShiftRoster($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user . "id shift added successfully"], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Shift Roaster not saved'], 500);
          }
     }


     public function getShiftRosterById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getShiftRosterById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Shift Roster not Found'], 500);
          }
     }



     public function updateShiftRosterById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $shift_roster = $request['shift_roster'] ?? [];
          $shift_in = $request['shift_in'] ?? [];
          $shift_out = $request['shift_out'] ?? [];
          $total_hours = $request['total_hours'] ?? [];
          $data = [
               'shift_roster' => $shift_roster,
               'shift_in' => $shift_in,
               'shift_out' => $shift_out,
               'total_hours' => $total_hours,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateShiftRosterById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Shift Roster not Updated'], 500);
          }
     }

     public function deleteShiftRosterById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteShiftRosterById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Shift Roster Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Shift Roster not Deleted'], 500);
          }
     }



     public function getReligion()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getReligion();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Religion Roaster not found'], 404);
          }
     }


     public function createReligion()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $religion = $request['religion'] ?? [];

          $data = [
               "religion" => $religion,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createReligion($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Religion Roaster not saved'], 500);
          }
     }


     public function getReligionById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getReligionById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Religion not Found'], 500);
          }
     }



     public function updateReligionById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $religion = $request['religion'] ?? [];
          $data = [
               'religion' => $religion,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateReligionById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Religion not Updated'], 500);
          }
     }

     public function deleteReligionById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteReligionById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Religion Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Religion  not Deleted'], 500);
          }
     }



     public function getCaste()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getCaste();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Caste not found'], 404);
          }
     }


     public function createCaste()
     {
          // $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $caste = $request['caste'] ?? [];

          $data = [
               "caste" => $caste,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createCaste($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Caste not saved'], 500);
          }
     }


     public function getCasteById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getCasteById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Caste not Found'], 500);
          }
     }



     public function updateCasteById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $caste = $request['caste'] ?? [];
          $data = [
               'caste' => $caste,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateCasteById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Caste not Updated'], 500);
          }
     }

     public function deleteCasteById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteCasteById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Caste Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Caste  not Deleted'], 500);
          }
     }



     public function getDegree()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getDegree();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Degree not found'], 404);
          }
     }


     public function createDegree()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $degrees = $request['degrees'] ?? [];

          $data = [
               "degrees" => $degrees,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createDegree($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Degree not saved'], 500);
          }
     }


     public function getDegreeById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getDegreeById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Degree not Found'], 500);
          }
     }



     public function updateDegreeById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $degrees = $request['degrees'] ?? [];
          $data = [
               'degrees' => $degrees,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateDegreeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Degree not Updated'], 500);
          }
     }

     public function deleteDegreeById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteDegreeById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Degree Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Degree  not Deleted'], 500);
          }
     }


     public function getBloodGroup()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getBloodGroup();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Blood Group not found'], 404);
          }
     }


     public function createBloodGroup()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $bloodgroup = $request['bloodGroup'] ?? [];

          $data = [
               "bloodgroup" => $bloodgroup,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createBloodGroup($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'BloodGroup not saved'], 500);
          }
     }


     public function getBloodGroupById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getBloodGroupById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Blood Group not Found'], 500);
          }
     }



     public function updateBloodGroupById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $bloodgroup = $request['bloodGroup'] ?? [];
          $data = [
               'bloodgroup' => $bloodgroup,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateBloodGroupById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Blood Group not Updated'], 500);
          }
     }

     public function deleteBloodGroupById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteBloodGroupById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Blood Group Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Blood Group  not Deleted'], 500);
          }
     }


     public function getHolidays()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getHolidays();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Holidays  not found'], 404);
          }
     }




     public function createHoliday()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          $request = $this->request->getJSON(true); // Get as associative array
          $holiday = $request['holiday'] ?? [];
          $date = $request['date'] ?? [];

          $data = [
               "holiday" => $holiday,
               "date" => $date,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createHoliday($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Holiday not saved'], 500);
          }
     }


     public function getHolidayById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getHolidayById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Holiday not Found'], 500);
          }
     }



     public function updateHolidayById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $holiday = $request['holiday'] ?? [];
          $date = $request['date'] ?? [];
          $data = [
               "holiday" => $holiday,
               "date" => $date,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateHolidayById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Holiday not Updated'], 500);
          }
     }

     public function deleteHolidayById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteHolidayById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Holiday Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Holiday  not Deleted'], 500);
          }
     }



     public function getRadiologyPersonalityAssessment()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getRadiologyPersonalityAssessment();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Personality Assessment  not found'], 404);
          }
     }








     public function createRadiologyPersonalityAssessment()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          $request = $this->request->getJSON(true);

          $candidate_id = $request['candidate_id'] ?? null;
          $assessment_date = $request['assessment_date'] ?? null;
          $criteria = $request['criteria'] ?? [];
          $rating = $request['rating'] ?? [];
          $remarks = $request['remarks'] ?? [];

          // Validation
          if (
               !$candidate_id || !$assessment_date ||
               count($criteria) !== count($rating) || count($rating) !== count($remarks)
          ) {
               return $this->respond(['status' => false, 'message' => 'Invalid or mismatched data'], 400);
          }

          $now = Time::now('Asia/Kolkata', 'en_US')->toDateTimeString();
          $userModel = new UserModel();

          foreach ($criteria as $i => $crit) {
               $row = [
                    'candidate_id'    => $candidate_id,
                    'assessment_date' => $assessment_date,
                    'criteria'        => $crit,
                    'rating'          => $rating[$i],
                    'remarks'         => $remarks[$i],
                    'created_by'      => $user,
                    'created_on'      => $now,
                    'modified_on'     => $now,
               ];

               $inserted = $userModel->createRadiologyPersonalityAssessment($row);
               if (!$inserted) {
                    return $this->respond(['status' => false, 'message' => 'Failed to save one of the assessments'], 500);
               }
          }

          return $this->respond(['status' => true, 'message' => 'Assessment saved successfully'], 200);
     }






     public function getRadiologyPersonalityAssessmentById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getRadiologyPersonalityAssessmentById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Personality Assessment not Found'], 500);
          }
     }



     public function updateRadiologyPersonalityAssessmentById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $assessment_date = $request['assessment_date'] ?? [];
          $criteria = $request['criteria'] ?? [];
          $rating = $request['rating'] ?? [];
          $remarks = $request['remarks'] ?? [];

          $data = [
               "assessment_date" => $assessment_date,
               "criteria" => $criteria,
               "rating" => $rating,
               "remarks" => $remarks,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateRadiologyPersonalityAssessmentById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Personality Assessment not Updated'], 500);
          }
     }



     public function updateCandidatePersonalityId($candidateId)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // associative array

          // Extract personality assessments array from the payload
          $personality_assessments = $request['personality_assessments'] ?? [];

          $success = true;

          foreach ($personality_assessments as $assessment) {
               $id = $assessment['id'] ?? null;
               if (!$id) {
                    // skip if no id found (or you can handle error)
                    continue;
               }

               $data = [
                    'assessment_date' => $assessment['assessment_date'] ?? null,
                    'criteria'        => $assessment['criteria'] ?? null,
                    'rating'          => $assessment['rating'] ?? null,
                    'remarks'         => $assessment['remarks'] ?? null,
               ];

               $updated = $userModel->updateCandidatePersonalityId($id, $data);

               if (!$updated) {
                    $success = false;
                    break;
               }
          }

          if ($success) {
               return $this->respond(['status' => true, 'message' => 'Personality assessments updated'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Failed to update personality assessments'], 500);
          }
     }





     public function deleteRadiologyPersonalityAssessmentById($id)
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteRadiologyPersonalityAssessmentById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Personality Assessment Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Personality Assessment  not Deleted'], 500);
          }
     }


     public function getRadiologyTechnicalEvaluation()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getRadiologyTechnicalEvaluation();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Technical Evaluation not found'], 404);
          }
     }





     public function createRadiologyTechnicalEvaluation()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          $request = $this->request->getJSON(true);

          if (!isset($request['candidate_id'], $request['evaluation']) || !is_array($request['evaluation'])) {
               return $this->respond(['status' => false, 'message' => 'Invalid input format'], 400);
          }

          $candidateId = $request['candidate_id'];
          $overallNotes = $request['overall_notes'] ?? null;
          $evaluations = $request['evaluation'];

          $userModel = new UserModel(); // Or use appropriate model
          $insertedIds = [];

          foreach ($evaluations as $row) {
               $data = [
                    "candidate_id"   => $candidateId,
                    "particular"     => $row['particular'] ?? null,
                    "assessment"     => $row['assessment'] ?? null,
                    "notes"          => $row['notes'] ?? null,
                    "overall_evaluation_notes" => $overallNotes,
                    'created_by'     => $user,
                    'created_on'     => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
                    'modified_on'    => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
               ];

               $inserted = $userModel->createRadiologyTechnicalEvaluation($data);

               if ($inserted) {
                    $insertedIds[] = $userModel->getInsertID();
               }
          }

          if (!empty($insertedIds)) {
               return $this->respond(['status' => true, 'data' => $insertedIds], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'No Technical Evaluation inserted'], 500);
          }
     }

     public function createRadiologyRegion()
     {
          $userDetails = $this->validateAuthorization(); // Your auth logic
          $user = $userDetails['user_code']; // Get current user code
          $request = $this->request->getJSON(true);

          if (!isset($request['candidate_id']) || !isset($request['regions']) || !is_array($request['regions'])) {
               return $this->respond(['status' => false, 'message' => 'Invalid payload format'], 400);
          }

          $candidateId = $request['candidate_id'];
          $regions = $request['regions'];
          $insertData = [];

          foreach ($regions as $region) {
               $regionName = $region['region'];
               $branches = $region['branches'];

               foreach ($branches as $branch) {
                    $insertData[] = [
                         'candidate_id' => $candidateId,
                         'region_name'  => $regionName,
                         'branch_name'  => $branch,
                         // 'created_by'   => $user,
                         'created_at'   => \CodeIgniter\I18n\Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
                    ];
               }
          }

          if (empty($insertData)) {
               return $this->respond(['status' => false, 'message' => 'No branches to insert'], 400);
          }

          $model = new \App\Models\UserModel(); // or RadiologyModel if applicable
          $success = $model->insertRadiologyRegion($insertData);

          if ($success) {
               return $this->respond(['status' => true, 'message' => 'Radiology regions saved successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Failed to save data'], 500);
          }
     }




     //  public function createRadiologyTechnicalEvaluation()
     //  {
     //       $userDetails = $this->validateAuthorization();
     //       $user = $userDetails['user_code'];
     //       $request = $this->request->getJSON(true); // Get as associative array
     //       $candidate_id = $request['candidate_id'] ?? [];
     //       $assessment_date = $request['assessment_date'] ?? [];
     //       $particular = $request['particular'] ?? [];
     //       $assessment  = $request['assessment'] ?? [];
     //       $notes = $request['notes'] ?? [];
     //       $Overall_evaluation_notes = $request['Overall_evaluation_notes'] ?? [];

     //       $data = [
     //           "candidate_id" => $candidate_id,
     //           "assessment_date" => $assessment_date,
     //           "particular" => $particular,
     //           "assessment" => $assessment,
     //           "notes" => $notes,
     //           "Overall_evaluation_notes"=>$Overall_evaluation_notes,
     //           'created_by'   => $user ?? '110104',
     //           'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
     //           'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
     //       ];

     //       $userModel = new UserModel();

     //       $user = $userModel->createRadiologyTechnicalEvaluation($data);
     //       if ($user) {  // true on success
     //           return $this->respond(['status' => true, 'data' => $user], 200);
     //       } else {
     //           return $this->respond(['status' => false, 'message' => 'Technical Evaluation not saved'], 500);
     //       }
     //  }


     public function getRadiologyTechnicalEvaluationById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getRadiologyTechnicalEvaluationById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Technical Evaluation not Found'], 500);
          }
     }



     public function updateRadiologyTechnicalEvaluationById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $assessment_date = $request['assessment_date'] ?? [];
          $particular = $request['particular'] ?? [];
          $assessment  = $request['assessment '] ?? [];
          $notes = $request['notes'] ?? [];

          $data = [
               "assessment_date" => $assessment_date,
               "particular" => $particular,
               "assessment" => $assessment,
               "notes" => $notes,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateRadiologyTechnicalEvaluationById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Technical Evaluation not Updated'], 500);
          }
     }


     public function updateTechnicalEvaluationId($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $assessment_date = $request['assessment_date'] ?? [];
          $particular = $request['particular'] ?? [];
          $assessment  = $request['assessment '] ?? [];
          $notes = $request['notes'] ?? [];

          $data = [
               "assessment_date" => $assessment_date,
               "particular" => $particular,
               "assessment" => $assessment,
               "notes" => $notes
          ];
          $user = $userModel->updateTechnicalEvaluationId($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Technical Evaluation not Updated'], 500);
          }
     }



     public function deleteRadiologyTechnicalEvaluationById($id)
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteRadiologyTechnicalEvaluationById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Technical Evaluation Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Technical Evaluation not Deleted'], 500);
          }
     }



     public function getRadiologyModalities()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getRadiologyModalities();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Radiology Modalities not found'], 404);
          }
     }




     //      public function createRadiologyModalities()
     // {
     //     $userDetails = $this->validateAuthorization();
     //     $user = $userDetails['user_code'];
     //     $request = $this->request->getJSON(true); // get as array

     //     if (!is_array($request)) {
     //         return $this->respond(['status' => false, 'message' => 'Invalid input format'], 400);
     //     }

     //     $userModel = new UserModel();
     //     $insertedIds = [];

     //     foreach ($request as $row) {
     //         $data = [
     //             "candidate_id" => $row['candidate_id'] ?? null,
     //             "mod_id"       => $row['mod_id'] ?? null,
     //             "sub_mod_id"   => $row['sub_mod_id'] ?? null,
     //             "sub_mod_class"        => $row['sub_mod_class'] ?? null,
     //             'created_by'   => $user ?? '110104',
     //             'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
     //             'modified_on'  => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
     //         ];

     //         // IMPORTANT: model should only insert and return insertID
     //         $inserted = $userModel->createRadiologyModalities($data);

     //         if ($inserted) {
     //             $insertedIds[] = $userModel->getInsertID();
     //         }
     //     }

     //     if (!empty($insertedIds)) {
     //         return $this->respond(['status' => true, 'data' => $insertedIds], 200);
     //     } else {
     //         return $this->respond(['status' => false, 'message' => 'No Radiology Modalities inserted'], 500);
     //     }
     // }




     public function createRadiologyModalities()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          $request = $this->request->getJSON(true);

          if (!is_array($request)) {
               return $this->respond(['status' => false, 'message' => 'Invalid input format'], 400);
          }

          $userModel = new UserModel();
          $insertedIds = [];

          foreach ($request as $row) {
               $data = [
                    "candidate_id"   => $row['candidate_id'] ?? null,
                    "mod_id"         => $row['mod_id'] ?? null,
                    "sub_mod_id"     => $row['sub_mod_id'] ?? null,
                    "sub_mod_class"  => $row['sub_mod_class'] ?? null,
                    'created_by'     => $user,
                    'created_on'     => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
                    'modified_on'    => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
               ];

               $inserted = $userModel->createRadiologyModalities($data);

               if ($inserted) {
                    $insertedIds[] = $userModel->getInsertID();
               }
          }

          if (!empty($insertedIds)) {
               return $this->respond(['status' => true, 'data' => $insertedIds], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'No Radiology Modalities inserted'], 500);
          }
     }



     public function getRadiologyModalityById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getRadiologyModalityById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Radiology Modalities not Found'], 500);
          }
     }



     public function updateRadiologyModalityById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $mod_id = $request['mod_id'] ?? [];
          $sub_mod_id = $request['sub_mod_id'] ?? [];
          $level  = $request['level'] ?? [];

          $data = [
               "mod_id" => $mod_id,
               "sub_mod_id" => $sub_mod_id,
               "level" => $level,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateRadiologyModalityById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Radiology Modalities not Updated'], 500);
          }
     }


     public function updateCandidateModalityId($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $mod_id = $request['mod_id'] ?? [];
          $sub_mod_id = $request['sub_mod_id'] ?? [];
          $sub_mod_class  = $request['sub_mod_class'] ?? [];

          $data = [
               "mod_id" => $mod_id,
               "sub_mod_id" => $sub_mod_id,
               "sub_mod_class" => $sub_mod_class,

          ];
          $user = $userModel->updateCandidateModalityId($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Candidate Modality not Updated'], 500);
          }
     }

     public function deleteRadiologyModalityById($id)
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteRadiologyModalityById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Radiology Modalities Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Radiology Modalities not Deleted'], 500);
          }
     }





     public function getRadiologyCandidates()
     {

          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getRadiologyCandidates();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Radiology Candidate  not found'], 404);
          }
     }

     public function getUserRadiologyCandidates()
     {

          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserRadiologyCandidates($user);

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Radiology Candidate  not found'], 404);
          }
     }

     public function getRadiologyHrCandidates()
     {

          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getRadiologyHrCandidates();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Radiology Hr Candidate  not found'], 404);
          }
     }




     public function createRadiologyCandidate()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          $request = $this->request->getJSON(true); // Get as associative array

          // Collect request fields safely
          $candidate_name           = $request['candidate_name'] ?? null;
          $position_applied         = $request['position_applied'] ?? null;
          $mobile                   = $request['mobile'] ?? null;
          $email                    = $request['email'] ?? null;
          $education_qualification  = $request['education_qualification'] ?? null;
          $medical_registration     = $request['medical_registration'] ?? null;
          $total_experience         = $request['total_experience'] ?? null;
          $department               = $request['department'] ?? null;
          $vdc_location             = $request['vdc_location'] ?? null;
          $association_type         = $request['association_type'] ?? null;
          $timing                   = $request['timing'] ?? null;

          // If you want to auto-set today’s date
          // $date = Time::now('Asia/Kolkata', 'en_US')->toDateString();

          $data = [
               "candidate_name"          => $candidate_name,
               "position_applied"        => $position_applied,
               "mobile"                  => $mobile,
               "email"                   => $email,
               "education_qualification" => $education_qualification,
               "medical_registration"    => $medical_registration,
               "total_experience"        => $total_experience,
               "department"              => $department,
               "vdc_location"            => $vdc_location,
               "association_type"        => $association_type,
               "timing"                  => $timing,
               // "date"                    => $date,
               "created_by"              => $user ?? '110104',
               "created_on"              => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               "modified_on"             => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();
          $user = $userModel->createRadiologyCandidate($data);

          if ($user) {  // true on success
               return $this->respond(['status' => true, 'candidate_id' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Candidate not saved'], 500);
          }
     }



     public function getRadiologyCandidateById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getRadiologyCandidateById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Candidate not Found'], 500);
          }
     }

     public function getStatus()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          $userModel = new UserModel();
          $user = $userModel->getStatus($user);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Status not Found'], 500);
          }
     }



     public function updateRadiologyCandidateById($id)
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'] ?? '110104';

          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array

          // Collect request fields safely
          $candidate_name           = $request['candidate_name'] ?? null;
          $position_applied         = $request['position_applied'] ?? null;
          $mobile                   = $request['mobile'] ?? null;
          $email                    = $request['email'] ?? null;
          $education_qualification  = $request['education_qualification'] ?? null;
          $medical_registration     = $request['medical_registration'] ?? null;
          $total_experience         = $request['total_experience'] ?? null;
          $department               = $request['department'] ?? null;
          $vdc_location             = $request['vdc_location'] ?? null;
          $association_type         = $request['association_type'] ?? null;
          $timing                   = $request['timing'] ?? null;


          $data = [
               "candidate_name"          => $candidate_name,
               "position_applied"        => $position_applied,
               "mobile"                  => $mobile,
               "email"                   => $email,
               "education_qualification" => $education_qualification,
               "medical_registration"    => $medical_registration,
               "total_experience"        => $total_experience,
               "department"              => $department,
               "vdc_location"            => $vdc_location,
               "association_type"        => $association_type,
               "timing"                  => $timing,

               "modified_by"             => $user,
               "modified_on"             => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $updated = $userModel->updateRadiologyCandidateById($id, $data);

          if ($updated) {
               return $this->respond(['status' => true, 'data' => $updated], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Candidate not updated'], 500);
          }
     }


     public function updateCandidateDetailsId($id)
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'] ?? '110104';

          $userModel = new UserModel();
          // $modalityModel = new ModalityModel();
          // $personalityModel = new PersonalityAssessmentModel();
          // $technicalModel = new TechnicalEvaluationModel();

          // associative array

          $db = \Config\Database::connect();
          $db->transBegin();
          try {
               $request = $this->request->getJSON(true);
               // Extract personal details from nested 'personal'
               $personal = $request['personal'] ?? [];

               $data = [
                    "candidate_name" => $personal['candidate_name'] ?? null,
                    "position_applied" => $personal['position_applied'] ?? null,
                    "mobile" => $personal['mobile'] ?? null,
                    "email" => $personal['email'] ?? null,
                    // "rpt_mgr_status" => $personal['rptMgrStatus'] ?? null,
                    // "overallNotes" => $personal['overallNotes'] ?? null,
                    "timing_from" => $personal['timing_from'] ?? null,
                    "timing_to" => $personal['timing_to'] ?? null,
                    "medical_registration" => $personal['medical_registration'] ?? null,
                    "education_qualification" => $personal['education_qualification'] ?? null,
                    "Paediatric" => $personal['Paediatric'] ?? null,
                    "total_experience" => $personal['total_experience'] ?? null,
                    "department" => $personal['department'] ?? null,
                    "vdc_location" => $personal['vdc_location'] ?? null,
                    "association_type" => $personal['association_type'] ?? null,
                    //"manager_approved_date" => $personal['managerApprovedDate'] ?? null,
                    // "hr_status" => $personal['hrStatus'] ?? null,
                    //"hr_approved_date" => $personal['hrApprovedDate'] ?? null,
                    // Add more fields if necessary
               ];




               // Update candidate personal info
               $updatedCandidate = $userModel->updateCandidateDetailsId($id, $data);


               // Update Modalities - ONLY if 'id' is provided; else skip
               if (isset($request['modalities']) && is_array($request['modalities'])) {
                    foreach ($request['modalities'] as $modality) {

                         if (!empty($modality['id'])) {
                              // 🔹 Update existing record
                              $updateData = [
                                   'modality'        => $modality['modality'] ?? '',
                                   'selection_area'  => $modality['selection_area'] ?? '',
                                   'level'           => $modality['level'] ?? '',
                                   'applicable'      => $modality['applicable'] ?? ''
                              ];
                              $userModel->updateModalityDetailsId($modality['id'], $updateData);
                         } else {

                              // 🔹 Insert new record (include candidate_id)
                              $insertData = [
                                   'candidate_id'    => $id ?? '',
                                   'modality'        => $modality['modality'] ?? '',
                                   'selection_area'  => $modality['selection_area'] ?? '',
                                   'level'           => $modality['level'] ?? '',
                                   'applicable'      => $modality['applicable'] ?? ''
                              ];

                              $userModel->createRadiologyModalities($insertData);
                         }
                    }
               }




               // Update Personality Assessments - ONLY if 'id' is provided
               if (isset($request['personality_assessments']) && is_array($request['personality_assessments'])) {
                    foreach ($request['personality_assessments'] as $personality) {
                         if (!empty($personality['id'])) {
                              $personalityData = [
                                   // 'assessment_date' => $personality['assessment_date'] ?? null,
                                   'criteria' => $personality['criteria'] ?? '',
                                   // 'rating' => $personality['rating'] ?? '0',
                                   'remarks' => $personality['remarks'] ?? '',
                              ];
                              $userModel->updatePersonalityDetailsId($personality['id'], $personalityData);
                         }
                    }
               }

               // Update Technical Evaluations - ONLY if 'id' is provided
               if (isset($request['technical_evaluations']) && is_array($request['technical_evaluations'])) {


                    foreach ($request['technical_evaluations'] as $technical) {

                         if (!empty($technical['id'])) {
                              $technicalData = [
                                   'particular' => $technical['particular'] ?? '',
                                   'assessment' => $technical['assessment'] ?? '',
                                   'notes' => $technical['notes'] ?? '',
                              ];
                              $userModel->updateTechnicalDetailsId($technical['id'], $technicalData);
                         }
                    }
               }


               // Update Regions - ONLY if 'id' is provided, else insert
               if (isset($request['regions']) && is_array($request['regions'])) {
                    foreach ($request['regions'] as $region) {
                         // Build data to save
                         $regionData = [
                              'region_name' => $region['region_name'] ?? '',
                              'branch_name' => $region['branch_name'] ?? '',
                              'candidate_id' => $id, // always pass candidate_id for insert
                         ];

                         if (!empty($region['id'])) {
                              // 🔹 Update existing region
                              $userModel->updateRegionDetailsId($region['id'], $regionData);
                         } else {
                              // 🔹 Insert new region
                              $userModel->createCandidateRegion($regionData);
                         }
                    }
               }

               $db->transCommit();
               return $this->respond(['status' => true, 'message' => 'Candidate updated successfully'], 200);
          } catch (\Exception $e) {
               // Rollback all changes if any update failed
               $db->transRollback();

               return $this->respond([
                    'status' => false,
                    'message' => 'Update failed: ' . $e->getMessage()
               ], 500);
          }
     }


     public function updateModalityDetailsId($id, $data)
     {
          $userModel = new UserModel();
          //  $request = $this->request->getJSON(true); // Get as associative array
          $id = $id ?? [];
          $modality = $data['modality'] ?? [];
          $selection_area  = $data['selection_area'] ?? [];
          $applicable  = $data['applicable'] ?? [];
          $level  = $data['level'] ?? [];

          $data = [
               "id" => $id,
               "modality" => $modality,
               "selection_area" => $selection_area,
               "applicable" => $applicable,
               "level" => $level,

          ];
          $user = $userModel->updateModalityDetailsId($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Candidate Modality not Updated'], 500);
          }
     }






     public function deleteRadiologyCandidateById($id)
     {
          $userModel = new UserModel();
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'] ?? '110104';
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteRadiologyCandidateById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Candidate Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Candidate  not Deleted'], 500);
          }
     }





     public function getModalities()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getModalities();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Modality  not found'], 404);
          }
     }


     public function createModality()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $mod_name = $request['mod_name'] ?? [];


          $data = [
               "mod_name" => $mod_name,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createModality($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Modality not saved'], 500);
          }
     }


     public function getModalityById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getModalityById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Modality not Found'], 500);
          }
     }



     public function updateModalityById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $mod_name = $request['mod_name'] ?? [];
          $data = [
               "mod_name" => $mod_name,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateModalityById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Modality not Updated'], 500);
          }
     }

     public function deleteModalityById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteModalityById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Modality Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Modality  not Deleted'], 500);
          }
     }



     public function getSubModClass()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getSubModClass();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Modality  not found'], 404);
          }
     }


     public function createSubModClass()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $sub_mod_class = $request['sub_mod_class'] ?? [];


          $data = [
               "sub_mod_class" => $sub_mod_class,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createSubModClass($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Mod Class not saved'], 500);
          }
     }


     public function getSubModClassById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getSubModClassById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Mod Class not Found'], 500);
          }
     }



     public function updateSubModClassById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $sub_mod_class = $request['sub_mod_class'] ?? [];
          $data = [
               "sub_mod_class" => $sub_mod_class,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateSubModClassById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Mod Class not Updated'], 500);
          }
     }

     public function deleteSubModClassById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteSubModClassById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Sub Mod Class Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Mod Class  not Deleted'], 500);
          }
     }




     public function getSubModalities()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getSubModalities();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Modality  not found'], 404);
          }
     }


     public function createSubModality()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $mod_id = $request['mod_id'] ?? [];
          $sub_mod_name = $request['sub_mod_name'] ?? [];
          $service_id = $request['service_id'] ?? [];
          $sub_mod_class = $request['sub_mod_class'] ?? [];

          $data = [
               "mod_id" => $mod_id,
               "sub_mod_name" => $sub_mod_name,
               "service_id" => $service_id,
               "sub_mod_class" => $sub_mod_class,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createSubModality($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Modality not saved'], 500);
          }
     }


     public function getSubModalityById($id)
     {
          $userModel = new UserModel();
          $user = $userModel->getSubModalityById($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Modality not Found'], 500);
          }
     }



     public function updateSubModalityById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $mod_id = $request['mod_id'] ?? [];
          $sub_mod_name = $request['sub_mod_name'] ?? [];
          $service_id = $request['service_id'] ?? [];
          $sub_mod_class = $request['sub_mod_class'] ?? [];
          $data = [
               "mod_id" => $mod_id,
               "sub_mod_name" => $sub_mod_name,
               "service_id" => $service_id,
               "sub_mod_class" => $sub_mod_class,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateSubModalityById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Modality not Updated'], 500);
          }
     }

     public function deleteSubModalityById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteSubModalityById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Sub Modality Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Modality  not Deleted'], 500);
          }
     }



     public function getRadiologyDoctor()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getRadiologyDoctor();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Radiology Doctor  not found'], 404);
          }
     }


     public function createRadiologyDoctor()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails->emp_code;
          $request = $this->request->getJSON(true); // Get as associative array
          $mod_id = $request['mod_id'] ?? [];
          $sub_mod_name = $request['sub_mod_name'] ?? [];
          $service_id = $request['service_id'] ?? [];
          $sub_mod_class = $request['sub_mod_class'] ?? [];

          $data = [
               "mod_id" => $mod_id,
               "sub_mod_name" => $sub_mod_name,
               "service_id" => $service_id,
               "sub_mod_class" => $sub_mod_class,
               'created_by'   => $user ?? '110104',
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $userModel = new UserModel();

          $user = $userModel->createRadiologyDoctor($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Modality not saved'], 500);
          }
     }


     public function getRadiologyDoctorId($id)
     {

          $userModel = new UserModel();
          $user = $userModel->getRadiologyDoctorId($id);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Radiology Doctor not Found'], 500);
          }
     }

     public function getCandidateFullDetails($id = null)
     {

          $userModel = new UserModel();

          if ($id) {
               $candidate = $userModel->getCandidateInfo($id);
               if (!$candidate) {
                    return $this->failNotFound("Candidate not found.");
               }

               $response = [
                    'candidate' => $candidate,
                    'regions' => $userModel->getCandidateRegions($id),
                    'personality_assessments' => $userModel->getCandidatePersonalityAssessments($id),
                    'modalities' => $userModel->getCandidateModalities($id),
                    'technical_evaluations' => $userModel->getCandidateTechnicalEvaluations($id),
               ];
          } else {
               // Get all candidates basic info
               $response = $userModel->getAllCandidatesFullDetails();
          }

          return $this->respond($response);
     }

     public function getUserCandidateFullDetails($id = null)
     {
          $userModel = new UserModel();
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          if ($id) {
               $candidate = $userModel->getCandidateInfo($id);
               if (!$candidate) {
                    return $this->failNotFound("Candidate not found.");
               }

               $response = [
                    'candidate' => $candidate,
                    'regions' => $userModel->getCandidateRegions($id),
                    'personality_assessments' => $userModel->getCandidatePersonalityAssessments($id),
                    'modalities' => $userModel->getCandidateModalities($id),
                    'technical_evaluations' => $userModel->getCandidateTechnicalEvaluations($id),
               ];
          } else {
               // Get all candidates basic info
               $response = $userModel->getUserCandidatesFullDetails($user);
          }

          return $this->respond($response);
     }





     public function updateRadiologyDoctorById($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $mod_id = $request['mod_id'] ?? [];
          $sub_mod_name = $request['sub_mod_name'] ?? [];
          $service_id = $request['service_id'] ?? [];
          $sub_mod_class = $request['sub_mod_class'] ?? [];
          $data = [
               "mod_id" => $mod_id,
               "sub_mod_name" => $sub_mod_name,
               "service_id" => $service_id,
               "sub_mod_class" => $sub_mod_class,
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->updateRadiologyDoctorById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Modality not Updated'], 500);
          }
     }

     public function deleteRadiologyDoctorById($id)
     {
          $userModel = new UserModel();
          $data = [
               'status' => 'I',
               'modified_by' => $user ?? '110104',
               'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];
          $user = $userModel->deleteRadiologyDoctorById($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'message' => 'Sub Modality Deleted Successfully'], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Modality  not Deleted'], 500);
          }
     }





     public function getEmp()
     {

          $userDetails = $this->validateAuthorization();
          //  $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getEmp();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Blood Group not found'], 404);
          }
     }


     public function saveUser()
     {
          $request = service('request');

          // Get JSON string from form-data field 'data'
          $jsonString = $request->getPost('data');
          $data = json_decode($jsonString, true);

          $documentData = [];

          // ✅ Handle uploaded documents (multiple files)
          if ($files = $this->request->getFiles()) {
               // Check if 'photo' is an array (this handles multiple files under 'photo[]')

               if (isset($files['photo'])) {
                    // Normalize into an array (handles both single and multiple files)
                    $photos = $files['photo'];
                    if (!is_array($photos)) {
                         $photos = [$photos]; // Ensure it's an array
                    }

                    foreach ($photos as $file) {
                         if ($file->isValid() && !$file->hasMoved()) {
                              // Generate a random file name and define upload path
                              $fileName   = $file->getRandomName();
                              $uploadPath = FCPATH . 'uploads/employe/';

                              // Create directory if it doesn't exist
                              if (!is_dir($uploadPath)) {
                                   mkdir($uploadPath, 0777, true);
                              }

                              // Move the file to the upload directory
                              $file->move($uploadPath, $fileName);

                              // Save document data for later insertion into the database
                              $documentData[] = [
                                   'document_name' => $file->getClientName(), // Original file name
                                   'document_path' => 'uploads/employe/' . $fileName,
                                   'uploaded_at'   => date('Y-m-d H:i:s')
                              ];
                         }
                    }
               }
          }

          // ✅ Insert into DB via model
          $userModel = new \App\Models\UserModel();
          $result = $userModel->insertUser($data, $documentData);

          // Return response based on result
          if ($result['status']) {
               $this->logActivity('create', $result['emp_id'], $data);
               return $this->respond([
                    'status'  => true,
                    'message' => 'User saved successfully',
                    'emp_id'  => $result['emp_id']
               ]);
          } else {
               return $this->respond([
                    'status'  => false,
                    'message' => $result['message']
               ], 500);
          }
     }








     public function getAllUsers()
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getAllUsers();

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Users not found'], 404);
          }
     }

     public function getUserById($id)
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserById($id);

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Users not found'], 404);
          }
     }


     public function getEmpByIdNew($id)
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getEmpByIdNew($id);

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Users not found'], 404);
          }
     }

     public function getRadiologyUserById($id)
     {
          $userDetails = $this->validateAuthorization();
          // $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getRadiologyUserById($id);

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Radiology Users not found'], 404);
          }
     }

     // public function getAllUsers()
     // {
     //     $db = \Config\Database::connect();
     //     $users = [];

     //     // Get all user_ids from the doctors table
     //     $doctorRows = $db->table('doctors')->get()->getResultArray();

     //     foreach ($doctorRows as $doctor) {
     //         $id = $doctor['user_id'];
     //         $user = [];

     //         $user['tab1'] = $doctor;
     //         $user['tab2'] = $db->table('doc_contract')->where('user_id', $id)->get()->getRowArray();
     //         $user['tab3'] = $db->table('doc_contact')->where('user_id', $id)->get()->getRowArray();
     //         $user['tab4']['qualifications'] = $db->table('doc_qualifications')->where('user_id', $id)->get()->getResultArray();
     //         $user['tab5']['earnings'] = $db->table('doc_earnings')->where('user_id', $id)->get()->getResultArray();
     //         $user['tab6']['deductions'] = $db->table('doc_deductions')->where('user_id', $id)->get()->getResultArray();
     //         $user['tab7']['loans'] = $db->table('doc_loans')->where('user_id', $id)->get()->getResultArray();
     //         $user['tab8']['leaves'] = $db->table('doc_leaves')->where('user_id', $id)->get()->getResultArray();

     //         // Merge air ticket policy into tab8
     //         $airPolicy = $db->table('doc_air_ticket_policy')->where('user_id', $id)->get()->getRowArray();
     //         if (!empty($airPolicy)) {
     //             $user['tab8'] = array_merge($user['tab8'], $airPolicy);
     //         }

     //         $user['tab9'] = $db->table('doc_pf_details')->where('user_id', $id)->get()->getRowArray();

     //         $users[] = $user;
     //     }

     //     return $this->response->setJSON($users);
     // }

     public function updateDoc($id)
     {
          $model = new UserModel();

          // Prepare data (only updated_on by default)
          $data = [
               'updated_at' => date('Y-m-d H:i:s'),
          ];

          // Get uploaded file (document)
          $file = $this->request->getFile('photo');

          // Call model function
          $result = $model->updateDocData($id, $data, $file);

          // Return response
          if ($result['status']) {
               return $this->respond([
                    'status'  => true,
                    'message' => $result['message']
               ], 200);
          } else {
               return $this->respond([
                    'status'  => false,
                    'message' => $result['message']
               ], 400);
          }
     }


     public function updateRadiologyUser($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $user_name = $request['user_name'] ?? [];
          $user_code = $request['user_code'] ?? [];
          $role = $request['role'] ?? [];

          //   $service_id = $request['service_id'] ?? [];
          //   $sub_mod_class = $request['sub_mod_class'] ?? [];
          $data = [
               "user_name" => $user_name,
               "user_code" => $user_code,
               "role" => $role
               //   "service_id" => $service_id,
               //   "sub_mod_class" => $sub_mod_class,
               //   'modified_by' => $user ?? '110104',
               //   'modified_on' => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $user = $userModel->updateRadiologyUser($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Sub Modality not Updated'], 500);
          }
     }




     public function deleteUser($id)
     {
          if (!$id) {
               return $this->respond(['status' => false, 'message' => 'Invalid ID'], 400);
          }

          $userModel = new \App\Models\UserModel();
          $result = $userModel->deleteUserData($id);
          if ($result['status']) {
               $this->logActivity('delete', $id, []);
               return $this->respond(['status' => true, 'message' => 'User deleted successfully']);
          } else {
               return $this->respond(['status' => false, 'message' => $result['message']], 500);
          }
     }


     public function deleteDoc($id)
     {
          if (!$id) {
               return $this->respond(['status' => false, 'message' => 'Invalid ID'], 400);
          }

          $userModel = new \App\Models\UserModel();
          $result = $userModel->deleteDocData($id);
          if ($result['status']) {
               $this->logActivity('delete', $id, []);
               return $this->respond(['status' => true, 'message' => 'Doc deleted successfully']);
          } else {
               return $this->respond(['status' => false, 'message' => $result['message']], 500);
          }
     }



     // private function logActivity($action, $userId, $details = [])
     // {
     //      $db = \Config\Database::connect();
     //      $performedBy = $this->request->user_code ?? 'system'; // Or get from JWT/session
     //      $ip = $this->request->getIPAddress();
     //      $agent = $this->request->getUserAgent();
     //      $db->table('user_activity_logs')->insert([
     //           'user_id'      => $userId,
     //           'action'       => $action,
     //           'performed_by' => $performedBy,
     //           'details'      => json_encode($details),
     //           'ip_address'   => $ip,
     //           'user_agent'   => $agent,
     //      ]);
     // }


     public function getUserAttendance()
     {

          $userDetails = $this->validateAuthorization();

          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getUserAttendance($user);

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Attendance not found'], 404);
          }
     }

     //  public function getMonthlyAttendance()
     //  {

     //       $userDetails = $this->validateAuthorization();
     //      $user = $userDetails['user_code'];

     //       $userModel = new UserModel();
     //       $user = $userModel->getMonthlyAttendance($user);

     //       if ($user) {
     //           return $this->respond(['status' => true, 'data' => $user], 200);
     //       } else {
     //           return $this->respond(['status' => false, 'message' => 'User Attendance not found'], 404);
     //       }
     //  }


     public function getMonthlyAttendance()
     {
          $userDetails = $this->validateAuthorization();
          // Fix: Safely get user_code or emp_code
          if (!is_array($userDetails) || (!isset($userDetails['user_code']) && !isset($userDetails['emp_code']))) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Unauthorized or missing user_code'
               ], 401);
          }

          $user = $userDetails['user_code'] ?? $userDetails['emp_code'];


          // Get year and month from query parameters
          $year = $this->request->getGet('year');
          $month = $this->request->getGet('month');

          if (empty($year)) {
               $year = date('Y'); // Current year
          }
          if (empty($month)) {
               $month = date('n'); // Current month (1-12)
          }
          // Validate year and month parameters
          if (!$year || !$month) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Year and month parameters are required'
               ], 400);
          }

          // Validate year format (4 digits)
          if (!preg_match('/^\d{4}$/', $year) || $year < 1900 || $year > 2100) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Invalid year format. Please provide a valid 4-digit year'
               ], 400);
          }

          // Validate month format (1-12)
          if (!is_numeric($month) || $month < 1 || $month > 12) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Invalid month. Please provide a month between 1 and 12'
               ], 400);
          }

          // Optional: Validate future dates
          $currentYear = date('Y');
          $currentMonth = date('n');
          if ($year > $currentYear || ($year == $currentYear && $month > $currentMonth)) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Cannot fetch attendance data for future dates'
               ], 400);
          }

          $userModel = new UserModel();
          $attendanceData = $userModel->getMonthlyAttendance($user, $year, $month);

          if ($attendanceData) {
               return $this->respond([
                    'status' => true,
                    'data' => $attendanceData,
                    'year' => (int)$year,
                    'month' => (int)$month,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                    'total_records' => count($attendanceData)
               ], 200);
          } else {
               return $this->respond([
                    'status' => false,
                    'message' => "No attendance data found for {$year}-{$month}"
               ], 404);
          }
     }


     public function changepassword()
     {
          // Get input data
          $data = $this->request->getJSON(true);
          $empCode = $data['emp_code'] ?? null;
          $oldPassword = $data['oldPassword'] ?? null;
          $newPassword = $data['newPassword'] ?? null;
          $confirmPassword = $data['confirmPassword'] ?? null;

          $db      = \Config\Database::connect();
          $builder = $db->table('users');



          if (!$newPassword || !$confirmPassword) {
               return $this->failValidationError('Current password and new password are required');
          }

          $users = new UserModel();
          $user = $users->where('user_code', $empCode)->first();

          if (!$user) {
               return $this->failNotFound('User not found');
          }



          // Verify the current password
          if (md5($oldPassword) != $user['password']) { // Ensure you match the hash method used in your database
               return $this->failUnauthorized('Old password is incorrect');
          }

          $validityDate = new \DateTime(); // Current date
          $validityDate->modify('+90 days'); // Add 90 days to the current date
          $newValidity = $validityDate->format('Y-m-d');



          // Hash the new password
          $hashedNewPassword = md5($newPassword);


          // Update the password in the database
          // $updated = $users->update($empCode, ['password' => $hashedNewPassword]);
          $builder->where('user_code', $empCode)
               ->update(['password' => $hashedNewPassword]);
          $updated = $builder->where('user_code', $empCode)
               ->update(['validity' => $newValidity]);


          if ($updated) {
               return $this->respond(['status' => true, 'message' => 'Password changed successfully'], 200);
          } else {
               return $this->respond(['error' => 'Failed to change password'], 500);
          }
     }


     public function updateManagerStatusId($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array

          $rpt_mgr_status = $request['rpt_mgr_status'] ?? [];
          $report_manager = $request['reporting_manager'] ?? [];
          $rm_name = $request['rm_name'] ?? [];
          $overallNotes = $request['overallNotes'] ?? [];

          // Update Technical Evaluations - ONLY if 'id' is provided
          if (isset($request['technical']) && is_array($request['technical'])) {
               foreach ($request['technical'] as $tech) {
                    if (!empty($tech['id'])) {
                         $technicalData = [
                              'assessment' => $tech['assessment'] ?? '',
                              'notes'      => $tech['notes'] ?? '',
                              'grading'    => $tech['grading'] ?? '',
                         ];
                         $userModel->updateTechnicalDetailsId($tech['id'], $technicalData);
                    }
               }
          }

          $data = [
               "rpt_mgr_status"       => $rpt_mgr_status,
               "reporting_manager"    => $report_manager,
               "rm_name"              => $rm_name,
               "overallNotes"         => $overallNotes,
               "manager_approved_date" => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];

          $user = $userModel->updateManagerStatusId($id, $data);

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Manager Status not Updated'], 500);
          }
     }



     public function updateHrStatusId($id)
     {
          $userModel = new UserModel();
          $request = $this->request->getJSON(true); // Get as associative array
          $hr_status = $request['hr_status'] ?? [];
          $hr = $request['hr'] ?? [];
          $hr_name = $request['hr_name'] ?? [];


          $data = [
               "hr_status" => $hr_status,
               "hr" => $hr,
               "hr_name" => $hr_name,
               "hr_approved_date" => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()

          ];


          $user = $userModel->updateHrStatusId($id, $data);
          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'Manager Status not Updated'], 500);
          }
     }


     public function assessment_form()
     {
          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          $userModel = new UserModel();
          $request = $this->request->getJSON(true);
          $personal = $request['personal'] ?? [];
          $modalities = $request['modalities'] ?? [];
          $technical = $request['technical'] ?? [];
          $personality = $request['personality'] ?? [];
          $regions = $request['regions'] ?? [];

          $db      = \Config\Database::connect();
          $db->transStart();

          // Generate 6-digit random number and prefix with VDCR
          $randomDigits = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
          $trans_code = 'VDCR' . $randomDigits;

          $candidate_data = [
               'position_applied' => $personal['positionApplied'] ?? '',
               'trans_code' => $trans_code,
               'candidate_name' => $personal['candidateName'] ?? '',
               'mobile' => $personal['mobile'] ?? '',
               'email' => $personal['email'] ?? '',
               'education_qualification' => $personal['qualification'] ?? '',
               'medical_registration' => $personal['registration'] ?? '',
               'paediatric' => $personal['paediatric'] ?? '',
               'total_experience' => $personal['totalExperience'] ?? '',
               'department' => $personal['department'] ?? '',
               'vdc_location' => $personal['vdcLocation'] ?? '',
               'association_type' => $personal['typeOfAssociation'] ?? '',
               'timing_from' => $personal['timingsFrom'] ?? '',
               'timing_to' => $personal['timingsTo'] ?? '',
               'created_by' => $user ?? '110104',
               'created_on' => date('Y-m-d H:i:s')
          ];


          $candidate_id = $userModel->insertCandidate($candidate_data);

          if ($candidate_id && !empty($modalities)) {
               foreach ($modalities as $modalityItem) {
                    $modality = $modalityItem['modality'] ?? '';
                    $selections = $modalityItem['selections'] ?? [];

                    foreach ($selections as $selection => $data) {
                         $applicable = $data['applicable'] ?? '';
                         $level = $data['level'] ?? '';

                         $modality_data = [
                              'candidate_id' => $candidate_id,
                              'modality' => $modality,
                              'selection_area' => $selection,
                              'level' => $level,
                              'applicable' => $applicable,   // Store applicable too if your DB supports this
                              'created_on' => date('Y-m-d H:i:s')
                         ];

                         $userModel->insertModality($modality_data);
                    }
               }
          }



          if ($candidate_id && !empty($technical)) {
               foreach ($technical as $tech) {
                    $technical_data = [
                         'candidate_id' => $candidate_id,
                         'particular' => $tech['type'],
                         'assessment' => $tech['assessment'],
                         'grading' => $tech['grading'],
                         'notes' => $tech['notes'],
                         'created_by' => $user,
                         'created_on' => date('Y-m-d H:i:s')
                    ];
                    $userModel->insertTechnical($technical_data);
               }
          }

          if ($candidate_id && !empty($personality)) {

               foreach ($personality as $person) {

                    $personality_data = [
                         'candidate_id' => $candidate_id,
                         'criteria' => $person['type'],
                         'remarks' => $person['criteria'],
                         'created_by' => $user,
                         'created_on' => date('Y-m-d H:i:s')
                    ];
                    $userModel->insertPersonality($personality_data);
               }
          }

          if ($candidate_id && !empty($regions)) {
               foreach ($regions as $regionName => $locations) {
                    foreach ($locations as $location) {
                         $region_data = [
                              'candidate_id' => $candidate_id,
                              'region_name' => $regionName,      // e.g., "AP & Telangana"
                              'branch_name' => $location,      // e.g., "Himayathnagar"
                              'created_by' => $user,
                              'created_on' => date('Y-m-d H:i:s')
                         ];
                         $userModel->insertRegion($region_data);
                         // Make sure this method is implemented
                    }
               }
          }

          $db->transComplete();
          if ($db->transStatus() === FALSE) {
               return $this->failServerError("Something went wrong. All changes were rolled back.");
          }
          return $this->respond([
               'status' => true,
               'message' => 'Candidate assessment saved successfully.',
               'candidate_id' => $candidate_id
          ]);
     }

     public function createUser()
     {

          $userDetails = $this->validateAuthorization();
          $user = $userDetails['user_code'];
          $request = $this->request->getJSON(true); // Get as associative array
          $user_code = $request['user_code'] ?? [];
          $user_name = $request['user_name'] ?? [];
          $role = $request['role'] ?? [];
          $validityDate = \CodeIgniter\I18n\Time::now('Asia/Kolkata', 'en_US')->addDays(90)->toDateTimeString();


          $data = [
               "user_code" => $user_code,
               "user_name" => $user_name,
               "role" => $role,
               'created_by'   => $user ?? '110104',
               'validity'   => $validityDate,
               'created_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString(),
               'modified_on'   => Time::now('Asia/Kolkata', 'en_US')->toDateTimeString()
          ];


          $userModel = new UserModel();

          $user = $userModel->createUser($data);
          if ($user) {  // true on success
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Mode not saved'], 500);
          }
     }

     public function getDetails()
     {

          $userDetails = $this->validateAuthorization();

          $user = $userDetails['user_code'];

          $userModel = new UserModel();
          $user = $userModel->getDetails($user);

          if ($user) {
               return $this->respond(['status' => true, 'data' => $user], 200);
          } else {
               return $this->respond(['status' => false, 'message' => 'User Details not found'], 404);
          }
     }
}
