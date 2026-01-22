<?php


namespace App\Controllers;

use App\Models\DutyRosterModel;
use CodeIgniter\API\ResponseTrait;
use App\Controllers\BaseController;

class SplitDutyRoster extends BaseController
{
     use ResponseTrait;
     protected $dutyRosterModel;

     public function __construct()
     {
          $this->dutyRosterModel = new DutyRosterModel();
     }

     /**
      * Get split shift attendance for a doctor/employee for a month.
      * Expects: { "employee_code": "...", "selectedMonth": "YYYY-MM" }
      */
     public function getSplitDutyAttendance()
     {
          $userDetails = $this->validateAuthorization();
          $json = $this->request->getJSON(true);

          $emp_id = $json['emp_id'] ?? null;
          $selectedMonth = $json['selectedMonth'] ?? null;
          $selectedToMonth = $json['selectedToMonth'] ?? null;

          if (empty($emp_id) || empty($selectedMonth)) {
               return $this->respond([
                    'status' => false,
                    'message' => 'emp_id and selectedMonth are required'
               ], 400);
          }

          // Get employee_code from emp_id
          $db = \Config\Database::connect('default');
          $employee = $db->table('employees')
               ->select('employee_code')
               ->where('emp_id', $emp_id)
               ->get()
               ->getRowArray();

          if (!$employee) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Employee not found'
               ], 404);
          }

          $employee_code = $employee['employee_code'];
          $attendanceData = $this->dutyRosterModel->getEmployeeSplitDutyAttendance($employee_code, $selectedMonth, $selectedToMonth);

          // Check if employee exists and has roster data
          if ($attendanceData && isset($attendanceData['roster']) && !empty($attendanceData['roster'])) {
               return $this->respond([
                    'status' => true,
                    'data' => $attendanceData,
                    'monthYear' => $selectedMonth
               ], 200);
          } else {
               return $this->respond([
                    'status' => false,
                    'message' => "No split duty attendance data found for {$selectedMonth}",
                    'debug' => [
                         'emp_id' => $emp_id,
                         'employee_code' => $employee_code,
                         'roster_count' => isset($attendanceData['roster']) ? count($attendanceData['roster']) : 0
                    ]
               ], 404);
          }
     }

     /**
      * Assign split shifts for an employee for a month.
      * Expects: { "emp_id": ..., "roster": [ { "date": "YYYY-MM-DD", "shift_id": ... }, ... ] }
      */
     public function createSplitDutyRoster()
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['user_code'];
               $json = $this->request->getJSON(true);

               if (empty($json['emp_id']) || empty($json['roster']) || !is_array($json['roster'])) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'emp_id and roster array are required'
                    ], 400);
               }

               $emp_id = $json['emp_id'];
               $roster = $json['roster'];
               $success = [];
               $failed = [];

               foreach ($roster as $entry) {
                    // Check if it's a weekoff
                    $isWeekoff = $entry['weekoff'] ?? false;

                    // Handle split shifts with type tracking
                    $shiftsData = [];
                    if (!empty($entry['shift_1_id'])) {
                         $shiftsData[] = [
                              'shift_id' => $entry['shift_1_id'],
                              'split_shift_type' => 'shift_1'
                         ];
                    }
                    if (!empty($entry['shift_2_id'])) {
                         $shiftsData[] = [
                              'shift_id' => $entry['shift_2_id'],
                              'split_shift_type' => 'shift_2'
                         ];
                    }

                    // If weekoff and no shifts, record as weekoff only
                    if ($isWeekoff && empty($shiftsData)) {
                         $data = [
                              'emp_id' => $emp_id,
                              'shift_id' => null,
                              'attendance_date' => $entry['date'],
                              'custom_weekoff_date' => $entry['date'],
                              'split_shift_type' => null,
                              'createdBy' => $user,
                              'createdDTM' => date('Y-m-d H:i:s')
                         ];

                         // Delete existing shifts for this date (weekoff overrides shifts)
                         $this->dutyRosterModel
                              ->where('emp_id', $emp_id)
                              ->where('attendance_date', $entry['date'])
                              ->delete();

                         if ($this->dutyRosterModel->insert($data)) {
                              $success[] = ['date' => $entry['date'], 'action' => 'weekoff_recorded'];
                         } else {
                              $failed[] = ['date' => $entry['date'], 'reason' => 'weekoff_insert_failed'];
                         }
                         continue;
                    }

                    // If no shifts and not a weekoff, skip
                    if (empty($shiftsData)) {
                         $failed[] = ['date' => $entry['date'], 'reason' => 'no_shifts_specified'];
                         continue;
                    }

                    // Clear weekoff if shifts are assigned
                    if (!$isWeekoff) {
                         $this->dutyRosterModel
                              ->where('emp_id', $emp_id)
                              ->where('attendance_date', $entry['date'])
                              ->where('shift_id', null)
                              ->delete();
                    }

                    // Process each shift separately with type
                    foreach ($shiftsData as $shiftData) {
                         $data = [
                              'emp_id' => $emp_id,
                              'shift_id' => $shiftData['shift_id'],
                              'attendance_date' => $entry['date'],
                              'custom_weekoff_date' => null,
                              'split_shift_type' => $shiftData['split_shift_type'],
                              'createdBy' => $user,
                              'createdDTM' => date('Y-m-d H:i:s')
                         ];

                         // Check for existing by emp_id + date + shift_id + split_shift_type
                         $existing = $this->dutyRosterModel
                              ->where('emp_id', $emp_id)
                              ->where('attendance_date', $entry['date'])
                              ->where('shift_id', $shiftData['shift_id'])
                              ->where('split_shift_type', $shiftData['split_shift_type'])
                              ->first();

                         if ($existing) {
                              $updateData = $data;
                              $updateData['updatedBy'] = $user;
                              $updateData['updatedDTM'] = date('Y-m-d H:i:s');
                              if ($this->dutyRosterModel->update($existing['id'], $updateData)) {
                                   $success[] = [
                                        'date' => $entry['date'],
                                        'shift_id' => $shiftData['shift_id'],
                                        'shift_type' => $shiftData['split_shift_type'],
                                        'action' => 'updated'
                                   ];
                              } else {
                                   $failed[] = [
                                        'date' => $entry['date'],
                                        'shift_id' => $shiftData['shift_id'],
                                        'shift_type' => $shiftData['split_shift_type'],
                                        'action' => 'update_failed'
                                   ];
                              }
                         } else {
                              if ($this->dutyRosterModel->insert($data)) {
                                   $success[] = [
                                        'date' => $entry['date'],
                                        'shift_id' => $shiftData['shift_id'],
                                        'shift_type' => $shiftData['split_shift_type'],
                                        'action' => 'created'
                                   ];
                              } else {
                                   $failed[] = [
                                        'date' => $entry['date'],
                                        'shift_id' => $shiftData['shift_id'],
                                        'shift_type' => $shiftData['split_shift_type'],
                                        'action' => 'create_failed'
                                   ];
                              }
                         }
                    }
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'Split duty roster processed',
                    'success' => $success,
                    'failed' => $failed
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }



     public function updateSplitDutyRoster()
     {
          try {
               $userDetails = $this->validateAuthorization();
               $user = $userDetails['user_code'];
               $json = $this->request->getJSON(true);

               if (empty($json['emp_id']) || empty($json['roster']) || !is_array($json['roster'])) {
                    return $this->respond([
                         'status' => false,
                         'message' => 'emp_id and roster array are required'
                    ], 400);
               }

               $emp_id = $json['emp_id'];
               $roster = $json['roster'];
               $success = [];
               $failed = [];

               foreach ($roster as $entry) {
                    // Check if it's a weekoff
                    $isWeekoff = $entry['weekoff'] ?? false;

                    // Prepare shiftsData with split_shift_type
                    $shiftsData = [];
                    if (!empty($entry['shift_1_id'])) {
                         $shiftsData[] = [
                              'shift_id' => $entry['shift_1_id'],
                              'split_shift_type' => 'shift_1'
                         ];
                    }
                    if (!empty($entry['shift_2_id'])) {
                         $shiftsData[] = [
                              'shift_id' => $entry['shift_2_id'],
                              'split_shift_type' => 'shift_2'
                         ];
                    }

                    // Delete all existing entries for this date first
                    $this->dutyRosterModel
                         ->where('emp_id', $emp_id)
                         ->where('attendance_date', $entry['date'])
                         ->delete();

                    // If weekoff and no shifts, record as weekoff only
                    if ($isWeekoff && empty($shiftsData)) {
                         $data = [
                              'emp_id' => $emp_id,
                              'shift_id' => null,
                              'attendance_date' => $entry['date'],
                              'custom_weekoff_date' => $entry['date'],
                              'split_shift_type' => null,
                              'createdBy' => $user,
                              'createdDTM' => date('Y-m-d H:i:s')
                         ];

                         if ($this->dutyRosterModel->insert($data)) {
                              $success[] = ['date' => $entry['date'], 'action' => 'weekoff_updated'];
                         } else {
                              $failed[] = ['date' => $entry['date'], 'reason' => 'weekoff_update_failed'];
                         }
                         continue;
                    }

                    // If no shifts and not a weekoff, skip
                    if (empty($shiftsData)) {
                         $failed[] = ['date' => $entry['date'], 'reason' => 'no_shifts_specified'];
                         continue;
                    }

                    // Insert new shifts with correct split_shift_type
                    foreach ($shiftsData as $shiftData) {
                         $data = [
                              'emp_id' => $emp_id,
                              'shift_id' => $shiftData['shift_id'],
                              'attendance_date' => $entry['date'],
                              'custom_weekoff_date' => null,
                              'split_shift_type' => $shiftData['split_shift_type'],
                              'createdBy' => $user,
                              'createdDTM' => date('Y-m-d H:i:s')
                         ];

                         if ($this->dutyRosterModel->insert($data)) {
                              $success[] = [
                                   'date' => $entry['date'],
                                   'shift_id' => $shiftData['shift_id'],
                                   'split_shift_type' => $shiftData['split_shift_type'],
                                   'action' => 'updated'
                              ];
                         } else {
                              $failed[] = [
                                   'date' => $entry['date'],
                                   'shift_id' => $shiftData['shift_id'],
                                   'split_shift_type' => $shiftData['split_shift_type'],
                                   'action' => 'update_failed'
                              ];
                         }
                    }
               }

               return $this->respond([
                    'status' => true,
                    'message' => 'Split duty roster updated successfully',
                    'success' => $success,
                    'failed' => $failed
               ], 200);
          } catch (\Exception $e) {
               return $this->respond([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
               ], 500);
          }
     }

     // public function updateSplitDutyRoster()
     // {
     //      try {
     //           $userDetails = $this->validateAuthorization();
     //           $user = $userDetails['user_code'];
     //           $json = $this->request->getJSON(true);

     //           if (empty($json['emp_id']) || empty($json['roster']) || !is_array($json['roster'])) {
     //                return $this->respond([
     //                     'status' => false,
     //                     'message' => 'emp_id and roster array are required'
     //                ], 400);
     //           }

     //           $emp_id = $json['emp_id'];
     //           $roster = $json['roster'];
     //           $success = [];
     //           $failed = [];

     //           foreach ($roster as $entry) {
     //                // Check if it's a weekoff
     //                $isWeekoff = $entry['weekoff'] ?? false;

     //                // Handle split shifts (shift_1_id and shift_2_id)
     //                $shifts = [];
     //                if (!empty($entry['shift_1_id'])) {
     //                     $shifts[] = $entry['shift_1_id'];
     //                }
     //                if (!empty($entry['shift_2_id'])) {
     //                     $shifts[] = $entry['shift_2_id'];
     //                }

     //                // Delete all existing entries for this date first
     //                $this->dutyRosterModel
     //                     ->where('emp_id', $emp_id)
     //                     ->where('attendance_date', $entry['date'])
     //                     ->delete();

     //                $split_shift_type = null;
     //                if (!empty($entry['shift_1_id']) && !empty($entry['shift_2_id'])) {
     //                     $split_shift_type = 'both';
     //                } elseif (!empty($entry['shift_1_id'])) {
     //                     $split_shift_type = 'shift_1';
     //                } elseif (!empty($entry['shift_2_id'])) {
     //                     $split_shift_type = 'shift_2';
     //                }

     //                // If weekoff and no shifts, record as weekoff only
     //                if ($isWeekoff && empty($shifts)) {
     //                     $data = [
     //                          'emp_id' => $emp_id,
     //                          'shift_id' => null,
     //                          'attendance_date' => $entry['date'],
     //                          'custom_weekoff_date' => $entry['date'],
     //                          'createdBy' => $user,
     //                          'createdDTM' => date('Y-m-d H:i:s')
     //                     ];

     //                     if ($this->dutyRosterModel->insert($data)) {
     //                          $success[] = ['date' => $entry['date'], 'action' => 'weekoff_updated'];
     //                     } else {
     //                          $failed[] = ['date' => $entry['date'], 'reason' => 'weekoff_update_failed'];
     //                     }
     //                     continue;
     //                }

     //                // If no shifts and not a weekoff, skip
     //                if (empty($shifts)) {
     //                     $failed[] = ['date' => $entry['date'], 'reason' => 'no_shifts_specified'];
     //                     continue;
     //                }

     //                // Insert new shifts
     //                foreach ($shifts as $shift_id) {
     //                     $data = [
     //                          'emp_id' => $emp_id,
     //                          'shift_id' => $shift_id,
     //                          'attendance_date' => $entry['date'],
     //                          'custom_weekoff_date' => $isWeekoff ? $entry['date'] : null,
     //                          'split_shift_type' => $split_shift_type, // e.g. 'shift_1' or 'shift_2'
     //                          'createdBy' => $user,
     //                          'createdDTM' => date('Y-m-d H:i:s')
     //                     ];

     //                     if ($this->dutyRosterModel->insert($data)) {
     //                          $success[] = ['date' => $entry['date'], 'shift_id' => $shift_id, 'action' => 'updated'];
     //                     } else {
     //                          $failed[] = ['date' => $entry['date'], 'shift_id' => $shift_id, 'action' => 'update_failed'];
     //                     }
     //                }
     //           }

     //           return $this->respond([
     //                'status' => true,
     //                'message' => 'Split duty roster updated successfully',
     //                'success' => $success,
     //                'failed' => $failed
     //           ], 200);
     //      } catch (\Exception $e) {
     //           return $this->respond([
     //                'status' => false,
     //                'message' => 'Error: ' . $e->getMessage()
     //           ], 500);
     //      }
     // }


     /**
      * Helper to process punches for a shift window.
      */
     private function processShiftPunches($allPunches, $shiftIn, $shiftOut)
     {
          // Remove duplicates
          $allPunches = array_unique($allPunches);

          // Filter punches within shift window
          $shiftPunches = array_filter($allPunches, function ($p) use ($shiftIn, $shiftOut) {
               return $p >= $shiftIn && $p <= $shiftOut;
          });

          // Sort punches
          sort($shiftPunches);

          // Pair punches as in/out
          $workedSeconds = 0;
          $pairedPunches = [];
          $count = count($shiftPunches);

          for ($i = 0; $i < $count - 1; $i += 2) {
               $in = $shiftPunches[$i];
               $out = $shiftPunches[$i + 1];
               $workedSeconds += strtotime($out) - strtotime($in);
               $pairedPunches[] = [$in, $out];
          }

          // If odd number of punches, last punch is unpaired (missed punch)
          $missedPunch = ($count % 2 !== 0) ? $shiftPunches[$count - 1] : null;

          return [
               'worked_seconds' => $workedSeconds,
               'paired_punches' => $pairedPunches,
               'missed_punch'   => $missedPunch,
               'all_punches'    => array_values($shiftPunches)
          ];
     }
}
