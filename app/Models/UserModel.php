<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
     // Use default DB group
     protected $table            = 'users';
     protected $primaryKey       = 'id';
     protected $useAutoIncrement = true;
     protected $returnType       = 'array';
     protected $useSoftDeletes   = false;
     protected $protectFields    = true;

     protected $allowedFields = [
          'user_code',
          'user_name',
          'password',
          'status',
          'disabled',
          'validity',
          'failed_attems',
          'is_admin',
          'exit_date',
          'role',
     ];

     protected bool $allowEmptyInserts = false;

     // Dates
     protected $useTimestamps = false;
     // No createdField/updatedField in doctors table

     // Validation
     protected $validationRules      = [];
     protected $validationMessages   = [];
     protected $skipValidation       = false;
     protected $cleanValidationRules = true;

     // Callbacks
     protected $allowCallbacks = true;
     protected $beforeInsert   = [];
     protected $afterInsert    = [];
     protected $beforeUpdate   = [];
     protected $afterUpdate    = [];
     protected $beforeFind     = [];
     protected $afterFind      = [];
     protected $beforeDelete   = [];
     protected $afterDelete    = [];



     public function BM_DashboardCount($user, $role, $selectedMonth, $selectedBranch)
     {
          $db2 = \Config\Database::connect('default');
          // log_message('error', "selectedMonth: {$selectedMonth}");
          // log_message('error', "selectedBranch: {$selectedBranch}");
          // echo $selectedBranch;die();
          $morningTasksCount = $db2->table('bm_tasks')
               ->where('branch', $selectedBranch)
               ->where('DATE_FORMAT(taskDate, "%Y-%m")', $selectedMonth)
               ->countAllResults();

          $powerConsumptionCount = $db2->table('power_consumption')
               ->where('branch_id', $selectedBranch)
               ->where('DATE_FORMAT(consumption_date, "%Y-%m")', $selectedMonth)
               ->countAllResults();

          $dieselConsumptionCount = $db2->table('diesel_consumption')
               ->where('branch_id', $selectedBranch)
               ->where('DATE_FORMAT(consumption_date, "%Y-%m")', $selectedMonth)
               ->countAllResults();

          $bmDashboardCount = [
               'morningTasksCount' => $morningTasksCount,
               'powerConsumptionCount' => $powerConsumptionCount,
               'dieselConsumptionCount' => $dieselConsumptionCount
          ];
          return $bmDashboardCount;
     }

     public function CM_DashboardCount($user, $role, $selectedMonth, $selectedBranch, $selectedCluster)
     {

          $db2 = \Config\Database::connect('default');
          // Get branch IDs for the selected cluster
          $branchIds = [];

          if ($selectedCluster > 0) {
               $branchIds = $db2->table('user_map as cb')
                    ->select('cb.*')
                    ->where('cb.branches', $selectedBranch)
                    ->where('cb.cluster', $selectedCluster)
                    // ->where('cb.zone', $selectedZone)
                    ->get()
                    ->getResultArray();

               //    $branchIds = array_column($branchIds, 'branches');


          } else if ($selectedBranch === 0  && $selectedCluster === 0) {
               $branchIds = [];
          }

          // Count BM Tasks
          $builder = $db2->table('bm_tasks');

          if ($selectedBranch > 0) {
               $builder->where('branch', $selectedBranch);
          } else if (!empty($branchIds)) {
               $builder->whereIn('branch', $branchIds);
          }
          $bmTasksCount = $builder->where('DATE_FORMAT(taskDate, "%Y-%m")', $selectedMonth)
               ->countAllResults();



          // Count Power Consumption
          $builder = $db2->table('power_consumption');
          if ($selectedBranch > 0) {
               $builder->where('branch_id', $selectedBranch);
          } else if (!empty($branchIds)) {
               $builder->whereIn('branch_id', $branchIds);
          }
          $powerConsumptionCount = $builder->where('DATE_FORMAT(consumption_date, "%Y-%m")', $selectedMonth)
               ->countAllResults();

          // Count Diesel Consumption
          $builder = $db2->table('diesel_consumption');
          if ($selectedBranch > 0) {
               $builder->where('branch_id', $selectedBranch);
          } else if (!empty($branchIds)) {
               $builder->whereIn('branch_id', $branchIds);
          }
          $dieselConsumptionCount = $builder->where('DATE_FORMAT(consumption_date, "%Y-%m")', $selectedMonth)
               ->countAllResults();

          return [
               'bmTasksCount' => $bmTasksCount,
               'powerConsumptionCount' => $powerConsumptionCount,
               'dieselConsumptionCount' => $dieselConsumptionCount
          ];
     }

     public function CM_DashboardCount_old($user, $role, $selectedMonth, $selectedBranch, $selectedCluster)
     {
          $db2 = \Config\Database::connect('default');
          // Get branch IDs for the selected cluster
          $branchIds = [];
          if ($selectedCluster > 0) {
               $branchIds = $db2->table('cluster_branch_map as cb')
                    ->select('cb.branch_id')
                    ->where('cb.cluster_id', $selectedCluster)
                    ->get()
                    ->getResultArray();
               $branchIds = array_column($branchIds, 'branch_id');
          } else if ($selectedBranch === 0  && $selectedCluster === 0) {
               $branchIds = [];
          }

          // Count BM Tasks
          $builder = $db2->table('bm_tasks');
          if ($selectedBranch > 0) {
               $builder->where('branch', $selectedBranch);
          } else if (!empty($branchIds)) {
               $builder->whereIn('branch', $branchIds);
          }
          $bmTasksCount = $builder->where('DATE_FORMAT(taskDate, "%Y-%m")', $selectedMonth)
               ->countAllResults();

          // Count Power Consumption
          $builder = $db2->table('power_consumption');
          if ($selectedBranch > 0) {
               $builder->where('branch_id', $selectedBranch);
          } else if (!empty($branchIds)) {
               $builder->whereIn('branch_id', $branchIds);
          }
          $powerConsumptionCount = $builder->where('DATE_FORMAT(consumption_date, "%Y-%m")', $selectedMonth)
               ->countAllResults();

          // Count Diesel Consumption
          $builder = $db2->table('diesel_consumption');
          if ($selectedBranch > 0) {
               $builder->where('branch_id', $selectedBranch);
          } else if (!empty($branchIds)) {
               $builder->whereIn('branch_id', $branchIds);
          }
          $dieselConsumptionCount = $builder->where('DATE_FORMAT(consumption_date, "%Y-%m")', $selectedMonth)
               ->countAllResults();

          return [
               'bmTasksCount' => $bmTasksCount,
               'powerConsumptionCount' => $powerConsumptionCount,
               'dieselConsumptionCount' => $dieselConsumptionCount
          ];
     }

     // public function CM_DashboardCount($user, $role, $selectedMonth)
     // {

     //     // Fetch the user's branch IDs
     //     $branch  = $this->getCMUserBranchList($user, $role);

     //     // Convert the branch IDs to strings
     //     $branchIds = array_map('strval', $branch);

     //     $morningTasksCount = $this->db->table('morningtasks')
     //         ->whereIn('branch', $branchIds)
     //         ->where('DATE_FORMAT(taskDate, "%Y-%m")', $selectedMonth)
     //         ->countAllResults();

     //     $nightTasksCount = $this->db->table('nighttasks')
     //         ->whereIn('branch', $branchIds)
     //         ->where('DATE_FORMAT(taskDate, "%Y-%m")', $selectedMonth)
     //         ->countAllResults();

     //     $cm_morningTasksCount = $this->db->table('cm_morning_tasks')
     //         ->where('created_by', $user)
     //         ->where('DATE_FORMAT(taskDate, "%Y-%m")', $selectedMonth)
     //         ->countAllResults();

     //     $cm_nightTasksCount = $this->db->table('cm_night_tasks')
     //         ->where('created_by', $user)
     //         ->where('DATE_FORMAT(taskDate, "%Y-%m")', $selectedMonth)
     //         ->countAllResults();

     //     $bmDashboardCount = [
     //         'bm_morningTasksCount' => $morningTasksCount,
     //         'bm_nightTasksCount' => $nightTasksCount,
     //         'cm_morningTasksCount' => $cm_morningTasksCount,
     //         'cm_nightTasksCount' => $cm_nightTasksCount
     //     ];
     //     return $bmDashboardCount;
     // }

     public function getCMUserBranchList($user, $role)
     {
          $db2 = \Config\Database::connect('default');
          $b = $db2->table('branchesmapped as b')
               ->select('b.cluster_id')
               ->where('emp_code', $user)
               ->get();
          $b2 = $b->getRowArray();
          $cluster_id = $b2['cluster_id'];

          $builder = $db2->table('cluster_branch_map as cl')
               ->select('cl.branch_id as branch_id,  ') // Select only branch_id
               ->join('branches as b', 'cl.branch_id = b.branch_id', 'left'); // Use 'inner' join if needed

          // Apply condition only if the role is not 'SUPER_ADMIN'
          if ($role != 'SUPER_ADMIN') {
               $builder->where('cl.cluster_id', $cluster_id);
          }

          // Execute the query and return branch_ids
          $query = $builder->get();
          $branchIds = array_column($query->getResultArray(), 'branch_id');

          // Log the fetched branch IDs
          log_message('error', "Branch IDs fetched from getUserBranchList: " . print_r($branchIds, true));

          // If no branches are found, log it and return empty array
          if (empty($branchIds)) {
               log_message('error', "No branches found for user {$user} with role {$role}");
          }

          return $branchIds; // Return only the branch_ids
     }


     public function getZoneClusterBranchesTree($zone_id)
     {
          $db2 = \Config\Database::connect('default');
          // Get Zone Information
          $zone = $db2->table('zones as z')
               ->where('z.z_id', $zone_id)
               ->get()
               ->getRowArray(); // Assuming a single zone is returned

          if (!$zone) {
               return []; // Return empty array if no zone is found
          }

          // Get Clusters in the Zone
          $clusterList = $db2->table('cluster_zone_map as cz')
               ->select('cz.cluster_id, c.cluster')
               ->join('clusters as c', 'c.cluster_id = cz.cluster_id', 'left')
               ->where('cz.zone_id', $zone_id)
               ->get()
               ->getResultArray();

          // Extract Cluster IDs
          $clustersID = array_column($clusterList, 'cluster_id');

          // Check if there are any cluster IDs to avoid empty WHERE IN clause
          if (empty($clustersID)) {
               // No clusters found, add clusters as an empty array and return the zone data
               $zone['clusters'] = [];
               return $zone;
          }

          // Get Branches in each Cluster
          $branchLists = $db2->table('cluster_branch_map as cb')
               ->select('cb.cluster_id, cb.branch_id, b.branch')
               ->join('branches as b', 'b.branch_id = cb.branch_id', 'left')
               ->whereIn('cb.cluster_id', $clustersID)
               ->get()
               ->getResultArray();

          // Organize branches by cluster_id
          $branchesByCluster = [];
          foreach ($branchLists as $branch) {
               $branchesByCluster[$branch['cluster_id']][] = [
                    'branch_id' => $branch['branch_id'],
                    'branch' => $branch['branch']
               ];
          }

          // Add branches to each cluster
          foreach ($clusterList as &$cluster) {
               $cluster['branches'] = $branchesByCluster[$cluster['cluster_id']] ?? [];
          }

          // Add clusters to the zone
          $zone['clusters'] = $clusterList;

          return $zone;
     }


     public function getUsersList()
     {

          try {
               // Fetch all employee details along with the role in one query
               $employees = $this->db->table('new_emp_master as a')
                    ->select('a.*, b.role')
                    ->join('bmcm as b', 'b.emp_code = a.emp_code', 'left')
                    ->where('a.active', 'Active')
                    ->orderBy('b.role', 'asc')
                    ->get()
                    ->getResultArray();

               // Gather employee codes for branch list query
               $empCodes = array_column($employees, 'emp_code');
               $db2 = \Config\Database::connect('default');
               // Get branch details for these employee codes in one query
               $branchLists = $db2->table('branchesmapped as bm')
                    ->select('bm.emp_code, bm.branch_id, b.branch, bm.cluster_id, c.cluster, bm.zone_id,z.zone')
                    ->join('branches as b', 'bm.branch_id = b.branch_id', 'left')
                    ->join('clusters as c', 'c.cluster_id = bm.cluster_id', 'left')
                    ->join('zones as z', 'z.z_id = bm.zone_id', 'left')
                    ->whereIn('bm.emp_code', $empCodes)
                    ->get()
                    ->getResultArray();

               // Map branches by emp_code for easier assignment
               $branchesByEmpCode = [];
               foreach ($branchLists as $branch) {
                    $branchesByEmpCode[$branch['emp_code']][] = $branch;
               }

               // Assign branch lists to corresponding employees
               foreach ($employees as &$employee) {
                    $employee['userBranchList'] = $branchesByEmpCode[$employee['emp_code']] ?? [];
               }

               return $employees;
          } catch (\Exception $e) {
               log_message('error', 'Database query failed in getUsersList: ' . $e->getMessage());
               return [];
          }
     }

     public function addClusterToZone($data, $zone_id, $cluster_id)
     {

          $db2 = \Config\Database::connect('default');
          $db2->transStart();
          // Prepare query to check if cluster_id already exists in the table with optional zone filter
          $builder = $db2->table('cluster_zone_map')
               ->where('cluster_id', $cluster_id);

          if ($zone_id > 0) {
               $builder->where('zone_id', $zone_id);
          }

          $existingRecord = $builder->get()->getRowArray();

          if ($existingRecord) {
               // If cluster_id exists, perform an update
               $builder = $db2->table('cluster_zone_map')->where('cluster_id', $cluster_id);

               if ($zone_id > 0) {
                    $builder->where('zone_id', $zone_id);
               }

               log_message('debug', 'Attempting to add cluster assignment in model for cluster_id: ' . $cluster_id);

               if (!$builder->update($data)) {
                    $error = $db2->error();
                    log_message('error', 'Database error during update in cluster_zone_map: ' . json_encode($error));
                    $db2->transRollback();
                    return false;
               }
          } else {

               log_message('debug', 'Entered addClusterToZone for cluster_id: ' . $cluster_id);

               // If cluster_id does not exist, perform an insert
               if (!$db2->table('cluster_zone_map')->insert($data)) {
                    $error = $db2->error();
                    log_message('error', 'Failed to insert data into cluster_zone_map table: ' . print_r($error, true));
                    $db2->transRollback();
                    return false;
               }
          }

          $db2->transComplete();
          if (!$db2->transStatus()) {
               log_message('error', 'Failed transaction for cluster_id: ' . $cluster_id);
          }

          return $db2->transStatus();
     }

     public function addBranchToCluster($data, $cluster_id, $branch_id)
     {

          $db2 = \Config\Database::connect('default');
          $db2->transStart();
          // Prepare query to check if branch_id already exists in the table with optional cluster filter
          $builder = $db2->table('cluster_branch_map')
               ->where('branch_id', $branch_id);

          if ($cluster_id > 0) {
               $builder->where('cluster_id', $cluster_id);
          }

          $existingRecord = $builder->get()->getRowArray();

          if ($existingRecord) {
               // If branch_id exists, perform an update
               $builder = $db2->table('cluster_branch_map')->where('branch_id', $branch_id);

               if ($cluster_id > 0) {
                    $builder->where('cluster_id', $cluster_id);
               }

               log_message('error', 'Attempting to add branch assignment in model for branch_id: ' . $branch_id);

               if (!$builder->update($data)) {
                    $error = $db2->error();
                    log_message('error', 'Database error during update in cluster_branch_map: ' . json_encode($error));
                    $db2->transRollback();
                    return false;
               }
          } else {

               log_message('error', 'Entered addBranchToCluster for branch_id: ' . $branch_id);

               // If branch_id does not exist, perform an insert
               if (!$db2->table('cluster_branch_map')->insert($data)) {
                    $error = $db2->error();
                    log_message('error', 'Failed to insert data into cluster_branch_map table: ' . print_r($error, true));
                    $db2->transRollback();
                    return false;
               }
          }

          $db2->transComplete();
          if (!$db2->transStatus()) {
               log_message('error', 'Failed transaction for branch_id: ' . $branch_id);
          }

          return $db2->transStatus();
     }

     // public function getClusterBranchList($cluster_id)
     // {
     //     $db2 = \Config\Database::connect('default');
     //     $builder = $db2->table('cluster_branch_map as cb')
     //         ->select('b.branch, cb.branch_id')
     //         ->join('branches as b', 'b.branch_id = cb.branch_id', 'left');
     //     $builder->where('cb.cluster_id', $cluster_id);

     //     // Log the SQL query before executing it
     //     // log_message('error', 'SQL Query: ' . $this->db->getLastQuery());

     //     $query = $builder->get();
     //     $result = $query->getResultArray();

     //     // Log the result of the query
     //     // log_message('error', 'Cluster Branch List Result: ' . json_encode($result));

     //     return $result;
     // }

     public function getClusterBranchList($cluster_id)
     {
          try {
               $db2 = \Config\Database::connect('default');
               if (!$db2) {
                    log_message('error', 'Failed to connect to default database.');
                    return [];
               }

               $builder = $db2->table('cluster_branch_map as cb')
                    ->select('b.branch, cb.branch_id')
                    ->join('branches as b', 'b.branch_id = cb.branch_id', 'left')
                    ->where('cb.cluster_id', $cluster_id);

               $query = $builder->get();
               $result = $query->getResultArray();

               log_message('debug', 'Cluster Branch List Result: ' . json_encode($result));
               return $result;
          } catch (\Exception $e) {
               log_message('error', 'Database error in getClusterBranchList: ' . $e->getMessage());
               return [];
          }
     }

     // public function getZoneClusterList($zone_id)
     // {
     //     $db2 = \Config\Database::connect('default');

     //     $builder = $db2->table('cluster_zone_map as cz')
     //         ->select('c.cluster, cz.cluster_id')
     //         ->join('clusters as c', 'c.cluster_id = cz.cluster_id', 'left');
     //     $builder->where('cz.zone_id', $zone_id);

     //     // Log the SQL query before executing it
     //     log_message('error', 'SQL Query: ' . $db2->getLastQuery());

     //     $query = $builder->get();
     //     $result = $query->getResultArray();

     //     // Log the result of the query
     //     log_message('error', 'Zone Cluster List Result: ' . json_encode($result));

     //     return $result;
     // }

     // fetch zones list, clusters list, branches list from user_map table
     public function getUserwiseBranchClusterZoneList($user, $role)
     {
          $db2 = \Config\Database::connect('default');
          $secondaryDB = \Config\Database::connect('secondary');

          // For AVP or SUPER_ADMIN roles, fetch all data
          if ($role === 'AVP' || $role === 'SUPER_ADMIN') {
               $zones = $db2->table('zones')
                    ->select('z_id, zone')
                    ->where('status', 'A')
                    ->get()
                    ->getResultArray();

               $clusters = $db2->table('clusters')
                    ->select('cluster_id, cluster')
                    ->where('status', 'A')
                    ->get()
                    ->getResultArray();

               $branches = [];
               try {
                    $branches = $secondaryDB->table('Branches')
                         ->select('id, SysField as branch_name')
                         ->where('Status', 'A')
                         ->orderBy('id', 'ASC')
                         ->get()
                         ->getResultArray();
               } catch (\Exception $e) {
                    log_message('error', 'Error fetching branches: ' . $e->getMessage());
               }

               return [
                    'zones' => $zones,
                    'clusters' => $clusters,
                    'branches' => $branches
               ];
          }

          // For other roles, fetch based on user mapping
          $userMap = $db2->table('user_map')
               ->where('emp_code', $user)
               ->get()
               ->getRowArray();

          if (!$userMap) {
               return [
                    'zones' => [],
                    'clusters' => [],
                    'branches' => []
               ];
          }

          // Parse IDs from comma-separated strings
          $zoneIds = !empty($userMap['zone']) ? explode(',', $userMap['zone']) : [];
          $clusterIds = !empty($userMap['cluster']) ? explode(',', $userMap['cluster']) : [];
          $branchIds = !empty($userMap['branches']) ? explode(',', $userMap['branches']) : [];

          // Fetch zone details
          $zones = [];
          if (!empty($zoneIds)) {
               $zones = $db2->table('zones')
                    ->select('z_id, zone')
                    ->whereIn('z_id', $zoneIds)
                    ->where('status', 'A')
                    ->get()
                    ->getResultArray();
          }

          // Fetch cluster details
          $clusters = [];
          if (!empty($clusterIds)) {
               $clusters = $db2->table('clusters')
                    ->select('cluster_id, cluster')
                    ->whereIn('cluster_id', $clusterIds)
                    ->where('status', 'A')
                    ->get()
                    ->getResultArray();
          }

          // Fetch branch details
          $branches = [];
          if (!empty($branchIds)) {
               try {
                    $branches = $secondaryDB->table('Branches')
                         ->select('id, SysField as branch_name')
                         ->whereIn('id', $branchIds)
                         ->where('Status', 'A')
                         ->get()
                         ->getResultArray();
               } catch (\Exception $e) {
                    log_message('error', 'Error fetching branches: ' . $e->getMessage());
               }
          }

          return [
               'zones' => $zones,
               'clusters' => $clusters,
               'branches' => $branches
          ];
     }

     public function getCMclusterList($user, $role)
     {
          $db2 = \Config\Database::connect('default');
          $builder = $db2->table('branchesmapped as bm')
               ->select('bm.emp_code,  bm.cluster_id, bm.cluster');
          // ->join('clust_area_map as cl', 'cl.cluster_id = bm.cluster_id', 'left')
          // ->join('area as a', 'cl.area_id = a.id', 'left');

          // Apply condition only if the role is not 'SUPER_ADMIN'
          if ($role != 'SUPER_ADMIN') {
               $builder->where('bm.emp_code', $user);
          }

          $query = $builder->get();
          return $query->getResultArray();
     }

     public function getUserZones($user, $role)
     {
          $db2 = \Config\Database::connect('default');

          if ($role !== 'BM') {
               // Step 1: Get user map data
               $userMapData = $db2->table('user_map')
                    ->select('zone, cluster, branches')
                    ->where('emp_code', $user)
                    ->get()
                    ->getResultArray();

               $allZoneIDs    = [];
               $allClusterIDs = [];
               $allBranchIDs  = [];

               foreach ($userMapData as $row) {
                    // Zones (comma-separated zone IDs)
                    if (!empty($row['zone'])) {
                         $zoneIDs = array_map('trim', explode(',', $row['zone']));
                         $allZoneIDs = array_merge($allZoneIDs, $zoneIDs);
                    }

                    // Clusters
                    if (!empty($row['cluster'])) {
                         $clusterIDs = array_map('trim', explode(',', $row['cluster']));
                         $allClusterIDs = array_merge($allClusterIDs, $clusterIDs);
                    }

                    // Branches
                    if (!empty($row['branches'])) {
                         $branchIDs = array_map('trim', explode(',', $row['branches']));
                         $allBranchIDs = array_merge($allBranchIDs, $branchIDs);
                    }
               }




               // Deduplicate
               $allZoneIDs    = array_unique($allZoneIDs);
               $allClusterIDs = array_unique($allClusterIDs);
               $allBranchIDs  = array_unique($allBranchIDs);

               // Step 2: Fetch zones from IDs
               $zones = [];
               if (!empty($allZoneIDs)) {
                    $zones = $db2->table('zones')
                         ->select('z_id, zone as z_zone')
                         ->whereIn('z_id', $allZoneIDs)
                         ->get()
                         ->getResultArray();
               }

               // Step 3: Fetch cluster details
               $clusters = [];
               if (!empty($allClusterIDs)) {
                    $clusters = $db2->table('clusters')
                         ->whereIn('cluster_id', $allClusterIDs)
                         ->get()
                         ->getResultArray();
               }

               // Step 4: Fetch branches and build a lookup
               $branchesList = [];
               $branchNames  = [];
               if (!empty($allBranchIDs)) {
                    $branches = $this->db->table('Branches') // <- use default DB
                         ->select('id, SysField')
                         ->whereIn('id', $allBranchIDs)
                         ->get()
                         ->getResultArray();

                    foreach ($branches as $branch) {
                         $branchesList[] = [
                              'b_id'   => $branch['id'],
                              'b_name' => $branch['SysField'],
                         ];
                         $branchNames[$branch['id']] = $branch['SysField'];
                    }
               }

               // Step 5: Replace branch IDs in clusters with names
               foreach ($clusters as &$cluster) {
                    $clusterBranches = explode(',', $cluster['branches']);
                    $branchNamesList = [];

                    foreach ($clusterBranches as $branchID) {
                         $branchID = trim($branchID);
                         if (isset($branchNames[$branchID])) {
                              $branchNamesList[] = $branchNames[$branchID];
                         }
                    }

                    $cluster['branches'] = implode(', ', $branchNamesList);
               }

               // Step 6: Return full response
               return [
                    'STATUS'   => true,
                    'message'  => 'Data fetched successfully',
                    'zones'    => $zones,
                    'clusters' => $clusters,
                    'branches' => $branchesList
               ];
          } else {
               return $this->response->setJSON([
                    'STATUS'  => false,
                    'message' => 'Access denied for BM role',
               ]);
          }
     }





     public function getUserZonesAVP($user, $role)
     {
          $db2 = \Config\Database::connect('default');
          $secondaryDB = \Config\Database::connect('secondary'); // For Branches table

          // If role is BM, deny access
          if ($role === 'BM') {
               return [
                    'STATUS'  => false,
                    'message' => 'Access denied for BM role'
               ];
          }

          // If role is AVP, return ALL zones, clusters, branches
          if ($role === 'AVP') {
               $zones = $db2->table('zones')
                    ->select('z_id, zone')
                    ->get()
                    ->getResultArray();

               $clusters = $db2->table('clusters')
                    ->get()
                    ->getResultArray();

               $branches = $secondaryDB->table('Branches')
                    ->select('id, SysField')
                    ->get()
                    ->getResultArray();

               $branchesList = [];
               $branchNames = [];

               foreach ($branches as $branch) {
                    $branchesList[] = [
                         'b_id'   => $branch['id'],
                         'b_name' => $branch['SysField'],
                    ];
                    $branchNames[$branch['id']] = $branch['SysField'];
               }

               // Update cluster branches with names
               foreach ($clusters as &$cluster) {
                    $branchIds = explode(',', $cluster['branches']);
                    $branchLabels = [];

                    foreach ($branchIds as $bid) {
                         $bid = trim($bid);
                         if (isset($branchNames[$bid])) {
                              $branchLabels[] = $branchNames[$bid];
                         }
                    }

                    $cluster['branches'] = implode(', ', $branchLabels);
               }

               return [
                    'STATUS'   => true,
                    'message'  => 'Full access data for AVP',
                    'clusters' => $clusters,
                    'branches' => $branchesList,
                    'zones'    => $zones
               ];
          }

          // For all other roles (non-BM and non-AVP)
          $userMapData = $db2->table('user_map as b')
               ->select('b.zone as b_zone, z.zone as z_zone, z.z_id, b.cluster as b_cluster, b.branches as b_branches')
               ->join('zones as z', 'z.z_id = b.zone', 'left')
               ->where('b.emp_code', $user)
               ->get()
               ->getResultArray();

          if (empty($userMapData)) {
               return $this->response->setJSON([
                    'STATUS'  => false,
                    'message' => 'No mapping found for emp_code: ' . $user,
               ]);
          }

          $allClusterIDs = [];
          $allBranchIDs = [];
          $zones = [];

          foreach ($userMapData as $row) {
               if (!empty($row['b_cluster'])) {
                    $clusterIDs = array_map('trim', explode(',', $row['b_cluster']));
                    $allClusterIDs = array_merge($allClusterIDs, $clusterIDs);
               }

               if (!empty($row['b_branches'])) {
                    $branchIDs = array_map('trim', explode(',', $row['b_branches']));
                    $allBranchIDs = array_merge($allBranchIDs, $branchIDs);
               }

               if (!empty($row['z_zone'])) {
                    $zones['z_zone'] = $row['z_zone'];
                    $zones['z_id'] = $row['z_id'];
               }
          }

          $allClusterIDs = array_unique($allClusterIDs);
          $allBranchIDs = array_unique($allBranchIDs);
          $zones = array_unique($zones);

          $clusters = [];
          if (!empty($allClusterIDs)) {
               $clusters = $db2->table('clusters')
                    ->whereIn('cluster_id', $allClusterIDs)
                    ->get()
                    ->getResultArray();
          }

          $branchesList = [];
          $branchNames = [];
          if (!empty($allBranchIDs)) {
               $branches = $secondaryDB->table('Branches')
                    ->select('id, SysField')
                    ->whereIn('id', $allBranchIDs)
                    ->get()
                    ->getResultArray();

               foreach ($branches as $branch) {
                    $branchesList[] = [
                         'b_id'   => $branch['id'],
                         'b_name' => $branch['SysField'],
                    ];
                    $branchNames[$branch['id']] = $branch['SysField'];
               }
          }

          foreach ($clusters as &$cluster) {
               $clusterBranchNames = [];
               $clusterBranchIds = explode(',', $cluster['branches']);

               foreach ($clusterBranchIds as $branchID) {
                    $branchID = trim($branchID);
                    if (isset($branchNames[$branchID])) {
                         $clusterBranchNames[] = $branchNames[$branchID];
                    }
               }

               $cluster['branches'] = implode(', ', $clusterBranchNames);
          }

          return [
               'STATUS'   => true,
               'message'  => 'Data fetched successfully',
               'clusters' => $clusters,
               'branches' => $branchesList,
               'zones'    => $zones
          ];
     }




     // $b2 = $b->getRowArray(); // Retrieve the first row of the result

     // // Debugging: Output the result to check it
     // // echo "<pre>";
     // // print_r($b2); // This will print the resulting array
     // // die(); 
     // return $b2;
     //     $cluster_id = $b2['cluster_id'];

     //     $builder = $db2->table('cluster_zone_map as cz')
     //         ->select('cz.zone_id, z.zone')
     //         ->join('zones as z', 'z.z_id = cz.zone_id', 'left')
     //         ->where('cz.cluster_id', $cluster_id);
     //     $query = $builder->get();
     //     return $query->getResultArray();
     // } else {
     //     $builder = $db2->table('branchesmapped as bm')
     //         ->select('bm.emp_code, bm.zone_id, z.zone')
     //         ->join('zones as z', 'z.z_id = bm.zone_id', 'left');
     //     // Apply condition only if the role is not 'SUPER_ADMIN'
     //     if ($role != 'SUPER_ADMIN') {
     //         $builder->where('bm.emp_code', $user);
     //     }
     //     $query = $builder->get();
     //     return $query->getResultArray();




     public function getUserBranchClusterZoneList($user, $role)
     {
          $db2 = \Config\Database::connect('default');
          log_message('error', 'Model - User: ' . $user . ', Role: ' . $role);
          if ($role === 'BM') {
               $uList = $db2->table('branchesmapped as bm')
                    ->select('  bm.branch_id, b.branch')
                    ->join('branches as b', 'b.branch_id = bm.branch_id', 'left')
                    ->where('bm.emp_code', $user)
                    ->get()
                    ->getResultArray();
          } else if ($role === 'CM') {
               $uList = $db2->table('branchesmapped as bm')
                    ->select('bm.cluster_id, c.cluster')
                    ->join('clusters as c', 'c.cluster_id = bm.cluster_id', 'left')
                    ->where('bm.emp_code', $user)
                    ->get()
                    ->getResultArray();
          } else if ($role === 'ZONAL_MANAGER' || $role === 'AVP') {
               $uList = $db2->table('branchesmapped as bm')
                    ->select('bm.zone_id, z.zone')
                    ->join('zones as z', 'z.z_id = bm.zone_id', 'left')
                    ->where('bm.emp_code', $user)
                    ->get()
                    ->getResultArray();
          } else if ($role === 'SUPER_ADMIN') {
               $uList = $db2->table('branchesmapped as bm')
                    ->select('bm.zone_id, z.zone')
                    ->join('zones as z', 'z.z_id = bm.zone_id', 'left')
                    ->get()
                    ->getResultArray();
          }

          log_message('error', '  user id ' . $user);
          log_message('error', '  role 11' . $role);
          log_message('error', '  uList ' . json_encode($uList));

          return [
               'uList' => $uList,
          ];
     }

     public function getUsersAreaList($emp_code)
     {
          $user = $this->getUserDetails($emp_code);
          if (!$user) {
               return [];
          }
          $db2 = \Config\Database::connect('default');

          // Start building the query
          $areaLists = $db2->table('branchesmapped as bm')
               ->select('cl.area_id, a.area')
               ->join('clust_area_map as cl', 'cl.cluster_id = bm.cluster_id', 'left')
               ->join('area as a', 'cl.area_id = a.id', 'left')
               ->groupBy('cl.area_id');  // Ensure chaining is correct

          // Restrict access for non-admin roles
          if ($user->isAdmin == 'N') {
               $areaLists = $areaLists->where('bm.emp_code', $emp_code);
          }

          // Execute query and return result 
          return $areaLists->get()->getResultArray();
     }



     public function getUserDetails($bmid)
     {

          // log_message('error', '  user id ' . $bmid);
          if ($bmid > 0) {
               // Fetch user details from the 'new_emp_master' table where 'emp_code' matches the provided $bmid
               return $this->select('new_emp_master.*, bmcm.role') // Select fields you need
                    ->join('bmcm', 'bmcm.emp_code = new_emp_master.emp_code', 'left') // Define the join condition
                    ->where('new_emp_master.emp_code', $bmid)
                    //->where( 'bmcm.role !=', NULL )
                    ->get()
                    ->getRow();
               // Fetch and return a single row as an object
          }
          return false;
          // Return false if the $bmid is invalid
     }

     public function getUserProfiles($bmid)
     {
          log_message('error', '  user id ' . $bmid);
          $db2 = \Config\Database::connect('secondary');
          if ($bmid > 0) {
               // Fetch user details from the 'new_emp_master' table where 'emp_code' matches the provided $bmid
               return $this->select('n.emp_code, n.fname, n.lname,n.comp_name,n.password,   bmcm.role') // Select fields you need
                    ->from('new_emp_master as n') // Alias for new_emp_master table
                    ->join('bmcm', 'bmcm.emp_code = n.emp_code', 'left') // Define the join condition
                    ->where('n.emp_code', $bmid)
                    //->where( 'bmcm.role !=', NULL )
                    ->get()
                    ->getRow();
               // Fetch and return a single row as an object
          }
          return false;
          // Return false if the $bmid is invalid
     }


     public function insertLoginData($data)
     {
          return $this->db->table('login_sessions')->insert($data);
     }

     public function addBranchOrClusterToEmp($data, $emp_code, $branch_id, $cluster_id, $zone_id)
     {
          $db2 = \Config\Database::connect('default');

          $db2->transStart();

          // Prepare query to check if emp_code already exists in the table with optional branch and cluster filters
          $builder = $db2->table('branchesmapped')
               ->where('emp_code', $emp_code);

          if ($branch_id > 0) {
               $builder->where('branch_id', $branch_id);
          }
          if ($cluster_id > 0) {
               $builder->where('cluster_id', $cluster_id);
          }
          if ($zone_id > 0) {
               $builder->where('zone_id', $zone_id);
          }

          $existingRecord = $builder->get()->getRowArray();

          if ($existingRecord) {
               // If emp_code exists, perform an update
               $builder = $db2->table('branchesmapped')->where('emp_code', $emp_code);

               if ($branch_id > 0) {
                    $builder->where('branch_id', $branch_id);
               }
               if ($cluster_id > 0) {
                    $builder->where('cluster_id', $cluster_id);
               }
               if ($zone_id > 0) {
                    $builder->where('zone_id', $zone_id);
               }

               log_message('debug', 'Attempting to add branch/cluster assignment in model for emp_code: ' . $emp_code);

               if (!$builder->update($data)) {
                    $error = $db2->error();
                    log_message('error', 'Database error during update in branchesmapped: ' . json_encode($error));
                    $db2->transRollback();
                    return false;
               }
          } else {

               log_message('debug', 'Entered addBranchOrClusterToEmp for emp_code: ' . $emp_code);

               // If emp_code does not exist, perform an insert
               if (!$db2->table('branchesmapped')->insert($data)) {
                    $error = $db2->error();
                    log_message('error', 'Failed to insert data into branchesmapped table: ' . print_r($error, true));
                    $db2->transRollback();
                    return false;
               }
          }

          $db2->transComplete();
          if (!$db2->transStatus()) {
               log_message('error', 'Failed transaction for emp_code: ' . $emp_code);
          }

          return $db2->transStatus();
     }

     public function getAreaDetailsById($area_id)
     {
          $db2 = \Config\Database::connect('default');
          return $db2->table('area a')
               ->select('a.*')
               ->where('a.status', 'A')
               ->where('a.id', $area_id)
               ->get()
               ->getResultArray();
     }

     // public function getBranchDetailsById($branch_id){
     //     return $this->select('a.*, c.cluster, cb.cluster_id, z.zone, cz.zone_id')
     //     ->from('branches a') // Alias for asset table
     //     ->join('cluster_branch_map as cb', 'cb.branch_id = a.cluster_id', 'left')
     //     ->join('cluster_zone_map as cz', 'cz.cluster_id = cb.cluster_id', 'left')
     //     ->join('clusters as c', 'c.cluster_id = cb.cluster_id', 'left')
     //     ->join('zones as z', 'z.z_id = cz.zone_id', 'left')
     //     //->join('clust_area_map as cl', 'cl.cluster_id = bm.cluster_id', 'left')
     //     ->where('a.status', 'A')
     //     ->where('a.branch_id', $branch_id) // Group by asset id
     //     ->get()
     //     ->getResultArray();
     // }

     public function getBranchDetailsById($branch_id)
     {
          $db2 = \Config\Database::connect('default');
          return $db2->table('branches a')
               ->select('a.*, c.cluster, cb.cluster_id, z.zone, cz.zone_id')
               ->join('cluster_branch_map cb', 'cb.branch_id = a.branch_id', 'left') // Fixed the join condition
               ->join('cluster_zone_map cz', 'cz.cluster_id = cb.cluster_id', 'left')
               ->join('clusters c', 'c.cluster_id = cb.cluster_id', 'left')
               ->join('zones z', 'z.z_id = cz.zone_id', 'left')
               ->where('a.status', 'A')
               ->where('a.branch_id', $branch_id)
               ->get()
               ->getRowArray(); // Use getRowArray() if expecting a single row
     }

     public function getBranchDetailsById_fz($branch_id)
     {


          $db2 = \Config\Database::connect('default');

          return $db2->table('user_map a')
               ->select('a.*')
               ->where("FIND_IN_SET('$branch_id', a.branches)", null, false) // ✅ Use FIND_IN_SET for CSV search
               //->where("FIND_IN_SET('$cluster_id', a.cluster)", null, false)

               ->get()
               ->getRowArray();
     }

     public function getclusterId($branch_id)
     {

          $db2 = \Config\Database::connect('default');

          return $db2->table('clusters a')
               ->select('a.cluster_id')
               ->where("FIND_IN_SET('$branch_id', a.branches)", null, false) // ✅ Use FIND_IN_SET for CSV search
               ->get()
               ->getRowArray();
     }
     public function getClusterDetailsById($cluster_id)
     {
          $db2 = \Config\Database::connect('default');
          return $db2->table('clusters c')
               ->select('c.cluster, c.cluster_id, z.zone, cz.zone_id')
               ->join('cluster_zone_map cz', 'cz.cluster_id = c.cluster_id', 'left')
               ->join('zones z', 'z.z_id = cz.zone_id', 'left')
               ->where('c.cluster_id', $cluster_id)
               ->where('c.status', 'A')
               ->get()
               ->getRowArray();
     }

     public function getZoneDetailsById($zone_id)
     {
          $db2 = \Config\Database::connect('default');
          return $db2->table('zones z')
               ->select('z.zone, z.z_id as zone_id,   ')
               ->where('z.z_id', $zone_id)
               ->where('z.status', 'A')
               ->get()
               ->getRowArray();
     }

     public function getUserBranchList($user, $role)
     {
          $db2 = \Config\Database::connect('default');
          $builder = $db2->table('branchesmapped as bm')
               ->select('bm.emp_code, bm.branch_id, bm.branch, bm.cluster_id, bm.cluster, cl.area_id, a.area')
               ->join('clust_area_map as cl', 'cl.cluster_id = bm.cluster_id', 'left')
               ->join('area as a', 'cl.area_id = a.id', 'left');

          // Apply condition only if the role is not 'SUPER_ADMIN'
          if ($role != 'SUPER_ADMIN') {
               $builder->where('bm.emp_code', $user);
          }

          $query = $builder->get();
          return $query->getResultArray();
     }

     public function addRoleToEmp($data, $emp_code)
     {
          $this->db->transStart();

          // Check if emp_code already exists in the table
          $existingRecord = $this->db->table('bmcm')
               ->where('emp_code', $emp_code)
               ->get()
               ->getRowArray();

          if ($existingRecord) {
               // If emp_code exists, perform an update
               if (!$this->db->table('bmcm')->where('emp_code', $emp_code)->update($data)) {
                    $error = $this->db->error();
                    log_message('error', 'Failed to update data in bmcm table: ' . print_r($error, true));
                    $this->db->transRollback();
                    return false;
               }
          } else {
               // If emp_code does not exist, perform an insert
               if (!$this->db->table('bmcm')->insert($data)) {
                    $error = $this->db->error();
                    log_message('error', 'Failed to insert data into bmcm table: ' . print_r($error, true));
                    $this->db->transRollback();
                    return false;
               }
          }

          $this->db->transComplete();
          return $this->db->transStatus(); // Returns true if transaction succeeds, false if not
     }

     public function addAreaToEmp($data, $emp_code, $area_id)
     {

          $db2 = \Config\Database::connect('default');
          $db2->transStart();
          // Check if emp_code already exists in the empareamap table
          $existingRecord = $db2->table('empareamap')
               ->where('emp_code', $emp_code)
               ->where('area_id', $area_id) // Check for area_id as well
               ->get()
               ->getRowArray();

          if ($existingRecord) {
               // If emp_code and area_id exist, perform an update
               if (!$db2->table('empareamap')->where('emp_code', $emp_code)->where('area_id', $area_id)->update($data)) {
                    $error = $db2->error();
                    log_message('error', 'Failed to update data in empareamap table: ' . print_r($error, true));
                    $db2->transRollback();
                    return false;
               }
          } else {
               // If emp_code does not exist, perform an insert into empareamap
               if (!$db2->table('empareamap')->insert($data)) {
                    $error = $db2->error();
                    log_message('error', 'Failed to insert data into empareamap table: ' . print_r($error, true));
                    $db2->transRollback();
                    return false;
               }
          }

          $db2->transComplete();
          return $db2->transStatus(); // Returns true if transaction succeeds, false if not
     }


     // public function addUser($data, $emp_code) {
     //     $this->db->transStart();

     //     // Test inserting without transaction to see if it works
     //     if (!$this->db->table('new_emp_master')->insert($data)) {
     //         $error = $this->db->error();
     //         log_message('error', 'Failed to insert data into new_emp_master table: ' . print_r($error, true));
     //         return false; // This will help identify if insert fails
     //     }

     //     $this->db->transComplete();
     //     return true;

     // }

     public function addUser($data, $emp_code)
     {
          try {
               $this->db->transStart(); // Start the transaction

               // Validate input data
               if (empty($data['emp_code'])) {
                    log_message('error', 'Employee name or code is missing.');
                    return ['status' => false, 'message' => 'Missing required fields'];
               }

               // Check if emp_code exists (optional if unique constraint exists in DB)
               $existingEmpCode = $this->db->table('new_emp_master')
                    ->where('emp_code', $emp_code)
                    ->get()
                    ->getRowArray();

               if ($existingEmpCode) {
                    log_message('error', 'Employee code already exists: ' . $emp_code);
                    return ['status' => false, 'message' => 'Employee code already exists'];
               }

               // Insert new employee record
               if (!$this->db->table('new_emp_master')->insert($data)) {
                    $error = $this->db->error();
                    log_message('error', 'Failed to insert data into new_emp_master table: ' . print_r($error, true));
                    return ['status' => false, 'message' => 'Insertion failed'];
               }

               $this->db->transComplete(); // Complete the transaction
               return ['status' => true, 'message' => 'User added successfully'];
          } catch (\Exception $e) {
               $this->db->transRollback(); // Rollback on error
               log_message('error', 'Error during user addition: ' . $e->getMessage());
               return ['status' => false, 'message' => 'An error occurred'];
          }
     }





     public function editUser($data, $bmid)
     {
          if (is_array($data) && $bmid > 0) {

               return $this->db->table('new_emp_master')
                    ->set($data)
                    ->where('emp_code', $bmid)
                    ->update();
          }
          return false;
     }



     public function removeBranchFromCluster($cluster_id, $branch_id)
     {
          $this->db->transStart();

          // Prepare query to check if branch_id already exists in the table with optional cluster filter
          $builder = $this->db->table('cluster_branch_map')
               ->where('branch_id', $branch_id);

          if ($cluster_id > 0) {
               $builder->where('cluster_id', $cluster_id);
          }

          // Perform delete operation
          if (!$builder->delete()) {
               $error = $this->db->error();
               log_message('error', 'Failed to delete data from cluster_branch_map table: ' . print_r($error, true));
               $this->db->transRollback();
               return false;
          }

          $this->db->transComplete();
          return $this->db->transStatus(); // Returns true if transaction succeeds, false if not
     }


     public function deleteBranchOrClusterFromEmp($emp_code, $branch_id, $cluster_id, $zone_id)
     {
          $this->db->transStart();

          // Prepare query to check if emp_code already exists in the table with optional branch and cluster filters
          $builder = $this->db->table('branchesmapped')
               ->where('emp_code', $emp_code);

          if ($branch_id > 0) {
               $builder->where('branch_id', $branch_id);
          }
          if ($cluster_id > 0) {
               $builder->where('cluster_id', $cluster_id);
          }
          if ($zone_id > 0) {
               $builder->where('zone_id', $zone_id);
          }

          // Perform delete operation
          if (!$builder->delete()) {
               $error = $this->db->error();
               log_message('error', 'Failed to delete data from branchesmapped table: ' . print_r($error, true));
               $this->db->transRollback();
               return false;
          }

          $this->db->transComplete();
          return $this->db->transStatus(); // Returns true if transaction succeeds, false if not
     }


     public function getUniqueDepts()
     {
          $builder = $this->db->table('new_emp_master');
          $builder->select('dept_name');
          $builder->distinct();
          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array
     }


     public function emp_list()
     {
          $builder = $this->db->table('new_emp_master');
          $builder->select('emp_code,comp_name,mail_id,mobile,dept_name,location_name,is_pbt_access_given,super_admin_pbt');
          $builder->distinct();
          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array
     }


     public function que_list()
     {

          $db2 = \Config\Database::connect('default');
          // log_message('error', "selectedMonth: {$selectedMonth}");
          // log_message('error', "selectedBranch: {$selectedBranch}");
          // echo $selectedBranch;die();

          $builder = $db2->table('ques');
          $builder->select('id,dept,que_count,time,created_by,created_on');
          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array
     }

     public function emp_pbt_access($emp_code, $data)
     {
          $builder = $this->db->table('new_emp_master');
          $builder->where('emp_code', $emp_code);
          $updated = $builder->update($data);

          return $updated;  // returns true/false
     }



     public function get_role($emp_code)
     {
          $builder = $this->db->table('new_emp_master');
          $builder->select('is_pbt_access_given,super_admin_pbt');
          $builder->where('emp_code', $emp_code);
          $query = $builder->get();
          return $query->getResult();
     }

     public function add_que($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('ques');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return true if insert was successful
          return $inserted;
     }

     public function get_question($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('ques');
          $builder->where('id', $id);
          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array

     }


     public function update_question($id, $data)
     {
          $db = \Config\Database::connect();
          $builder = $db->table('ques');
          $builder->where('id', $id);
          return $builder->update($data); // returns true/false
     }

     public function question_details()
     {

          $db2 = \Config\Database::connect('default');


          $builder = $db2->table('que_details');
          $builder->select('id,dept,question,opt_a,opt_b,opt_c,opt_d,answer,type,category,created_by,created_on');
          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array
     }

     public function get_que_det($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('que_details');
          $builder->where('id', $id);
          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array

     }

     public function get_categories()
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('category');
          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array

     }

     public function update_que_det($id, $data)
     {
          $db = \Config\Database::connect();
          $builder = $db->table('que_details');
          $builder->where('id', $id);
          return $builder->update($data); // returns true/false
     }

     public function add_candidate($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('write_test');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }


     public function candidate_details()
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('write_test');

          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array

     }

     public function questions_by_id($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('write_test_details');

          $builder->select('que_details.*'); // select all columns from que_details
          $builder->join('que_details', 'que_details.id = write_test_details.que_id');
          $builder->where('write_test_details.candidate_id', $id);

          $query = $builder->get();
          return $query->getResult(); // or getResultArray() for array format
     }



     public function get_details_by_id($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('write_test');
          $builder->where('id', $id);
          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array

     }

     public function time_by_dept($dept)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('ques');
          $builder->where('dept', $dept);
          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array

     }

     public function getcountbydept($dept)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('ques');
          $builder->where('dept', $dept);
          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array

     }

     public function getrandomquestions($type, $category_question_count, $count, $dept)
     {
          $db = \Config\Database::connect('default');
          $finalIds = [];

          $totalCollected = 0;

          foreach ($category_question_count as $catInfo) {

               $category = $catInfo['category'];
               $catCount = $catInfo['question_count'];

               if ($totalCollected + $catCount > $count) {
                    $catCount = $count - $totalCollected;
               }

               if ($catCount <= 0) break;

               $builder = $db->table('que_details');
               $builder->select('id');
               $builder->where('dept', $dept);
               $builder->where('category', $category);
               $builder->where('type', $type);  // ✅ Add this line
               $builder->orderBy('RAND()');
               $builder->limit($catCount);

               $query = $builder->get();
               $result = $query->getResultArray();

               $ids = array_column($result, 'id');
               $finalIds = array_merge($finalIds, $ids);

               $totalCollected += count($ids);

               if ($totalCollected >= $count) {
                    break;
               }
          }

          return $finalIds;
     }



     public function getcategorydetails($type, $dept)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('category');
          $builder->select('category,question_count');
          $builder->where('dept', $dept);
          $builder->where('type', $type);

          $query = $builder->get();
          $result = $query->getResultArray();

          return $result; // returning actual query result instead of undefined $ids
     }





     public function update_answer($questionId, $candidateId, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('write_test_details');
          $builder->where('que_id', $questionId);
          $builder->where('candidate_id', $candidateId);
          return $builder->update($data);
     }

     public function update_marks($candidateId, $update_marks)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('write_test');
          $builder->where('id', $candidateId);
          return $builder->update($update_marks);
     }

     public function getMarks($candidateId)
     {
          $db = \Config\Database::connect();
          $builder = $db->table('write_test_details');

          $builder->select([
               'SUM(IF(result = "1", 1, 0)) AS tt_correct',
               'SUM(IF(result = "0", 1, 0)) AS wrong',
               'SUM(IF(result = "", 1, 0)) AS not_attemy'
          ]);

          $builder->where('candidate_id', $candidateId);

          return $builder->get()->getRowArray();
     }


     public function verifyOtp($candidateId, $otp)
     {
          $db = \Config\Database::connect();
          $builder = $db->table('write_test');
          $builder->where('id', $candidateId);
          $builder->where('otp', $otp);
          $builder->where('otp_expires_at >=', date('Y-m-d H:i:s')); // Not expired

          $result = $builder->get()->getRow();

          return $result ? true : false;
     }


     public function marks()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('write_test');

          return $builder->get()->getRowArray();
     }

     public function get_correct_answers($id)
     {
          $db = \Config\Database::connect();
          $builder = $db->table('write_test_details');
          $builder->select('
        write_test_details.*,
        que_details.dept,
        que_details.question,
        que_details.opt_a,
        que_details.opt_b,
        que_details.opt_c,
        que_details.opt_d,
        que_details.answer as correct_answer_key
    ');
          $builder->join('que_details', 'que_details.id = write_test_details.que_id');
          $builder->where('write_test_details.candidate_id', $id);
          $builder->where('write_test_details.selected_answer IS NOT NULL', null, false);
          $builder->where('write_test_details.selected_answer !=', '');

          $results = $builder->get()->getResult();

          // Map keys like 'A' to 'opt_a'
          $optionMap = [
               'A' => 'opt_a',
               'B' => 'opt_b',
               'C' => 'opt_c',
               'D' => 'opt_d'
          ];

          foreach ($results as $row) {
               $correctKey = strtoupper($row->correct_answer_key);
               $selectedKey = strtoupper($row->selected_answer);

               $row->correct_answer_value = isset($optionMap[$correctKey]) ? $row->{$optionMap[$correctKey]} : null;
               $row->selected_answer_value = isset($optionMap[$selectedKey]) ? $row->{$optionMap[$selectedKey]} : null;
          }

          return $results;
     }

     public function add_category($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('category');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }


     public function get_category_by_id($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('category');
          $builder->where('id', $id);
          $query = $builder->get();
          return $query->getResult(); // or getResultArray() if you want an array

     }







     public function getUserModes()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('mode');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserMode($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('mode');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function createUser($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('users');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserModeById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('mode');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserModeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('mode');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserModeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('mode');
          $builder->where('id', $id);
          return $builder->update($data);
     }




     public function getUserDesign()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('designations');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserDesign($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('designations');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserDesignById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('designations');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserDesignById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('designations');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserDesignModeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('designations');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserPosit()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('positions');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserPosit($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('positions');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserPositById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('positions');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserPositById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('positions');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserPositModeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('positions');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserCalen()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('calendar');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserCalen($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('calendar');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserCalenById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('calendar');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserCalenById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('calendar');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserCalenModeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('calendar');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function getUserDept()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('department');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserDept($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('department');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserDeptById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('department');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserDeptById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('department');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserDeptModeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('department');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function getUserSubDept()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('subdepartment');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserSubDept($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('subdepartment');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserSubDeptById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('subdepartment');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserSubDeptById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('subdepartment');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserSubDeptModeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('subdepartment');
          $builder->where('id', $id);
          return $builder->update($data);
     }



     public function getGrade()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('grade');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createGrade($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('grade');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getGradeById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('grade');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateGradeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('grade');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteGradeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('grade');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function getUserPayGroup()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('paygroup');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserPayGroup($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('paygroup');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserPayGroupById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('paygroup');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserPayGroupById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('paygroup');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserPayGroupModeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('paygroup');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function getUserCurrency()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('currency');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserCurrency($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('currency');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserCurrencyById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('currency');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserCurrencyById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('currency');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserCurrencyModeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('currency');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserJobProfile()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('jobprofile');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserJobProfile($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('jobprofile');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserJobProfileById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('jobprofile');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserJobProfileById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('jobprofile');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserJobProfileById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('jobprofile');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function getUserPaymentType()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('paymenttype');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserPaymentType($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('paymenttype');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserPaymentTypeById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('paymenttype');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserPaymentTypeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('paymenttype');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserPaymentTypeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('paymenttype');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function getUserBank()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('paymenttype');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserBank($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('banks');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserBankById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('banks');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserBankById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('banks');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserBankById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('banks');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserCenter()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('centers');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserCenter($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('centers');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserCenterById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('centers');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserCenterById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('centers');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserCenterById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('centers');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function getUserWorkType()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('worktype');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserWorkType($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('worktype');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserWorkTypeById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('worktype');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserWorkTypeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('worktype');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserWorkTypeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('worktype');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserMedRegCouncil()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('medregcouncil');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserMedRegCouncil($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('medregcouncil');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserMedRegCouncilById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('medregcouncil');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserMedRegCouncilById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('medregcouncil');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserMedRegCouncilById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('medregcouncil');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function getUserQualification()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('qualification');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserQualification($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('qualification');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserQualificationById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('qualification');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserQualificationById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('qualification');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserQualificationById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('qualification');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserSpecialization()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('specialization');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserSpecialization($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('specialization');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserSpecializationById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('specialization');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserSpecializationById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('specialization');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserSpecializationById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('specialization');
          $builder->where('id', $id);
          return $builder->update($data);
     }



     public function getUserEarningName()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('earningname');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserEarningName($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('earningname');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function updateUserEarningNameById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('earningname');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function deleteUserEarningNameById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('earningname');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserAccount()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('account');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserAccount($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('account');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserAccountById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('account');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserAccountById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('account');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserAccountById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('account');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserDeductionName()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('deductionname');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserDeductionName($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('deductionname');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserDeductionById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('deductionname');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserDeductionById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('deductionname');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserDeductionById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('deductionname');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserLoanType()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('loantype');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserLoanType($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('loantype');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserLoanTypeById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('loantype');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserLoanTypeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('loantype');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserLoanTypeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('loantype');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserLeaveTemplate()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('loantype');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserLeaveTemplate($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('leavetemplate');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserLeaveTemplateById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('leavetemplate');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserLeaveTemplateById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('leavetemplate');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserLeaveTemplateById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('leavetemplate');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserAirTicketTemplate()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('airtickettemplate');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserAirTicketTemplate($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('airtickettemplate');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserAirTicketTemplateById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('airtickettemplate');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserAirTicketTemplateById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('airtickettemplate');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserAirTicketTemplateById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('airtickettemplate');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserReasonForLeaving()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('reasonforleaving');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserReasonForLeaving($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('reasonforleaving');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserReasonForLeavingById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('reasonforleaving');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserReasonForLeavingById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('reasonforleaving');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserReasonForLeavingById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('reasonforleaving');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getUserState()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('state');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }

     public function getUsers()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('users');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createUserState($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('state');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getUserStateById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('state');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateUserStateById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('state');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteUserStateById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('state');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getSbu()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('sbu');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createSbu($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sbu');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getSbuById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sbu');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateSbuById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sbu');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteSbuById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sbu');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function getRegion()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('region');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createRegion($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('region');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function createCandidateRegion($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_regions');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getRegionId($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('region');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateRegionId($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('region');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function updateRegionDetailsId($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_regions');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function updateCandidateRegionId($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_regions');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteRegionId($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('region');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getOrgan()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('organization');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }





     public function createOrgan($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('organization');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getOrganById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('organization');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateOrganById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('organization');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteOrganById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('organization');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getCity()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('city');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createCity($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('city');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getCityById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('city');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateCityById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('city');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteCityById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('city');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getCluster()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('cluster');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createCluster($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('cluster');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getClusterById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('cluster');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateClusterById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('cluster');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteClusterById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('cluster');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getLocation()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('location');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createLocation($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('location');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getLocationById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('location');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateLocationById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('location');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteLocationById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('location');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getDeptCategory()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('dept_category');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createDeptCategory($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('dept_category');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getDeptCategoryById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('dept_category');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateDeptCategoryById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('dept_category');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteDeptCategoryById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('dept_category');
          $builder->where('id', $id);
          return $builder->update($data);
     }



     public function getMainDept()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('main_dept');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createMainDept($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('main_dept');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getMainDeptById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('main_dept');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateMainDeptById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('main_dept');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteMainDeptById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('main_dept');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getSubDept()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('sub_dept');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createSubDept($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_dept');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getSubDeptById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_dept');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateSubDeptById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_dept');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteSubDeptById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_dept');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function getBranch()
     {
          $travelappDb = \Config\Database::connect('travelapp');
          $builder = $travelappDb->table('Branches');
          $builder->where('status', 'A');
          $builder->orderBy('SysNo', 'ASC');
          return $builder->get()->getResult();
     }


     public function createBranch($data)
     {
          $travelappDb = \Config\Database::connect('travelapp');
          $builder = $travelappDb->table('Branches');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $travelappDb->insertID();
          } else {
               return false;
          }
     }

     public function getBranchById($id)
     {
          $travelappDb = \Config\Database::connect('travelapp');
          $builder = $travelappDb->table('Branches');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateBranchById($id, $data)
     {
          $travelappDb = \Config\Database::connect('travelapp');
          $builder = $travelappDb->table('Branches');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteBranchById($id, $data)
     {
          $travelappDb = \Config\Database::connect('travelapp');
          $builder = $travelappDb->table('Branches');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getShiftRoster()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('shift_roster');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createShiftRoster($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('shift_roster');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getShiftRosterById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('shift_roster');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateShiftRosterById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('shift_roster');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteShiftRosterById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('shift_roster');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getReligion()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('religion');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createReligion($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('religion');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getReligionById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('religion');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateReligionById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('religion');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteReligionById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('religion');
          $builder->where('id', $id);
          return $builder->update($data);
     }



     public function getCaste()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('caste');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createCaste($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('caste');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getCasteById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('caste');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateCasteById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('caste');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteCasteById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('caste');
          $builder->where('id', $id);
          return $builder->update($data);
     }



     public function getDegree()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('degrees');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createDegree($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('degrees');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getDegreeById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('degrees');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateDegreeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('degrees');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteDegreeById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('degrees');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getBloodGroup()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('bloodgroup');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createBloodGroup($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('bloodgroup');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getBloodGroupById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('bloodgroup');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateBloodGroupById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('bloodgroup');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteBloodGroupById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('bloodgroup');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getHolidays()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('holiday');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createHoliday($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('holiday');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getHolidayById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('holiday');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateHolidayById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('holiday');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteHolidayById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('holiday');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getRadiologyCandidates()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('radiology_candidate');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }

     public function getUserRadiologyCandidates($user)
     {
          $db = \Config\Database::connect();
          $builder = $db->table('radiology_candidate');
          $builder->where('created_by', $user);
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }

     public function getRadiologyHrCandidates()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('radiology_candidate');
          $builder->where('rpt_mgr_status', 'Approved');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createRadiologyCandidate($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_candidate');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getRadiologyCandidateById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_candidate');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateRadiologyCandidateById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_candidate');
          $builder->where('candidate_id', $id);
          return $builder->update($data);
     }

     public function updateCandidateDetailsId($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_candidate');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function updateModalityDetailsId($id, $data)
     {

          $db = \Config\Database::connect('default');
          $builder = $db->table('candidate_modalities');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function updatePersonalityDetailsId($id, $data)
     {
          // print_r($id);
          // print_r($data);die();
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_personality_assessment');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function updateTechnicalDetailsId($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_technical_evaluation');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteRadiologyCandidateById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_candidate');
          $builder->where('id', $id);
          $builder->where('status', 'A');
          return $builder->update($data);
     }



     public function getRadiologyPersonalityAssessment()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('radiology_personality_assessment');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createRadiologyPersonalityAssessment($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_personality_assessment');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getRadiologyPersonalityAssessmentById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_personality_assessment');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateRadiologyPersonalityAssessmentById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_personality_assessment');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function updateCandidatePersonalityId($id, $data)
     {

          $db = \Config\Database::connect();
          $builder = $db->table('radiology_personality_assessment');

          $builder->where('id', $id);
          return $builder->update($data); // $data is associative array with updated fields
     }


     public function deleteRadiologyPersonalityAssessmentById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_personality_assessment');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getRadiologyTechnicalEvaluation()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('radiology_technical_evaluation');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createRadiologyTechnicalEvaluation($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_technical_evaluation');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getRadiologyTechnicalEvaluationById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_technical_evaluation');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateRadiologyTechnicalEvaluationById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_technical_evaluation');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function updateTechnicalEvaluationId($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_technical_evaluation');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteRadiologyTechnicalEvaluationById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_technical_evaluation');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function getRadiologyModalities()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('radiology_modalities');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createRadiologyModalities($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('candidate_modalities');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getRadiologyModalityById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_modalities');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateRadiologyModalityById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_modalities');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteRadiologyModalityById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_modalities');
          $builder->where('id', $id);
          return $builder->update($data);
     }




     public function getModalities()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('modalities');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createModality($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('modalities');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getModalityById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('modalities');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateModalityById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('modalities');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteModalityById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('modalities');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     // public function getSubModalities()
     // {
     //     $db = \Config\Database::connect();
     //     $builder = $db->table('sub_modalities');
     //     $builder->where('status', 'A');
     //     $builder->orderBy('id', 'DESC');
     //     return $builder->get()->getResult();
     // }


     public function getSubModClass()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('sub_mod_class');
          $builder->where('status', 'A');
          $builder->orderBy('id', 'DESC');
          return $builder->get()->getResult();
     }


     public function createSubModClass($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_mod_class');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getSubModClassById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_mod_class');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateSubModClassById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_mod_class');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteSubModClassById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_mod_class');
          $builder->where('id', $id);
          return $builder->update($data);
     }




     public function getSubModalities()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('sub_modalities sm');
          $builder->select('sm.*, m.mod_name'); // ✅ select needed fields
          $builder->join('modalities m', 'm.id = sm.mod_id', 'left'); // ✅ join
          $builder->where('sm.status', 'A');
          $builder->orderBy('sm.id', 'DESC');

          return $builder->get()->getResult();
     }


     public function createSubModality($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_modalities');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }

     public function getSubModalityById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_modalities');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }


     public function getRadiologyUserById($id)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('users');

          $query = $builder->where('id', $id);
          return $query->get()->getRowArray();
     }

     public function updateSubModalityById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_modalities');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function deleteSubModalityById($id, $data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('sub_modalities');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function insertUser($data, $documentData)
     {

          try {
               $db = \Config\Database::connect('default');
               $db->transBegin(); // Begin transaction

               // Insert into employees table
               $builder = $db->table('employees');
               if (!$builder->insert($data)) {
                    throw new \Exception('Failed to insert into employees table');
               }

               // Get inserted employee ID
               $emp_id = $db->insertID();

               // Users table (for login)
               $user_name = $data['employee_name'] ?? '';
               $emp_code  = $data['employee_code'] ?? '';

               $user_data = [
                    'user_name'     => $user_name,
                    'user_code'     => $emp_code,
                    'emp_id'        => $emp_id,
                    'password'      => md5('adnet2008'),
                    'status'        => 'A',
                    'disabled'      => 'N',
                    'validity'      => date('Y-m-d', strtotime('+90 days')),
                    'failed_attems' => 0,
                    'is_admin'      => 'N',
                    'exit_date'     => '0000-00-00',
                    'role'          => '',

               ];

               $builder = $db->table('users');
               if (!$builder->insert($user_data)) {
                    throw new \Exception('Failed to insert into users table');
               }

               if (!empty($documentData) && is_array($documentData)) {
                    $builder = $db->table('employee_documents');

                    foreach ($documentData as $doc) {

                         $docData = [
                              'emp_id'        => $emp_id,
                              'document_name' => $doc['document_name'] ?? '',
                              'document_path' => $doc['document_path'] ?? '',
                              'uploaded_at'   => date('Y-m-d H:i:s')
                         ];

                         if (!$builder->insert($docData)) {
                              throw new \Exception('Failed to insert document: ' . ($doc['name'] ?? ''));
                         }
                    }
               }

               // ✅ Commit transaction
               $db->transCommit();

               return [
                    'status' => true,
                    'emp_id' => $emp_id
               ];
          } catch (\Exception $e) {
               // ❌ Rollback transaction on any error
               $db->transRollback();
               log_message('error', 'Insert User Error: ' . $e->getMessage());

               return [
                    'status'  => false,
                    'message' => 'Exception: ' . $e->getMessage()
               ];
          }
     }


     // public function getAllUsers()
     //     {
     //         $db = \Config\Database::connect();
     //         $builder = $db->table('employees');
     //         $builder->where('status', 'A');
     //         $builder->orderBy('emp_id', 'DESC');
     //         return $builder->get()->getResult();
     //     }

     public function getAllUsers()
     {
          $db = \Config\Database::connect();
          $builder = $db->table('employees');

          $builder->select('
        employees.emp_id, 
        employees.employee_code, 
        employees.employee_name,
        employees.designation, 
        employees.department, 
        employees.joining_date, 
        employees.employment_type, 
        employees.mobile, 
        employees.email, 
        employees.dob, 
        employees.gender, 
        employees.father_husband_name, 
        employees.marital_status, 
        employees.blood_group, 
        employees.religion, 
        employees.caste, 
        employees.department_category, 
        employees.main_department, 
        employees.sub_department, 
        employees.designation_name, 
        employees.grade_name, 
        employees.position, 
        employees.reporting_manager_name, 
        employees.reporting_manager_empcode, 
        employees.functional_manager_name, 
        employees.skip_level_manager_empcode, 
        employees.shift_description, 
        employees.highest_qualification, 
        employees.university, 
        employees.passing_year, 
        employees.total_experience, 
        employees.previous_company, 
        employees.previous_designation, 
        employees.previous_experience_years, 
        employees.bank_account_name, 
        employees.bank_account_number, 
        employees.ifsc_code, 
        employees.ctc, 
        employees.latest_agreement_valid_date, 
        employees.latest_agreement_end_date, 
        employees.latest_contract_fee_revision_amount, 
        employees.resignation, 
        employees.resignation_date, 
        employees.relieving_date, 
        employees.last_working_date, 
        employees.last_pay_date, 
        employees.separation_status, 
        employees.notice_period, 
        employees.status, 
        employees.created_at, 
        employees.updated_at,
        employee_documents.document_name, 
        employee_documents.document_path
    ');

          $builder->join('employee_documents', 'employee_documents.emp_id = employees.emp_id', 'left');
          $builder->where('employees.status', 'A');
          $builder->orderBy('employees.emp_id', 'DESC');

          $result = $builder->get()->getResultArray();

          $users = [];
          foreach ($result as $row) {
               $emp_id = $row['emp_id'];

               // Initialize employee if not already added
               if (!isset($users[$emp_id])) {
                    $users[$emp_id] = [
                         'emp_id' => $row['emp_id'],
                         'employee_code' => $row['employee_code'],
                         'employee_name' => $row['employee_name'],
                         'designation' => $row['designation'],
                         'department' => $row['department'],
                         'joining_date' => $row['joining_date'],
                         'employment_type' => $row['employment_type'],
                         'mobile' => $row['mobile'],
                         'email' => $row['email'],
                         'dob' => $row['dob'],
                         'gender' => $row['gender'],
                         'father_husband_name' => $row['father_husband_name'],
                         'marital_status' => $row['marital_status'],
                         'blood_group' => $row['blood_group'],
                         'religion' => $row['religion'],
                         'caste' => $row['caste'],
                         'department_category' => $row['department_category'],
                         'main_department' => $row['main_department'],
                         'sub_department' => $row['sub_department'],
                         'designation_name' => $row['designation_name'],
                         'grade_name' => $row['grade_name'],
                         'position' => $row['position'],
                         'reporting_manager_name' => $row['reporting_manager_name'],
                         'reporting_manager_empcode' => $row['reporting_manager_empcode'],
                         'functional_manager_name' => $row['functional_manager_name'],
                         'skip_level_manager_empcode' => $row['skip_level_manager_empcode'],
                         'shift_description' => $row['shift_description'],
                         'highest_qualification' => $row['highest_qualification'],
                         'university' => $row['university'],
                         'passing_year' => $row['passing_year'],
                         'total_experience' => $row['total_experience'],
                         'previous_company' => $row['previous_company'],
                         'previous_designation' => $row['previous_designation'],
                         'previous_experience_years' => $row['previous_experience_years'],
                         'bank_account_name' => $row['bank_account_name'],
                         'bank_account_number' => $row['bank_account_number'],
                         'ifsc_code' => $row['ifsc_code'],
                         'ctc' => $row['ctc'],
                         'latest_agreement_valid_date' => $row['latest_agreement_valid_date'],
                         'latest_agreement_end_date' => $row['latest_agreement_end_date'],
                         'latest_contract_fee_revision_amount' => $row['latest_contract_fee_revision_amount'],
                         'resignation' => $row['resignation'],
                         'resignation_date' => $row['resignation_date'],
                         'relieving_date' => $row['relieving_date'],
                         'last_working_date' => $row['last_working_date'],
                         'last_pay_date' => $row['last_pay_date'],
                         'separation_status' => $row['separation_status'],
                         'notice_period' => $row['notice_period'],
                         'status' => $row['status'],
                         'created_at' => $row['created_at'],
                         'updated_at' => $row['updated_at'],
                         'documents' => []
                    ];
               }

               // Add documents if available
               if ($row['document_name']) {
                    $users[$emp_id]['documents'][] = [
                         'document_name' => $row['document_name'],
                         'document_path' => $row['document_path']
                    ];
               }
          }

          // Reset keys so response is an array, not associative
          return array_values($users);
     }





     public function getUserById($id)
     {
          $db = \Config\Database::connect();
          $builder = $db->table('employees');

          // Join with employee_documents table for document details
          $builder->select('
        employees.emp_id, 
        employees.employee_code, 
        employees.employee_name as employee_name,
        employees.designation, 
        employees.department, 
        employees.joining_date, 
        employees.employment_type, 
        employees.mobile, 
        employees.email, 
        employees.dob, 
        employees.gender, 
        employees.father_husband_name, 
        employees.marital_status, 
        employees.blood_group, 
        employees.religion, 
        employees.caste, 
        employees.department_category, 
        employees.main_department, 
        employees.sub_department, 
        employees.designation_name, 
        employees.grade_name, 
        employees.position, 
        employees.reporting_manager_name, 
        employees.reporting_manager_empcode, 
        employees.functional_manager_name, 
        employees.skip_level_manager_empcode, 
        employees.shift_description,  
        employees.total_experience, 
        employees.previous_company, 
        employees.previous_designation, 
        employees.previous_experience_years, 
        employees.bank_account_name, 
        employees.bank_account_number, 
        employees.ifsc_code, 
        employees.ctc, 
        employees.latest_agreement_valid_date, 
        employees.latest_agreement_end_date, 
        employees.latest_contract_fee_revision_amount, 
        employees.resignation, 
        employees.resignation_date, 
        employees.relieving_date, 
        employees.last_working_date, 
        employees.last_pay_date, 
        employees.separation_status, 
        employees.notice_period, 
        employees.status, 
        employees.created_at, 
        employees.updated_at,
        employee_documents.document_name, 
        employee_documents.document_path
    ');

          // Join employee_documents with the employees table based on emp_id
          $builder->join('employee_documents', 'employee_documents.emp_id = employees.emp_id', 'left');

          // Apply status filter and ordering
          $builder->where('employees.status', 'A');
          $builder->where('employees.emp_id', $id);
          $builder->orderBy('employees.emp_id', 'DESC');

          // Get results
          $result = $builder->get()->getResultArray();

          // Organize results by emp_id, merging employee details and documents into the same structure
          $users = [];
          foreach ($result as $row) {
               $emp_id = $row['emp_id'];

               // Initialize user data if not already
               if (!isset($users[$emp_id])) {
                    $users = [
                         'emp_id' => $row['emp_id'],
                         'employee_code' => $row['employee_code'],
                         'employee_name' => $row['employee_name'],
                         'designation' => $row['designation'],
                         'department' => $row['department'],
                         'joining_date' => $row['joining_date'],
                         'employment_type' => $row['employment_type'],
                         'mobile' => $row['mobile'],
                         'email' => $row['email'],
                         'dob' => $row['dob'],
                         'gender' => $row['gender'],
                         'father_husband_name' => $row['father_husband_name'],
                         'marital_status' => $row['marital_status'],
                         'blood_group' => $row['blood_group'],
                         'religion' => $row['religion'],
                         'caste' => $row['caste'],
                         'department_category' => $row['department_category'],
                         'main_department' => $row['main_department'],
                         'sub_department' => $row['sub_department'],
                         'designation_name' => $row['designation_name'],
                         'grade_name' => $row['grade_name'],
                         'position' => $row['position'],
                         'reporting_manager_name' => $row['reporting_manager_name'],
                         'reporting_manager_empcode' => $row['reporting_manager_empcode'],
                         'functional_manager_name' => $row['functional_manager_name'],
                         'skip_level_manager_empcode' => $row['skip_level_manager_empcode'],
                         'shift_description' => $row['shift_description'],
                         'highest_qualification' => $row['highest_qualification'],
                         'university' => $row['university'],
                         'passing_year' => $row['passing_year'],
                         'total_experience' => $row['total_experience'],
                         'previous_company' => $row['previous_company'],
                         'previous_designation' => $row['previous_designation'],
                         'previous_experience_years' => $row['previous_experience_years'],
                         'bank_account_name' => $row['bank_account_name'],
                         'bank_account_number' => $row['bank_account_number'],
                         'ifsc_code' => $row['ifsc_code'],
                         'ctc' => $row['ctc'],
                         'latest_agreement_valid_date' => $row['latest_agreement_valid_date'],
                         'latest_agreement_end_date' => $row['latest_agreement_end_date'],
                         'latest_contract_fee_revision_amount' => $row['latest_contract_fee_revision_amount'],
                         'resignation' => $row['resignation'],
                         'resignation_date' => $row['resignation_date'],
                         'relieving_date' => $row['relieving_date'],
                         'last_working_date' => $row['last_working_date'],
                         'last_pay_date' => $row['last_pay_date'],
                         'separation_status' => $row['separation_status'],
                         'notice_period' => $row['notice_period'],
                         'status' => $row['status'],
                         'created_at' => $row['created_at'],
                         'updated_at' => $row['updated_at'],
                         'documents' => []
                    ];
               }

               // Add document details if they exist
               if ($row['document_name']) {
                    $users['documents'][] = [
                         'document_name' => $row['document_name'],
                         'document_path' => $row['document_path']
                    ];
               }
          }

          // Return merged result
          return $users;
     }

     public function update_password($id, $data)
     {
          return $this->update($id, $data);
     }

     public function update_user_validity($id, $data)
     {
          return $this->update($id, $data);
     }


     public function updateUser($id, $data, $documentData)
     {
          try {
               $db = \Config\Database::connect('default');
               $db->transBegin();

               // ✅ Update employees table
               $builder = $db->table('employees');
               if (!$builder->update($data, ['emp_id' => $id])) {
                    throw new \Exception('Failed to update employees table');
               }

               // ✅ Update users table (if needed)
               if (isset($data['employee_name']) || isset($data['employee_code'])) {
                    $userUpdate = [];
                    if (isset($data['employee_name'])) {
                         $userUpdate['user_name'] = $data['employee_name'];
                    }
                    if (isset($data['employee_code'])) {
                         $userUpdate['user_code'] = $data['employee_code'];
                    }

                    if (!empty($userUpdate)) {
                         $builder = $db->table('users');
                         if (!$builder->update($userUpdate, ['emp_id' => $id])) {
                              throw new \Exception('Failed to update users table');
                         }
                    }
               }

               // ✅ Insert new uploaded documents
               if (!empty($documentData)) {
                    $builder = $db->table('employee_documents');
                    foreach ($documentData as $doc) {
                         $docData = [
                              'emp_id'        => $id,
                              'document_name' => $doc['document_name'],
                              'document_path' => $doc['document_path'],
                              'uploaded_at'   => $doc['uploaded_at'],
                         ];

                         if (!$builder->insert($docData)) {
                              throw new \Exception('Failed to insert document: ' . $doc['document_name']);
                         }
                    }
               }

               $db->transCommit();

               return [
                    'status' => true,
                    'emp_id' => $id
               ];
          } catch (\Exception $e) {
               $db->transRollback();
               log_message('error', 'Update User Error: ' . $e->getMessage());

               return [
                    'status'  => false,
                    'message' => 'Exception: ' . $e->getMessage()
               ];
          }
     }








     public function insertUserData($data)
     {
          try {
               $db = \Config\Database::connect('default');
               $db->transBegin(); // Begin transaction

               // Tab1 → doctors
               $builder = $db->table('doctors');
               if (!$builder->insert($data['tab1'])) {
                    throw new \Exception('Failed to insert into doctors table');
               }

               $userId = $db->insertID();

               // Users table (for login)
               $user_name = $data['tab1']['displayName'] ?? '';
               $emp_code  = $data['tab1']['consultationCode'] ?? '';

               $user_data = [
                    'user_name'      => $user_name,
                    'user_code'      => $emp_code,
                    'password'       => md5('adnet2008'),
                    'status'         => 'A',
                    'disabled'       => 'N',
                    'validity'       => date('Y-m-d', strtotime('+90 days')),
                    'failed_attems'  => 0,
                    'is_admin'       => 'N',
                    'exit_date'      => '0000-00-00',
                    'role'           => '',
               ];

               $builder = $db->table('users');
               if (!$builder->insert($user_data)) {
                    throw new \Exception('Failed to insert into users table');
               }

               // Repeat pattern for all tabs
               if (!empty($data['tab2'])) {
                    $builder = $db->table('doc_contract');
                    if (!$builder->insert(array_merge($data['tab2'], ['user_id' => $userId]))) {
                         throw new \Exception('Failed to insert into doc_contract');
                    }
               }

               if (!empty($data['tab3'])) {
                    $builder = $db->table('doc_contact');
                    if (!$builder->insert(array_merge($data['tab3'], ['user_id' => $userId]))) {
                         throw new \Exception('Failed to insert into doc_contact');
                    }
               }

               if (!empty($data['tab4']['qualifications'])) {
                    $builder = $db->table('doc_qualifications');
                    foreach ($data['tab4']['qualifications'] as $qual) {
                         if (!$builder->insert(array_merge($qual, ['user_id' => $userId]))) {
                              throw new \Exception('Failed to insert into doc_qualifications');
                         }
                    }
               }

               // Tab4: doc_qualifications_1 (NEW)
               if (!empty($data['tab4'])) {
                    $builder = $db->table('doc_qualifications_1');
                    $qual1 = [
                         'user_id'                  => $userId,
                         'mbbsYearOfPassing'        => $data['tab4']['mbbsYearOfPassing'] ?? null,
                         'pgYearOfPassing'          => $data['tab4']['pgYearOfPassing'] ?? null,
                         'additionalQualification'  => $data['tab4']['additionalQualification'] ?? null,
                         'qualification'            => $data['tab4']['qualification'] ?? null,
                         'pgCollege'                => $data['tab4']['pgCollege'] ?? null,
                         'mbbsCollege'              => $data['tab4']['mbbsCollege'] ?? null,
                    ];
                    if (!$builder->insert($qual1)) {
                         throw new \Exception('Failed to insert into doc_qualifications_1');
                    }
               }

               if (!empty($data['tab5']['earnings'])) {
                    $builder = $db->table('doc_earnings');
                    foreach ($data['tab5']['earnings'] as $earn) {
                         if (!$builder->insert(array_merge($earn, ['user_id' => $userId]))) {
                              throw new \Exception('Failed to insert into doc_earnings');
                         }
                    }
               }

               // Tab5: doc_earnings_1 (NEW)
               if (!empty($data['tab5'])) {
                    $builder = $db->table('doc_earnings_1');
                    $earn1 = [
                         'user_id'                => $userId,
                         'ctc'                    => $data['tab5']['ctc'] ?? null,
                         'lopDays'                => $data['tab5']['lopDays'] ?? null,
                         'gratuityCalculatedDate' => $data['tab5']['gratuityCalculatedDate'] ?? null,
                         'gratuityPaidAmount'     => $data['tab5']['gratuityPaidAmount'] ?? null,
                    ];
                    if (!$builder->insert($earn1)) {
                         throw new \Exception('Failed to insert into doc_earnings_1');
                    }
               }

               if (!empty($data['tab6']['deductions'])) {
                    $builder = $db->table('doc_deductions');
                    foreach ($data['tab6']['deductions'] as $deduct) {
                         if (!$builder->insert(array_merge($deduct, ['user_id' => $userId]))) {
                              throw new \Exception('Failed to insert into doc_deductions');
                         }
                    }
               }

               if (!empty($data['tab7']['loans'])) {
                    $builder = $db->table('doc_loans');
                    foreach ($data['tab7']['loans'] as $loan) {
                         if (!$builder->insert(array_merge($loan, ['user_id' => $userId]))) {
                              throw new \Exception('Failed to insert into doc_loans');
                         }
                    }
               }

               if (!empty($data['tab8']['leaves'])) {
                    $builder = $db->table('doc_leaves');
                    foreach ($data['tab8']['leaves'] as $leave) {
                         if (!$builder->insert(array_merge($leave, ['user_id' => $userId]))) {
                              throw new \Exception('Failed to insert into doc_leaves');
                         }
                    }
               }

               $tab8 = $data['tab8'];
               unset($tab8['leaves']);
               if (!empty($tab8)) {
                    $builder = $db->table('doc_air_ticket_policy');
                    if (!$builder->insert(array_merge($tab8, ['user_id' => $userId]))) {
                         throw new \Exception('Failed to insert into doc_air_ticket_policy');
                    }
               }

               if (!empty($data['tab9'])) {
                    $builder = $db->table('doc_pf_details');
                    if (!$builder->insert(array_merge($data['tab9'], ['user_id' => $userId]))) {
                         throw new \Exception('Failed to insert into doc_pf_details');
                    }
               }

               // ✅ Commit transaction
               $db->transCommit();

               return ['status' => true, 'user_id' => $userId];
          } catch (\Exception $e) {
               // ❌ Rollback transaction on any error
               $db->transRollback();
               log_message('error', 'Insert User Error: ' . $e->getMessage());
               return ['status' => false, 'message' => 'Exception: ' . $e->getMessage()];
          }
     }



     public function updateUserData($userId, $data)
     {
          try {
               $db = \Config\Database::connect('default');
               $db->transStart();

               // Tab1 → users
               if (isset($data['tab1'])) {
                    $builder = $db->table('doctors');
                    $builder->where('user_id', $userId)->update($data['tab1']);
               }
               // Tab2 → user_contract
               if (isset($data['tab2'])) {
                    if (!empty($data['tab2'])) {
                         $builder = $db->table('doc_contract');
                         $builder->where('user_id', $userId)->update($data['tab2']);
                    }
               }

               // Tab3 → user_contact
               if (isset($data['tab3'])) {
                    if (!empty($data['tab3'])) {
                         $builder = $db->table('doc_contact');
                         $builder->where('user_id', $userId)->update($data['tab3']);
                    }
               }

               // Clear and reinsert multiple-tab data
               if (isset($data['tab4'])) {
                    $builder = $db->table('doc_qualifications');
                    $builder->where('user_id', $userId)->delete();
                    if (!empty($data['tab4']['qualifications'])) {
                         foreach ($data['tab4']['qualifications'] as $qual) {
                              $builder->insert(array_merge($qual, ['user_id' => $userId]));
                         }
                    }
               }
               if (isset($data['tab5'])) {
                    $builder = $db->table('doc_earnings');
                    $builder->where('user_id', $userId)->delete();
                    if (!empty($data['tab5']['earnings'])) {
                         foreach ($data['tab5']['earnings'] as $earn) {
                              $builder->insert(array_merge($earn, ['user_id' => $userId]));
                         }
                    }
               }

               if (isset($data['tab6'])) {
                    $builder = $db->table('doc_deductions');
                    $builder->where('user_id', $userId)->delete();
                    if (!empty($data['tab6']['deductions'])) {
                         foreach ($data['tab6']['deductions'] as $deduct) {
                              $builder->insert(array_merge($deduct, ['user_id' => $userId]));
                         }
                    }
               }

               if (isset($data['tab7'])) {
                    $builder = $db->table('doc_loans');
                    $builder->where('user_id', $userId)->delete();
                    if (!empty($data['tab7']['loans'])) {
                         foreach ($data['tab7']['loans'] as $loan) {
                              $builder->insert(array_merge($loan, ['user_id' => $userId]));
                         }
                    }
               }

               if (isset($data['tab8'])) {
                    $builder = $db->table('doc_leaves');
                    $builder->where('user_id', $userId)->delete();
                    if (!empty($data['tab8']['leaves'])) {
                         foreach ($data['tab8']['leaves'] as $leave) {
                              $builder->insert(array_merge($leave, ['user_id' => $userId]));
                         }
                    }

                    $builder = $db->table('doc_air_ticket_policy');
                    $builder->where('user_id', $userId)->delete();
                    $tab8 = $data['tab8'];
                    unset($tab8['leaves']);
                    if (!empty($tab8)) {
                         $builder->insert(array_merge($tab8, ['user_id' => $userId]));
                    }
               }

               if (isset($data['tab9'])) {
                    $builder = $db->table('doc_pf_details');
                    $builder->where('user_id', $userId)->update($data['tab9']);
               }

               $db->transComplete();

               if ($db->transStatus() === false) {
                    return ['status' => false, 'message' => 'Update failed'];
               }

               return ['status' => true];
          } catch (\Exception $e) {
               log_message('error', 'Update Error: ' . $e->getMessage());
               return ['status' => false, 'message' => $e->getMessage()];
          }
     }



     //  public function getEmpByIdNew($id)
     //      {
     //           $db = \Config\Database::connect();
     //           $builder = $db->table('employees');

     //           // Select only columns that exist in your employees table
     //           $builder->select('
     //         employees.emp_id, 
     //         employees.employee_code, 
     //         employees.employee_name as employee_name,
     //         employees.designation, 
     //         employees.department, 
     //         employees.joining_date, 
     //         employees.employment_type, 
     //         employees.mobile, 
     //         employees.email, 
     //         employees.dob, 
     //         employees.gender, 
     //         employees.father_husband_name, 
     //         employees.marital_status, 
     //         employees.blood_group, 
     //         employees.religion, 
     //         employees.caste, 
     //         employees.department_category, 
     //         employees.main_department, 
     //         employees.sub_department, 
     //         employees.designation_name, 
     //         employees.grade_name, 
     //         employees.position, 
     //         employees.reporting_manager_name, 
     //         employees.reporting_manager_empcode, 
     //         employees.functional_manager_name, 
     //         employees.skip_level_manager_empcode, 
     //         employees.shift_description, 
     //         employees.total_experience, 
     //         employees.bank_account_name, 
     //         employees.bank_account_number, 
     //         employees.ifsc_code, 
     //         employees.ctc, 
     //         employees.latest_agreement_valid_date, 
     //         employees.latest_agreement_end_date, 
     //         employees.latest_contract_fee_revision_amount, 
     //         employees.resignation, 
     //         employees.resignation_date, 
     //         employees.relieving_date, 
     //         employees.last_working_date, 
     //         employees.last_pay_date, 
     //         employees.separation_status, 
     //         employees.notice_period, 
     //         employees.status, 
     //         employees.created_at, 
     //         employees.updated_at,
     //         employee_documents.document_name, 
     //         employee_documents.document_path
     //     ');

     public function getUserByIdForImage($id)
     {
          $db = \Config\Database::connect();
          $user = [];

          $user['tab1'] = $db->table('doctors')->where('user_id', $id)->get()->getRowArray();


          return $user; // ✅ return array, NOT setJSON
     }


     public function deleteUserData($id)
     {
          // Start a database transaction for atomic operations
          $this->db->transStart();

          // First, fetch the documents associated with the employee to delete them from the file system
          $documents = $this->db->table('employee_documents')
               ->where('emp_id', $id)
               ->get()
               ->getResultArray();

          // Delete the related documents from the 'employee_documents' table (if any)
          foreach ($documents as $document) {
               // Assuming the 'document_path' column contains the file path
               $filePath = FCPATH . $document['document_path'];  // FCPATH is the root path to your project

               // Check if the file exists before trying to delete it
               if (file_exists($filePath)) {
                    unlink($filePath);  // Delete the file
               }
          }

          // Delete employee-related records from employees, employee_documents, and users tables
          $this->db->table('employees')->where('emp_id', $id)->delete();
          $this->db->table('employee_documents')->where('emp_id', $id)->delete();
          $this->db->table('users')->where('emp_id', $id)->delete();

          // Complete the transaction
          $this->db->transComplete();

          // Check if the transaction was successful
          if ($this->db->transStatus() === false) {
               return [
                    'status' => false,
                    'message' => 'Failed to delete user.'
               ];
          }

          // Return success message if everything went fine
          return [
               'status' => true,
               'message' => 'User deleted successfully.'
          ];
     }


     public function deleteDocData($id)
     {
          // Start a database transaction for atomic operations
          $this->db->transStart();

          // First, fetch the documents associated with the employee to delete them from the file system
          $documents = $this->db->table('employee_documents')
               ->where('doc_id', $id)
               ->get()
               ->getResultArray();

          // Delete the related documents from the 'employee_documents' table (if any)
          foreach ($documents as $document) {
               // Assuming the 'document_path' column contains the file path
               $filePath = FCPATH . $document['document_path'];  // FCPATH is the root path to your project

               // Check if the file exists before trying to delete it
               if (file_exists($filePath)) {
                    unlink($filePath);  // Delete the file
               }
          }

          // Delete employee-related records from employees, employee_documents, and users tables

          $this->db->table('employee_documents')->where('doc_id', $id)->delete();


          // Complete the transaction
          $this->db->transComplete();

          // Check if the transaction was successful
          if ($this->db->transStatus() === false) {
               return [
                    'status' => false,
                    'message' => 'Failed to delete user.'
               ];
          }

          // Return success message if everything went fine
          return [
               'status' => true,
               'message' => 'User deleted successfully.'
          ];
     }


     public function updateDocData($id, $data, $file = null)
     {

          // Start a database transaction
          $this->db->transStart();

          // If a new file is uploaded, replace the old one
          if ($file !== null && $file->isValid() && !$file->hasMoved()) {
               // Fetch old document details
               $oldDoc = $this->db->table('employee_documents')
                    ->where('doc_id', $id)
                    ->get()
                    ->getRowArray();

               if ($oldDoc && !empty($oldDoc['document_path'])) {
                    $oldFilePath = FCPATH . $oldDoc['document_path'];
                    if (file_exists($oldFilePath)) {
                         unlink($oldFilePath); // delete old file
                    }
               }

               // Save new file
               $newFileName = $file->getRandomName();

               $file->move(FCPATH . 'uploads/documents/', $newFileName);

               // Update the path in $data
               $data['document_path'] = 'uploads/documents/' . $newFileName;
          }

          // Update document details in DB
          $this->db->table('employee_documents')
               ->where('doc_id', $id)
               ->update($data);

          // Complete the transaction
          $this->db->transComplete();

          // Check transaction status
          if ($this->db->transStatus() === false) {
               return [
                    'status' => false,
                    'message' => 'Failed to update document.'
               ];
          }

          return [
               'status' => true,
               'message' => 'Document updated successfully.'
          ];
     }


     public function update_validity($emp_code, $data)
     {
          return $this->update($emp_code, $data);
     }







     public function getUserAttendance($userId)
     {
          $db = \Config\Database::connect('secondary');
          $builder = $db->table('new_punch_list');

          $today = date('Y-m-d');
          $startOfWeek = date('Y-m-d', strtotime('monday this week'));
          $endOfWeek   = date('Y-m-d', strtotime('sunday this week'));
          $startOfMonth = date('Y-m-01');
          $currentDayOfMonth = date('j'); // e.g., 1 if today is 1st, 15 if today is 15th

          // ✅ Today’s Punch In & Punch Out
          $todayData = $builder->select('MIN(LogDate) as punch_in, MAX(LogDate) as punch_out')
               ->where('UserId', $userId)
               ->where('DATE(LogDate)', $today)
               ->where('status', '1')
               ->get()->getRowArray();

          // ✅ Weekly Present Days
          $weeklyPresent = $builder->select('COUNT(DISTINCT DATE(LogDate)) as present_days')
               ->where('UserId', $userId)
               ->where('DATE(LogDate) >=', $startOfWeek)
               ->where('DATE(LogDate) <=', $today) // ✅ count only till today
               ->where('status', '1')
               ->get()->getRowArray()['present_days'];

          $weeklyTotalDays = date('N', strtotime($today)); // ✅ days passed in this week (Mon=1 … Today)
          $weeklyAbsent = max(0, ($weeklyTotalDays - $weeklyPresent));

          // ✅ Monthly Present Days
          $monthlyPresent = $builder->select('COUNT(DISTINCT DATE(LogDate)) as present_days')
               ->where('UserId', $userId)
               ->where('DATE(LogDate) >=', $startOfMonth)
               ->where('DATE(LogDate) <=', $today) // ✅ count only till today
               ->where('status', '1')
               ->get()->getRowArray()['present_days'];

          $monthlyTotalDays = $currentDayOfMonth; // ✅ count only days passed in this month
          $monthlyAbsent = max(0, ($monthlyTotalDays - $monthlyPresent));

          return [
               'today' => [
                    'punch_in'  => $todayData['punch_in'] ?? null,
                    'punch_out' => $todayData['punch_out'] ?? null,
               ],
               'this_week' => [
                    'present_days' => $weeklyPresent,
                    'absent_days'  => $weeklyAbsent,
               ],
               'this_month' => [
                    'present_days' => $monthlyPresent,
                    'absent_days'  => $monthlyAbsent,
               ]
          ];
     }


     public function getMonthlyAttendance($userCode, $year, $month)
     {
          $db = \Config\Database::connect('secondary');
          $defaultDB = \Config\Database::connect('default');
          $builder = $db->table('new_punch_list');

          if (empty($year)) {
               $year = date('Y'); // Current year
          }
          if (empty($month)) {
               $month = date('n'); // Current month (1-12)
          }

          // Create proper date range for the specified year and month
          $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
          $endOfMonth = date('Y-m-t', strtotime($startOfMonth)); // Last day of the specified month

          // Fetch each day's punch in/out for the specified user, year, and month
          $query = $builder->select('DATE(LogDate) as date, MIN(LogDate) as punch_in, MAX(LogDate) as punch_out')
               ->where('UserId', $userCode)
               ->where('DATE(LogDate) >=', $startOfMonth)
               ->where('DATE(LogDate) <=', $endOfMonth)
               ->where('status', '1')
               ->groupBy('DATE(LogDate)')
               ->orderBy('DATE(LogDate)', 'ASC')
               ->get();

          $attendanceDays = $query->getResultArray();

          // Calculate summary for the specified month
          $monthlyPresent = count($attendanceDays);
          $monthlyTotalDays = date('t', strtotime($startOfMonth)); // Total days in the specified month
          $monthlyAbsent = $monthlyTotalDays - $monthlyPresent;

          // Fetch week_off from employees table, default to Sunday if not found
          $employeeData = $defaultDB->table('employees')
               ->select('week_off')
               ->where('employee_code', $userCode)
               ->get()
               ->getRowArray();

          $weekoff = (!empty($employeeData) && !empty($employeeData['week_off']))
               ? $employeeData['week_off']
               : 'Sunday';

          // Fetch holidays list from holidays table for the month and year
          $holidays = $defaultDB->table('holiday')
               ->select('date as holiday_date, holiday as holiday_name')
               ->where('YEAR(date)', $year)
               ->where('MONTH(date)', $month)
               ->where('status', 'A')
               ->get()
               ->getResultArray();

          return [
               'month_range' => [
                    'start' => $startOfMonth,
                    'end'   => $endOfMonth,
                    'year'  => (int)$year,
                    'month' => (int)$month,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
               ],
               'attendance' => [
                    'present_days' => $monthlyPresent,
                    'absent_days'  => $monthlyAbsent,
                    'total_days'   => $monthlyTotalDays,
               ],
               'employee_info' => [
                    'week_off' => $weekoff,
               ],
               'holidays' => $holidays, // <-- Added holidays here
               'days' => $attendanceDays
          ];
     }


     // public function getMonthlyAttendance($userId)
     // {
     //     $db = \Config\Database::connect('secondary');
     //     $builder = $db->table('new_punch_list');

     //     $startOfMonth = date('Y-m-01'); // First day of this month
     //     $endOfMonth   = date('Y-m-t');  // Last day of this month

     //     // ✅ Fetch punch-in (earliest IN) and punch-out (latest OUT) per day
     //     $query = $builder->select("
     //             DATE(LogDate) as date,
     //             MIN(CASE WHEN Direction = 'OUT' THEN LogDate END)  as punch_in,
     //             MAX(CASE WHEN Direction = 'OUT' THEN LogDate END) as punch_out
     //         ")
     //         ->where('UserId', $userId)
     //         ->where('DATE(LogDate) >=', $startOfMonth)
     //         ->where('DATE(LogDate) <=', $endOfMonth)
     //         // ->where('status', '1')
     //         ->groupBy('DATE(LogDate)')
     //         ->orderBy('DATE(LogDate)', 'ASC')
     //         ->get();

     //     $attendanceDays = $query->getResultArray();

     //     // ✅ Format times properly
     //     foreach ($attendanceDays as &$day) {
     //         $day['punch_in']  = !empty($day['punch_in']) ? date('H:i:s', strtotime($day['punch_in'])) : '--';
     //         $day['punch_out'] = !empty($day['punch_out']) ? date('H:i:s', strtotime($day['punch_out'])) : '--';

     //         // 🛠️ Extra fix: if no "IN" but first record exists, take earliest LogDate
     //         if ($day['punch_in'] === '--') {
     //             $sub = $db->table('new_punch_list')
     //                 ->select('MIN(LogDate) as first_entry')
     //                 ->where('UserId', $userId)
     //                 ->where('DATE(LogDate)', $day['date'])
     //                 ->where('status', '1')
     //                 ->get()
     //                 ->getRow();
     //             if ($sub && $sub->first_entry) {
     //                 $day['punch_in'] = date('H:i:s', strtotime($sub->first_entry));
     //             }
     //         }

     //         // 🛠️ Extra fix: if no "OUT" but last record exists, take latest LogDate
     //         if ($day['punch_out'] === '--') {
     //             $sub = $db->table('new_punch_list')
     //                 ->select('MAX(LogDate) as last_entry')
     //                 ->where('UserId', $userId)
     //                 ->where('DATE(LogDate)', $day['date'])
     //                 ->where('status', '1')
     //                 ->get()
     //                 ->getRow();
     //             if ($sub && $sub->last_entry) {
     //                 $day['punch_out'] = date('H:i:s', strtotime($sub->last_entry));
     //             }
     //         }
     //     }

     //     // ✅ Calculate summary
     //     $monthlyPresent   = count($attendanceDays);
     //     $monthlyTotalDays = date('t'); // total days in month
     //     $monthlyAbsent    = $monthlyTotalDays - $monthlyPresent;

     //     return [
     //         'month_range' => [
     //             'start' => $startOfMonth,
     //             'end'   => $endOfMonth,
     //         ],
     //         'attendance' => [
     //             'present_days' => $monthlyPresent,
     //             'absent_days'  => $monthlyAbsent,
     //         ],
     //         'days' => $attendanceDays
     //     ];
     // }



     public function getRadiologyDoctor()
     {
          $db = \Config\Database::connect();
          $query = $db->query("
        SELECT 
            c.id AS candidate_id,
            c.candidate_name,
            c.position_applied,
            c.mobile,
            
            p.assessment_date,
            GROUP_CONCAT(DISTINCT p.criteria) AS criteria,
            GROUP_CONCAT(DISTINCT p.rating) AS rating,
            GROUP_CONCAT(DISTINCT p.remarks) AS remarks,

            GROUP_CONCAT(DISTINCT mo.mod_name) AS modality_names,
            GROUP_CONCAT(DISTINCT m.mod_id) AS mod_ids,
            GROUP_CONCAT(DISTINCT m.sub_mod_id) AS sub_mod_ids,
            GROUP_CONCAT(DISTINCT sm.id) AS sub_modality_ids,
            GROUP_CONCAT(DISTINCT sm.sub_mod_name) AS sub_modality_names,
            GROUP_CONCAT(DISTINCT sm.status) AS sub_modality_statuses,

            GROUP_CONCAT(DISTINCT sc.id) AS sub_mod_class_ids,
            GROUP_CONCAT(DISTINCT sc.sub_mod_class) AS sub_mod_class_names,

            GROUP_CONCAT(DISTINCT t.assessment) AS assessments,
            GROUP_CONCAT(DISTINCT t.notes) AS notes,

            GROUP_CONCAT(DISTINCT rr.region_name) AS region_names

        FROM radiology_candidate c

        LEFT JOIN radiology_personality_assessment p ON c.id = p.candidate_id
        LEFT JOIN radiology_modalities m ON c.id = m.candidate_id
        LEFT JOIN radiology_technical_evaluation t ON c.id = t.candidate_id
        LEFT JOIN modalities mo ON m.mod_id = mo.id
        LEFT JOIN sub_mod_class sc ON m.sub_mod_class = sc.id
        LEFT JOIN sub_modalities sm ON m.sub_mod_id = sm.id
        LEFT JOIN radiology_regions rr ON c.id = rr.candidate_id

        GROUP BY c.id
    ");

          return $query->getResult();
     }


     public function getRadiologyDoctorId($id)
     {
          $db = \Config\Database::connect();
          $query = $db->query("
        SELECT 
            c.id AS candidate_id,
            c.candidate_name,
            c.position_applied,
            c.mobile,
            c.email,
            
            p.assessment_date,
            GROUP_CONCAT(DISTINCT p.criteria) AS criteria,
            GROUP_CONCAT(DISTINCT p.rating) AS rating,
            GROUP_CONCAT(DISTINCT p.remarks) AS remarks,

            GROUP_CONCAT(DISTINCT mo.mod_name) AS modality_names,
            GROUP_CONCAT(DISTINCT m.mod_id) AS mod_ids,
            GROUP_CONCAT(DISTINCT m.sub_mod_id) AS sub_mod_ids,
            GROUP_CONCAT(DISTINCT sm.id) AS sub_modality_ids,
            GROUP_CONCAT(DISTINCT sm.sub_mod_name) AS sub_modality_names,
            GROUP_CONCAT(DISTINCT sm.status) AS sub_modality_statuses,

            GROUP_CONCAT(DISTINCT sc.id) AS sub_mod_class_ids,
            GROUP_CONCAT(DISTINCT sc.sub_mod_class) AS sub_mod_class_names,

            GROUP_CONCAT(DISTINCT t.id) AS t_ids,
            GROUP_CONCAT(DISTINCT t.particular) AS particulars,
            GROUP_CONCAT(DISTINCT t.assessment) AS assessments,
            GROUP_CONCAT(DISTINCT t.notes) AS notes,

            GROUP_CONCAT(DISTINCT rr.id) AS rr_ids,
            GROUP_CONCAT(DISTINCT rr.region_name) AS region_names

        FROM radiology_candidate c

        LEFT JOIN radiology_personality_assessment p ON c.id = p.candidate_id
        LEFT JOIN radiology_modalities m ON c.id = m.candidate_id
        LEFT JOIN radiology_technical_evaluation t ON c.id = t.candidate_id
        LEFT JOIN modalities mo ON m.mod_id = mo.id
        LEFT JOIN sub_mod_class sc ON m.sub_mod_class = sc.id
        LEFT JOIN sub_modalities sm ON m.sub_mod_id = sm.id
        LEFT JOIN radiology_regions rr ON c.id = rr.candidate_id

        WHERE c.id = ?
        GROUP BY c.id
    ", [$id]);  // Passing $id as parameter to prevent SQL injection

          return $query->getRow(); // Since you're filtering by ID, return a single row
     }



     public function getCandidateInfo($candidateId)
     {
          $db = \Config\Database::connect();
          return  $db->table('radiology_candidate')
               ->select('id, candidate_name, position_applied, mobile,email,rpt_mgr_status,rm_name,overallNotes,manager_approved_date,total_experience,timing_from,timing_to,Paediatric,education_qualification,department,medical_registration,vdc_location,association_type,hr_status, hr_approved_date, hr_name')
               ->where('id', $candidateId)
               ->get()
               ->getRowArray();
     }

     public function getCandidateRegions($candidateId)
     {
          $db = \Config\Database::connect();
          return $db->table('radiology_regions')
               ->select('id,region_name,branch_name')
               ->where('candidate_id', $candidateId)
               ->get()
               ->getResultArray();
     }

     public function getCandidatePersonalityAssessments($candidateId)
     {
          $db = \Config\Database::connect();
          return $db->table('radiology_personality_assessment')
               ->select('id,assessment_date, criteria, rating, remarks')
               ->where('candidate_id', $candidateId)
               ->get()
               ->getResultArray();
     }

     public function getCandidateModalities($candidateId)
     {
          $db = \Config\Database::connect();
          return $db->table('candidate_modalities')
               ->select('id,modality, selection_area, level, applicable')
               ->where('candidate_id', $candidateId)
               ->get()
               ->getResultArray();
     }

     public function getCandidateModalities_old($candidateId)
     {
          $db = \Config\Database::connect();
          return $db->table('radiology_modalities m')
               ->select('
                m.id,
                m.mod_id, 
                mo.mod_name, 
                m.sub_mod_id, 
                sm.sub_mod_name, 
                sc.id as sub_mod_class_id, 
                sc.sub_mod_class as sub_mod_class_name
            ')
               ->join('modalities mo', 'mo.id = m.mod_id', 'left')
               ->join('sub_modalities sm', 'sm.id = m.sub_mod_id', 'left')
               ->join('sub_mod_class sc', 'sc.id = m.sub_mod_class', 'left')
               ->where('m.candidate_id', $candidateId)
               ->get()
               ->getResultArray();
     }

     public function getCandidateTechnicalEvaluations($candidateId)
     {
          $db = \Config\Database::connect();
          return $db->table('radiology_technical_evaluation')
               ->select('id,particular,assessment, notes, grading')
               ->where('candidate_id', $candidateId)
               ->get()
               ->getResultArray();
     }


     public function getAllCandidatesFullDetails()
     {
          $db = \Config\Database::connect();

          $candidates = $db->table('radiology_candidate c')
               ->select('c.id, c.candidate_name, c.position_applied, c.mobile, c.email, c.manager_approved_date, c.hr_approved_date')
               ->get()
               ->getResultArray();

          foreach ($candidates as &$candidate) {
               $candidateId = $candidate['id'];

               $candidate['regions'] = $this->getCandidateRegions($candidateId);
               $candidate['personality_assessments'] = $this->getCandidatePersonalityAssessments($candidateId);
               $candidate['modalities'] = $this->getCandidateModalities($candidateId);
               $candidate['technical_evaluations'] = $this->getCandidateTechnicalEvaluations($candidateId);
          }

          return $candidates;
     }

     public function getUserCandidatesFullDetails($id)
     {
          $db = \Config\Database::connect();

          $candidates = $db->table('radiology_candidate c')
               ->select('c.id, c.candidate_name, c.position_applied, c.mobile, c.email')
               ->where('c.created_by', $id)
               ->get()
               ->getResultArray();

          foreach ($candidates as &$candidate) {
               $candidateId = $candidate['id'];

               $candidate['regions'] = $this->getCandidateRegions($candidateId);
               $candidate['personality_assessments'] = $this->getCandidatePersonalityAssessments($candidateId);
               $candidate['modalities'] = $this->getCandidateModalities($candidateId);
               $candidate['technical_evaluations'] = $this->getCandidateTechnicalEvaluations($candidateId);
          }

          return $candidates;
     }


     public function updateManagerStatusId($id, $data)
     {

          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_candidate');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function updateHrStatusId($id, $data)
     {

          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_candidate');
          $builder->where('id', $id);
          return $builder->update($data);
     }


     public function updateRadiologyUser($id, $data)
     {

          $db = \Config\Database::connect('default');
          $builder = $db->table('users');
          $builder->where('id', $id);
          return $builder->update($data);
     }

     public function insertRadiologyRegion($data)
     {
          $db = \Config\Database::connect('default');
          return $db->table('radiology_regions')->insertBatch($data);
     }




     // public function insertCandidate($data)
     // {
     //      $db = \Config\Database::connect('default');
     //     return $db->table('radiology_candidate')->insertBatch($data);
     // }

     public function insertCandidate($data)
     {
          $db = \Config\Database::connect('default');
          $builder = $db->table('radiology_candidate');

          // Insert the data
          $inserted = $builder->insert($data);

          // Return inserted ID if successful, else return false
          if ($inserted) {
               return $db->insertID();
          } else {
               return false;
          }
     }


     public function insertModality($data)
     {
          $db = \Config\Database::connect('default');
          return $db->table('candidate_modalities')->insertBatch($data);
     }


     public function insertTechnical($data)
     {
          $db = \Config\Database::connect('default');
          return $db->table('radiology_technical_evaluation')->insertBatch($data);
     }


     public function insertPersonality($data)
     {
          $db = \Config\Database::connect('default');
          return $db->table('radiology_personality_assessment')->insertBatch($data);
     }


     public function insertRegion($data)
     {
          $db = \Config\Database::connect('default');
          return $db->table('radiology_regions')->insertBatch($data);
     }


     public function getStatus($user)
     {
          $db = \Config\Database::connect('default');

          // ====== 1. All Candidates - rpt_mgr_status ======
          $builder = $db->table('radiology_candidate');
          $builder->select("rpt_mgr_status, COUNT(*) as count");
          $builder->groupBy("rpt_mgr_status");
          $rptMgrResultsAll = $builder->get()->getResultArray();

          // ====== 2. All Candidates - hr_status ======
          $builder = $db->table('radiology_candidate');
          $builder->select("hr_status, COUNT(*) as count");
          $builder->groupBy("hr_status");
          $hrResultsAll = $builder->get()->getResultArray();

          // ====== 3. User-Specific - rpt_mgr_status ======
          $builder = $db->table('radiology_candidate');
          $builder->select("rpt_mgr_status, COUNT(*) as count");
          $builder->where('created_by', $user);
          $builder->groupBy("rpt_mgr_status");
          $rptMgrResultsUser = $builder->get()->getResultArray();

          // ====== 4. User-Specific - hr_status ======
          $builder = $db->table('radiology_candidate');
          $builder->select("hr_status, COUNT(*) as count");
          $builder->where('created_by', $user);
          $builder->groupBy("hr_status");
          $hrResultsUser = $builder->get()->getResultArray();

          // ====== Convert to associative arrays ======
          $rptMgrAll = [];
          foreach ($rptMgrResultsAll as $row) {
               $rptMgrAll[$row['rpt_mgr_status']] = (int)$row['count'];
          }

          $hrAll = [];
          foreach ($hrResultsAll as $row) {
               $hrAll[$row['hr_status']] = (int)$row['count'];
          }

          $rptMgrUser = [];
          foreach ($rptMgrResultsUser as $row) {
               $rptMgrUser[$row['rpt_mgr_status']] = (int)$row['count'];
          }

          $hrUser = [];
          foreach ($hrResultsUser as $row) {
               $hrUser[$row['hr_status']] = (int)$row['count'];
          }

          // ====== Return combined data ======
          return [
               'rpt_mgr_status_counts_all' => $rptMgrAll,
               'hr_status_counts_all' => $hrAll,
               'rpt_mgr_status_counts_user' => $rptMgrUser,
               'hr_status_counts_user' => $hrUser,
          ];
     }


     public function getDetails($user)
     {
          $db = \Config\Database::connect('travelapp');
          $builder = $db->table('new_emp_master c');
          $builder->select('c.id, c.emp_code, c.comp_name, c.doj, c.gender, c.mail_id, c.report_mngr, c.function_mngr, c.designation_name, c.location_name, c.dept_name, c.ou_name, c.mobile');
          $builder->where('c.emp_code', $user);
          $query = $builder->get();
          return $query->getResultArray();
     }
}
