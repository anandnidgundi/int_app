<?php

namespace App\Controllers;


use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CentralLoginController extends BaseController
{
     use ResponseTrait;
     protected $userModel;

     public function __construct()
     {
          $this->userModel = new UserModel();
     }

     public function centralLogin()
     {
          $json = $this->request->getJSON();
          if (!$json || !isset($json->token)) {
               return $this->fail('Token is required', 400);
          }
          $token = $json->token;

          $key = getenv('JWT_SECRET');
          log_message('Received Token:', $token);
          log_message('JWT Secret Key:', $key);
          if (!$key) {
               return $this->fail('JWT secret key not found.', 500);
          }

          try {
               $payload = JWT::decode($token, new Key($key, 'HS256'));
               if (isset($payload->exp) && time() > $payload->exp) {
                    return $this->fail('Token has expired.', 401);
               }

               // Fetch user using emp_code
               $emp_code = $payload->emp_code;
               $user = $this->userModel->where('emp_code', $emp_code)->first();

               if (!$user) {
                    return $this->fail('User not found.', 404);
               }

               // Optional: Repeat account status checks as in index()
               if ($user['disabled'] === 'Y') {
                    return $this->fail('Your account is disabled due to too many failed login attempts.', 401);
               }
               $currentDate = date('Y-m-d');
               $validityDate = $user['validity'];
               if (isset($validityDate) && (strtotime($currentDate) > strtotime($validityDate))) {
                    return $this->fail('Your Validity has expired.', 401);
               }
               $active = $user['active'];
               if (isset($active) && ($active != 'Active')) {
                    return $this->fail('Your account has expired.', 401);
               }
               $exit_date = $user['exit_date'];
               if (($exit_date != '0000-00-00') && (strtotime($currentDate) >= strtotime($exit_date))) {
                    return $this->fail('Your account has expired.', 401);
               }

               // Generate new token
               $iat = time();
               $exp = $iat + 7200; // 2 hour expiration

               $newPayload = [
                    "iss" => "Issuer of the JWT",
                    "aud" => "Audience of the JWT",
                    "sub" => $emp_code,
                    "is_admin" => $user['is_admin'],
                    "iat" => $iat,
                    "exp" => $exp,
                    "emp_code" => $user['emp_code'],
               ];

               $newToken = JWT::encode($newPayload, $key, 'HS256');
               return $this->respond([
                    'status' => true,
                    'message' => 'Token verified and new token generated.',
                    'token' => $newToken,
                    'exp' => $exp,
                    'emp_code' => $emp_code,
                    'user' => [
                         'emp_code' => $user['emp_code'],
                         'is_admin' => $user['is_admin'],
                         // Add other user fields as needed
                    ]
               ], 200);
          } catch (\Exception $e) {
               return $this->fail('Invalid token: ' . $e->getMessage(), 401);
          }
     }
}
