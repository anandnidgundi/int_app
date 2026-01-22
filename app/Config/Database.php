<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
     /**
      * The directory that holds the Migrations and Seeds directories.
      */
     public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

     /**
      * Lets you choose which connection group to use if no other is specified.
      */
     public string $defaultGroup = 'default'; // Define the default group name

     // Default and Secondary DB group properties
     public array $default = [];
     public array $secondary = [];
     public array $travelapp = [];

     public function __construct()
     {
          parent::__construct();

          // Get the environment variable (development, production, etc.)
          $environment = ENVIRONMENT;

          if ($environment === 'development') {
               // Default database group
               $this->default = [
                    'DSN'      => '',
                    'hostname' => env('database.default.hostname', 'localhost'),
                    'username' => env('database.default.username', 'root'),
                    'password' => env('database.default.password', ''),
                    'database' => env('database.default.database', 'ci4'),
                    'DBDriver' => env('database.default.DBDriver', 'MySQLi'),
                    'DBPrefix' => '',
                    'pConnect' => false,
                    'DBDebug'  => (ENVIRONMENT !== 'production'),
                    'charset'  => 'utf8mb4',
                    'DBCollat' => 'utf8mb4_general_ci',
                    'swapPre'  => '',
                    'encrypt'  => false,
                    'compress' => false,
                    'strictOn' => false,
                    'failover' => [],
                    'port'     => (int) env('database.default.port', 3306),
               ];

               // ➕ Secondary database group
               $this->secondary = [
                    'DSN'      => '',
                    'hostname' => env('database.secondary.hostname', 'localhost'),
                    'username' => env('database.secondary.username', 'phhr_payroll'),
                    'password' => env('database.secondary.password', ''),
                    'database' => env('database.secondary.database', 'phhr_payroll'),
                    'DBDriver' => env('database.secondary.DBDriver', 'MySQLi'),
                    'DBPrefix' => '',
                    'pConnect' => false,
                    'DBDebug'  => (ENVIRONMENT !== 'production'),
                    'charset'  => 'utf8mb4',
                    'DBCollat' => 'utf8mb4_general_ci',
                    'swapPre'  => '',
                    'encrypt'  => false,
                    'compress' => false,
                    'strictOn' => false,
                    'failover' => [],
                    'port'     => (int) env('database.secondary.port', 3306),
               ];

               // ➕ Travelapp database group
               $this->travelapp = [
                    'DSN'      => '',
                    'hostname' => env('database.travelapp.hostname', 'localhost'),
                    'username' => env('database.travelapp.username', ''),
                    'password' => env('database.travelapp.password', ''),
                    'database' => env('database.travelapp.database', ''),
                    'DBDriver' => env('database.travelapp.DBDriver', 'MySQLi'),
                    'DBPrefix' => '',
                    'pConnect' => false,
                    'DBDebug'  => (ENVIRONMENT !== 'production'),
                    'charset'  => 'utf8mb4',
                    'DBCollat' => 'utf8mb4_general_ci',
                    'swapPre'  => '',
                    'encrypt'  => false,
                    'compress' => false,
                    'strictOn' => false,
                    'failover' => [],
                    'port'     => (int) env('database.travelapp.port', 3306),
               ];
          }

          // You can add more logic for production or other environments here
     }
}
