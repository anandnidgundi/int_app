<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuth implements FilterInterface
{
     public function before(RequestInterface $request, $arguments = null)
     {
          // Allow CORS preflight requests to pass through
          if (strtoupper($request->getMethod()) === 'OPTIONS') {
               return;
          }
          $authHeader = $request->getHeaderLine('Authorization');
          if (!$authHeader) {
               return service('response')->setJSON(['error' => 'Authorization header missing'])->setStatusCode(401);
          }
          $token = null;
          if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
               $token = $matches[1];
          }
          if (!$token) {
               return service('response')->setJSON(['error' => 'Token not found'])->setStatusCode(401);
          }
          $key = getenv('JWT_SECRET');
          if (!$key) {
               return service('response')->setJSON(['error' => 'JWT secret key not found'])->setStatusCode(500);
          }
          try {
               $decoded = JWT::decode($token, new Key($key, 'HS256'));
               // Optionally, set user info in request or session
          } catch (\Exception $e) {
               return service('response')->setJSON(['error' => 'Invalid or expired token'])->setStatusCode(401);
          }
          // Allow request to proceed
     }

     public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
     {
          // No post-processing needed
     }
}
