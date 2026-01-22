<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Logout extends ResourceController
{
    public function index()
    {
        try {
            // Retrieve the token from the Authorization header
            $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];

            if ($authorizationHeader) {
                // Check if the token starts with 'Bearer ' and extract the token
                if (strpos($authorizationHeader, 'Bearer ') === 0) {
                    // Extract token by removing the 'Bearer ' prefix
                    $token = substr($authorizationHeader, 7);
                } else {
                    // If it doesn't have the prefix, use it as is
                    $token = $authorizationHeader;
                }

                // Trim any whitespace from the token
                $token = trim($token);

                // Token received successfully (no blacklist logic)
                return $this->respond(['status' => true, 'message' => 'Logout request received.'], 200);
            } else {
                return $this->failUnauthorized('Token required');
            }
        } catch (\Exception $e) {
            return $this->fail('An error occurred: ' . $e->getMessage());
        }
    }
}
