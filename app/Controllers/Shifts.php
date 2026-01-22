<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ShiftListsModel;

class Shifts extends BaseController
{
     protected $shiftListsModel;

     public function __construct()
     {
          $this->shiftListsModel = new ShiftListsModel();
     }
     // create methods for shift management here
     // create, read, update, delete shifts


     public function createShift()
     {
          // Try to get JSON, fallback to POST
          $jsonData = $this->request->getJSON(true) ?? $this->request->getPost();

          // Debug: log received data
          error_log('Received data: ' . json_encode($jsonData));

          $jsonData['split_shift'] = $jsonData['split_shift'] ?? 'N';
          $shiftStart = $jsonData['ShiftStart'];
          $shiftEnd = $jsonData['ShiftEnd'];

          // Calculate working hours rules
          $workingHoursRules = $this->calculateWorkingsHoursToBeConsiderdFullDayOrHalfDay($shiftStart, $shiftEnd);

          // Add calculated values to the shift data
          $jsonData['WorkingsHoursToBeConsiderdFullDay'] = $workingHoursRules['full_day_minimum_hours'];
          $jsonData['WorkingsHoursToBeConsiderdHalfDay'] = $workingHoursRules['half_day_minimum_hours'];
          $jsonData['late_login_applicable'] = $workingHoursRules['late_login_applicable'] ? 'Y' : 'N';

          return $this->shiftListsModel->insert($jsonData);
     }

     protected function calculateWorkingsHoursToBeConsiderdFullDayOrHalfDay($start, $end)
     {
          // Convert time strings to DateTime objects
          $startTime = new \DateTime($start);
          $endTime = new \DateTime($end);

          // Handle shifts that cross midnight
          if ($endTime < $startTime) {
               $endTime->modify('+1 day');
          }

          // Calculate total shift hours
          $interval = $startTime->diff($endTime);
          $totalHours = $interval->h + ($interval->i / 60);

          // Round to nearest 0.5 for calculation
          $totalHours = round($totalHours * 2) / 2;

          // Define rules based on total shift hours
          $rules = [
               2.0 => ['full' => 2.0, 'half' => 1.0],
               2.5 => ['full' => 2.5, 'half' => 1.25],
               3.0 => ['full' => 3.0, 'half' => 1.5],
               3.5 => ['full' => 3.5, 'half' => 1.5],
               4.0 => ['full' => 4.0, 'half' => 2.0, 'late_login_applicable' => true],
               4.5 => ['full' => 4.5, 'half' => 2.0, 'late_login_applicable' => true],
               5.0 => ['full' => 5.0, 'half' => 2.5, 'late_login_applicable' => true],
               6.0 => ['full' => 6.0, 'half' => 3.0, 'late_login_applicable' => true],
               7.0 => ['full' => 6.83, 'half' => 3.5, 'late_login_applicable' => true], // 6hrs 50min
               8.5 => ['full' => 8.33, 'half' => 4.25, 'late_login_applicable' => true], // 8hrs 20min
               9.0 => ['full' => 8.83, 'half' => 4.5, 'late_login_applicable' => true], // 8hrs 50min
               10.0 => ['full' => 9.83, 'half' => 5.0, 'late_login_applicable' => true], // 9hrs 50min
               12.0 => ['full' => 11.83, 'half' => 6.0, 'late_login_applicable' => true], // 11hrs 50min
          ];

          // Find the closest matching rule
          $closestHours = null;
          $minDiff = PHP_FLOAT_MAX;

          foreach ($rules as $ruleHours => $ruleData) {
               $diff = abs($totalHours - $ruleHours);
               if ($diff < $minDiff) {
                    $minDiff = $diff;
                    $closestHours = $ruleHours;
               }
          }

          // Get the rule for the closest match
          $rule = $rules[$closestHours] ?? ['full' => $totalHours, 'half' => $totalHours / 2];

          return [
               'total_hours' => $totalHours,
               'full_day_minimum_hours' => $rule['full'],
               'half_day_minimum_hours' => $rule['half'],
               'late_login_applicable' => $rule['late_login_applicable'] ?? false,
               'shift_start' => $start,
               'shift_end' => $end
          ];
     }


     public function getShift($id)
     {
          return $this->shiftListsModel->find($id);
     }

     public function updateShift($id, $data)
     {
          return $this->shiftListsModel->update($id, $data);
     }

     public function deleteShift($id)
     {
          return $this->shiftListsModel->delete($id);
     }
}
