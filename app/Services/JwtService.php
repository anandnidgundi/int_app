<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
     /**
      * Validates a JWT token from the Authorization header.
      * @param string|null $authorizationHeader
      * @return array [ 'data' => decodedToken ] or [ 'error' => message, 'status' => code ]
      */
     public function validateToken($authorizationHeader)
     {
          if (!$authorizationHeader) {
               return ['error' => 'Authorization header missing', 'status' => 401];
          }
          if (!str_starts_with($authorizationHeader, 'Bearer ')) {
               return ['error' => 'Invalid Authorization header format', 'status' => 401];
          }
          $token = substr($authorizationHeader, 7);
          $key = getenv('JWT_SECRET');
          if (!$key) {
               return ['error' => 'JWT secret key not found', 'status' => 500];
          }
          try {
               $decoded = JWT::decode($token, new Key($key, 'HS256'));
               return ['data' => (array)$decoded];
          } catch (\Firebase\JWT\ExpiredException $e) {
               return ['error' => 'Token expired', 'status' => 401];
          } catch (\Exception $e) {
               return ['error' => 'Invalid token: ' . $e->getMessage(), 'status' => 401];
          }
     }
}
