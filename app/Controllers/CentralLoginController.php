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

    // Debug: Hardcoded key and token
    $key = '4e9f2c0a4f1d3b8e6a2c4b7d8e9f0c1a2f4b6d7e8a9c0f1e2d3b5c7a8d9f0e1';
    $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJJc3N1ZXIgb2YgdGhlIEpXVCIsImF1ZCI6IkF1ZGllbmNlIG9mIHRoZSBKV1QiLCJzdWIiOiIxMDgyMjQiLCJpc19hZG1pbiI6IlkiLCJpYXQiOjE3NTgzNjMwMTAsImV4cCI6MTc1ODM3MDIxMCwiZW1wX2NvZGUiOiIxMDgyMjQifQ.ExmmyRvDPFzrQCVWDH9ETEekUUil7ovOWrb_U4dXu7Q';

    try {
        $payload = JWT::decode($token, new Key($key, 'HS256'));
        print_r($payload);
        exit();
    } catch (\Exception $e) {
        echo 'Invalid token: ' . $e->getMessage();
        exit();
    }
}
}
