<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Services\JwtService;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
     /**
      * Instance of the main Request object.
      *
      * @var CLIRequest|IncomingRequest
      */
     protected $request;

     /**
      * An array of helpers to be loaded automatically upon
      * class instantiation. These helpers will be available
      * to all other controllers that extend BaseController.
      *
      * @var list<string>
      */
     protected $helpers = [];

     protected $userRole;
     protected $userEmpCode;

     /**
      * Be sure to declare properties for any property fetch you initialized.
      * The creation of dynamic property is deprecated in PHP 8.2.
      */
     // protected $session;

     /**
      * @return void
      */
     public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
     {
          // Do Not Edit This Line
          parent::initController($request, $response, $logger);

          // Preload any models, libraries, etc, here.

          // E.g.: $this->session = service('session');
          // Initialize userRole and userEmpCode
          $this->initializeUserData();
     }

     /**
      * Validates JWT Authorization header and returns decoded token data or error response.
      * Can be called from any controller extending BaseController.
      *
      * @return array|object Decoded token data or CI response object on error
      */
     protected function validateAuthorization()
     {

          if (!class_exists('App\\Services\\JwtService')) {
               return $this->response->setJSON(['error' => 'JwtService class not found'])->setStatusCode(500);
          }
          $authorizationHeader = $this->request->header('Authorization')?->getValue();
          $jwtService = new JwtService();
          $result = $jwtService->validateToken($authorizationHeader);

          if (isset($result['error'])) {
               return $this->response->setJSON(['error' => $result['error']])->setStatusCode($result['status'] ?? 401);
          }
          return $result['data'];
     }

     protected function validateAuthorization2()
     {
          if (!class_exists('App\\Services\\JwtService')) {
               // Throw an exception if JwtService is not found
               throw new \RuntimeException('JwtService class not found');
          }

          $authorizationHeader = $this->request->header('Authorization')?->getValue();
          $jwtService = new JwtService();
          $result = $jwtService->validateToken($authorizationHeader);

          if (isset($result['error'])) {
               // Log the error and return null if the token is invalid
               log_message('error', 'Authorization failed: ' . $result['error']);
               return null;
          }

          // Return the decoded token data
          return $result['data'] ?? null;
     }

     /**
      * Initializes userRole and userEmpCode from the JWT token.
      *
      * @return void
      */
     private function initializeUserData(): void
     {
          $authData = $this->validateAuthorization2();

          if ($authData === null) {
               // Handle the case where the token is invalid
               $this->userRole = null;
               $this->userEmpCode = null;
               return;
          }

          // Set userRole and userEmpCode globally
          $this->userRole = $authData['role'] ?? null;
          $this->userEmpCode = $authData['user_code'] ?? null;
     }

     protected function logActivity(string $action, $userId, array $details = []): void
     {
          $db = \Config\Database::connect();

          // Use validateAuthorization() to get the user data from the JWT token
          $authData = $this->validateAuthorization();
          $performedBy = is_array($authData) && isset($authData['user_code']) ? $authData['user_code'] : 'system';

          $ip = $this->request->getIPAddress();
          $agent = $this->request->getUserAgent();

          $db->table('user_activity_logs')->insert([
               'user_id'      => $userId,
               'action'       => $action,
               'performed_by' => $performedBy,
               'details'      => json_encode($details),
               'ip_address'   => $ip,
               'user_agent'   => $agent,
               'created_at'   => date('Y-m-d H:i:s'), // Add timestamp if needed
          ]);
     }
}
