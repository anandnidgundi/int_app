<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes 
 */
$routes->GET('/', 'Home::index');
$routes->GET('viewAttachment/(:any)', 'FileUpload::viewAttachment/$1');

$routes->group('api', ['filter' => 'cors'], function ($routes) {

     $routes->POST("register", "Register::index");

     $routes->match(['POST', 'OPTIONS'], 'login', 'Login::index');
     $routes->match(['POST', 'OPTIONS'], 'newLogin', 'Login::newLogin');

     $routes->match(['POST', 'OPTIONS'], 'logout', 'Login::logout', ['filter' => 'jwt']);
     $routes->match(['POST', 'OPTIONS'], 'validate_token', 'Auth::validateToken');
     //$routes->match(['POST', 'options'], "logout", "Logout::index"); // Removed 'api/' prefix here 

     $routes->match(['GET', 'options'], "uniqueDepts", "User::uniqueDepts", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "emp_list", "User::emp_list", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "que_list", "User::que_list", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "emp_pbt_access", "User::emp_pbt_access", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "add_que", "User::add_que", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "get_question/(:segment)", "User::get_question/$1", ['filter' => 'authFilter']);
     $routes->match(['PUT', 'options'], "update_question/(:segment)", "User::update_question/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "add_question_details", "User::add_question_details", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "question_details", "User::question_details", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "get_que_det/(:segment)", "User::get_que_det/$1", ['filter' => 'authFilter']);
     $routes->match(['PUT', 'options'], "update_que_det/(:segment)", "User::update_que_det/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "add_bulk_que", "User::add_bulk_que", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "add_candidate", "User::add_candidate", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "candidate_details", "User::candidate_details", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "questions_by_id/(:segment)", "User::questions_by_id/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "get_details_by_id/(:segment)", "User::get_details_by_id/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "submit_answer", "User::submit_answer", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "time_by_dept/(:segment)", "User::time_by_dept/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "check_otp", "User::check_otp", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "marks", "User::marks", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "get_correct_answers/(:segment)", "User::get_correct_answers/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "roles", "User::roles", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "get_categories", "User::get_categories", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "add_category", "User::add_category", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "get_category_by_id/(:segment)", "User::get_category_by_id/$1", ['filter' => 'authFilter']);
     $routes->match(['PUT', 'options'], "update_category_id/(:segment)", "User::update_category_id/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "times", "User::times", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "add_time", "User::add_time", ['filter' => 'authFilter']);
     $routes->match(['DELETE', 'options'], "delete_time/(:num)", "User::delete_time/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "changeMyPass1", "User::changeMyPass1");
     $routes->match(['GET', 'options'], "get_tiny_details_by_id/(:segment)", "User::get_tiny_details_by_id/$1");
     $routes->match(['GET', 'options'], "get_user_details_by_id/(:segment)", "User::get_user_details_by_id/$1");
     $routes->match(['POST', 'options'], "check_user_otp", "User::check_user_otp");
     $routes->match(['GET', 'options'], "get_details_by_id_tokenless/(:segment)", "User::get_details_by_id_tokenless/$1");
     $routes->match(['GET', 'options'], "questions_by_id_tokenless/(:segment)", "User::questions_by_id_tokenless/$1");
     $routes->match(['POST', 'options'], "submit_answer_tokenless", "User::submit_answer_tokenless");
     $routes->match(['GET', 'options'], "candidatestatuschange/(:segment)", "User::candidatestatuschange/$1");



     $routes->match(['POST', 'options'], "checkUser", "Login::checkUser");
     $routes->match(['GET', 'options'], "profile", "Profile::index", ['filter' => 'authFilter']);


     $routes->match(['POST', 'options'], "uploadFile", "FileUpload::uploadFile", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getFiles", "FileUpload::getFiles", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "download/(:segment)", "FileUpload::download/$1", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "addBM_Task", "BM_Tasks::addBM_Task", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBM_TaskDetails/(:segment)", "BM_Tasks::getBM_TaskDetails/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editBM_Task/(:segment)", "BM_Tasks::editBM_Task/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBM_TaskList", "BM_Tasks::getBM_TaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBranchComboTaskListNew", "BM_Tasks::getBranchComboTaskListNew", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getBM_TaskDetailsByMid/(:segment)", "BM_Tasks::getBM_TaskDetailsByMid/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBM_TaskListForAdmin", "BM_Tasks::getBM_TaskListForAdmin", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBM_TaskListForAdminforbranch", "BM_Tasks::getBM_TaskListForAdminforbranch", ['filter' => 'authFilter']);

     $routes->match(['GET', 'options'], "getDieselConsumptionList/(:segment)", "DieselConsumption::getDieselConsumptionList/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getDieselConsumptionById/(:segment)", "DieselConsumption::getDieselConsumptionById/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addDieselConsumption", "DieselConsumption::addDieselConsumption", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editDieselConsumption/(:segment)", "DieselConsumption::editDieselConsumption/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteDieselConsumption", "DieselConsumption::deleteDieselConsumption", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getDieselConsumptionAdminList/(:segment)", "DieselConsumption::getDieselConsumptionAdminList/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getDieselConsumptionAdminListforbranch/(:segment)", "DieselConsumption::getDieselConsumptionAdminListforbranch/$1", ['filter' => 'authFilter']);

     $routes->match(['GET', 'options'], "getPowerConsumptionList/(:segment)", "PowerConsumption::getPowerConsumptionList/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getPowerConsumptionById/(:segment)", "PowerConsumption::getPowerConsumptionById/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addPowerConsumption", "PowerConsumption::addPowerConsumption", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editPowerConsumption/(:segment)", "PowerConsumption::editPowerConsumption/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deletePowerConsumption", "PowerConsumption::deletePowerConsumption", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getPowerConsumptionAdminList/(:segment)", "PowerConsumption::getPowerConsumptionAdminList/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getPowerConsumptionAdminListforbranch/(:segment)", "PowerConsumption::getPowerConsumptionAdminListforbranch/$1", ['filter' => 'authFilter']);


     $routes->match(['POST', 'options'], "addMorningTask", "Morningtask::addMorningTask", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getMorningTaskDetails", "Morningtask::getMorningTaskDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "saveMorningTaskDetails", "Morningtask::saveMorningTaskDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "uploadedMTlist", "Morningtask::uploadedMTlist", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBranchMorningTaskList", "Morningtask::getBranchMorningTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getMorningTaskDetailsByMid", "Morningtask::getMorningTaskDetailsByMid", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBranchComboTaskList", "Morningtask::getBranchComboTaskList", ['filter' => 'authFilter']);


     $routes->match(['POST', 'options'], "addCmMorningTask", "CmMorningTask::addCmMorningTask", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCmMorningTaskDetails", "CmMorningTask::getCmMorningTaskDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCmMorningTaskDetailsNew", "CmMorningTask::getCmMorningTaskDetailsNew", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "saveCm_morningtaskDetails", "CmMorningTask::saveCm_morningtaskDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "uploadedCmMTtask", "CmMorningTask::uploadedCmMTtask", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCmMorningTaskList", "CmMorningTask::getCmMorningTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBmcMorningTaskList", "CmMorningTask::getBmcMorningTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCMUserBranchList", "CmMorningTask::getCMUserBranchList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCMUserBranchListDetails", "CmMorningTask::getCMUserBranchListDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCMBranchComboTaskList", "CmMorningTask::getCMBranchComboTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getZ_BranchWeeklyList", "CmMorningTask::getZ_BranchWeeklyList", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getZonalManagerBranchList", "CmMorningTask::getZonalManagerBranchList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBm_Z_MorningTaskList", "CmMorningTask::getBm_Z_MorningTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCm_Z_MorningTaskList", "CmMorningTask::getCm_Z_MorningTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBmcWeeklyTaskList", "CmMorningTask::getBmcWeeklyTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCM_BranchMorningTaskList", "CmMorningTask::getCM_BranchMorningTaskList", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "addNightTask", "Nighttask::addNightTask", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getNightTaskDetails", "Nighttask::getNightTaskDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getNightTaskDetailsNew", "Nighttask::getNightTaskDetailsNew", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "saveNightTaskDetails", "Nighttask::saveNightTaskDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "uploadedNightlist", "Nighttask::uploadedNightlist", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBranchNightTaskList", "Nighttask::getBranchNightTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getDocData", "Nighttask::getDocData", ['filter' => 'authFilter']);
     // $routes->match(['POST', 'options'],"getMriData", "Nighttask::getMriData", ['filter' => 'authFilter']);
     // $routes->match(['POST', 'options'],"getCtData", "Nighttask::getCtData", ['filter' => 'authFilter']);
     // $routes->match(['POST', 'options'],"getUsgData", "Nighttask::getUsgData", ['filter' => 'authFilter']);
     // $routes->match(['POST', 'options'],"getXrayData", "Nighttask::getXrayData", ['filter' => 'authFilter']);
     // $routes->match(['POST', 'options'],"getCardioTmtData", "Nighttask::getCardioTmtData", ['filter' => 'authFilter']);
     // $routes->match(['POST', 'options'],"getCardioEcgData", "Nighttask::getCardioEcgData", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getBmWeeklyTaskList", "BMweeklyTask::getBmWeeklyTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "createBmWeeklyTask", "BMweeklyTask::createBmWeeklyTask", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBmWeeklyTask", "BMweeklyTask::getBmWeeklyTask", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "updateBmWeeklyTask", "BMweeklyTask::updateBmWeeklyTask", ['filter' => 'authFilter']);


     $routes->match(['POST', 'options'], "addCmNightTask", "Cm_nighttask::addCmNightTask", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCm_nightTaskDetails", "Cm_nighttask::getCm_nightTaskDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "saveCm_nightTaskDetails", "Cm_nighttask::saveCm_nightTaskDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "uploadedCm_nightlist", "Cm_nighttask::uploadedCm_nightlist", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCmNightTaskList", "Cm_nighttask::getCmNightTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCmNightTaskDetailsNew", "Cm_nighttask::getCmNightTaskDetailsNew", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBmcNightTaskList", "Cm_nighttask::getBmcNightTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "saveCmNightTaskDetails", "Cm_nighttask::saveCmNightTaskDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getBm_Z_NightTaskList", "Cm_nighttask::getBm_Z_NightTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCm_Z_NightTaskList", "Cm_nighttask::getCm_Z_NightTaskList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCM_BranchNightTaskList", "Cm_nighttask::getCM_BranchNightTaskList", ['filter' => 'authFilter']);

     $routes->match(['GET', 'options'], "users", "User::index", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getempcodes", "User::getEmpCodes", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "changeEmpPass", "User::changeEmpPass", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "changeMyPass", "User::changeMyPass", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getUsersList", "User::getUsersList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addUser", "User::addUser", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editUser", "User::editUser", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteUser", "User::deleteUser", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "resetPass", "User::resetPass", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addRoleToEmp", "User::addRoleToEmp", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addAreaToEmp", "User::addAreaToEmp", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addBranchOrClusterToEmp", "User::addBranchOrClusterToEmp", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getCMclusterList", "User::getCMclusterList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getUserBranchList", "User::getUserBranchList", ['filter' => 'authFilter']);

     $routes->match(['GET', 'options'], "getUserwiseBranchClusterZoneList", "User::getUserwiseBranchClusterZoneList", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getZoneClusterBranchesTree", "User::getZoneClusterBranchesTree", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getUserZones", "User::getUserZones", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getClusterBranchList", "User::getClusterBranchList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getZoneClusterList", "User::getZoneClusterList", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addClusterToZone", "User::addClusterToZone", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addBranchToCluster", "User::addBranchToCluster", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "BM_DashboardCount", "User::BM_DashboardCount", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "CM_DashboardCount", "User::CM_DashboardCount", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "checkToken", "User::checkToken", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getUserBranchClusterZoneList", "User::getUserBranchClusterZoneList", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getDepts", "Home::getDeptWithCat", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editDept", "Home::editDept", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addDept", "Home::addDept", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteDept", "Home::deleteDept", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getArea", "Home::getArea");
     $routes->match(['POST', 'options'], "addArea", "Home::addArea", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editArea", "Home::editArea", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteArea", "Home::deleteArea", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getRoles", "Home::getRoles", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getZones", "Home::getZones", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addZone", "Home::addZone", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editZone", "Home::editZone", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteZone", "Home::deleteZone", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getClusters", "Home::getAllCluster", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "editCluster", "Home::editCluster", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteCluster", "Home::deleteCluster", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "clusterMapping", "Home::clusterMapping", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getBranchDetails", "Home::getBranchDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editBranchDetails", "Home::editBranchDetails", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addNewBranch", "Home::addNewBranch", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteBranch", "Home::deleteBranch", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getManagers", "Home::getManagers", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addNewManager", "Home::addNewManager", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editManager", "Home::editManager", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteManager", "Home::deleteManager", ['filter' => 'authFilter']);


     $routes->match(['POST', 'options'], "getCategory", "Home::getCategory", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getTechnicians", "Home::getTechnicians", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addNewTechnician", "Home::addNewTechnician", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editTechnician", "Home::editTechnician", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteTechnician", "Home::deleteTechnician", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getAssets", "Home::getAssets", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addNewAssets", "Home::addNewAssets", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editAssets", "Home::editAssets", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteAssets", "Home::deleteAssets", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getServiceManager", "Home::getServiceManager", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addServiceManager", "Home::addServiceManager", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editServiceManager", "Home::editServiceManager", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteServiceManager", "Home::deleteServiceManager", ['filter' => 'authFilter']);

     $routes->match(['POST', 'options'], "getEquipments", "Home::getEquipments", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addEquipments", "Home::addEquipments", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "editEquipments", "Home::editEquipments", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteEquipments", "Home::deleteEquipments", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "dashboardCount", "Home::DashboardCount", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "getAssetDetails", "Home::getAssetDetails", ['filter' => 'authFilter']);


     $routes->match(['POST', 'options'], "branchwiseComplaints", "Reports::branchwiseComplaints", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deptwiseComplaints", "Reports::deptwiseComplaints", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "statuswiseComplaints", "Reports::statuswiseComplaints", ['filter' => 'authFilter']);



     $routes->match(['POST', 'options'], "branchQuetions", "Reports::branchQuetions", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "branchNightQuetions", "Reports::branchNightQuetions", ['filter' => 'authFilter']);

     $routes->match(['GET', 'options'], "getpassword", "User::getpassword");
     $routes->match(['POST', 'options'], "deleteBranchOrClusterFromEmp", "User::deleteBranchOrClusterFromEmp", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "removeBranchFromCluster", "User::removeBranchFromCluster", ['filter' => 'authFilter']);

     $routes->match(['GET', 'options'], "getClusters_New", "Home::getClusters", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addNewCluster", "Home::addNewCluster", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getClusterByid/(:num)", "Home::getClusterByid/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getBranches", "Home::getBranches", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "updateCluster/(:num)", "Home::updateCluster/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deleteClusterbYiD/(:num)", "Home::deleteClusterbYiD/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "addNewCluster", "Home::addNewCluster", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "saveCluster", "Home::saveCluster", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getZonalByid/(:num)", "Home::getZonalByid/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "updateZonal/(:num)", "Home::updateZonal/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getUsers", "User::getUsers");
     $routes->match(['GET', 'options'], "getZonals", "Home::getZonals", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "assignZoneToEmployee", "Home::assignZoneToEmployee", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getUserBranchList_new", "Home::getUserBranchList_new", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getUserMap/(:num)", "Home::getUserMap/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getEmpBranches", "Home::getEmpBranches", ['filter' => 'authFilter']);


     $routes->match(['GET', 'options'], "avpdashboardCount", "Home::avpdashboardCount", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getMatchedBranches/(:num)", "Home::getMatchedBranches/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getClustersWithBranches", "Home::getClustersWithBranches", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getYesterdaysReading", "Home::getYesterdaysReading", ['filter' => 'authFilter']);








     // routes for VendorMaster  
     $routes->match(['POST', 'options'], "createVendor", "VendorMaster::createVendor", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getVendorById/(:num)", "VendorMaster::getVendorById/$1", ['filter' => 'authFilter']);
     $routes->match(['PUT', 'options'], "updateVendor/(:num)", "VendorMaster::updateVendor/$1", ['filter' => 'authFilter']);
     $routes->match(['DELETE', 'options'], "deleteVendor/(:num)", "VendorMaster::deleteVendor/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getVendorList", "VendorMaster::getVendorList", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getVendorByBranchId/(:num)", "VendorMaster::getVendorByBranchId/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getVendorForPestControlByBranchId/(:num)", "VendorMaster::getVendorForPestControlByBranchId/$1", ['filter' => 'authFilter']);

     $routes->match(['GET', 'options'], "getVendorForElevationCleaningByBranchId/(:num)", "VendorMaster::getVendorForElevationCleaningByBranchId/$1", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getVendorForWaterTankCleaningByBranchId/(:num)", "VendorMaster::getVendorForWaterTankCleaningByBranchId/$1", ['filter' => 'authFilter']);


     $routes->match(['GET', 'options'], "getBranchListMappedWithVendor/(:num)", "VendorMaster::getBranchListMappedWithVendor/$1", ['filter' => 'authFilter']);




     //routes for PestControl
     $routes->match(['POST', 'options'], "createPestControl", "PestControl::createPestControl", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getPestControlList", "PestControl::getPestControlList", ['filter' => 'authFilter']);
     $routes->match(['GET', 'options'], "getPestControlById/(:num)", "PestControl::getPestControlById/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "updatePestControl/(:num)", "PestControl::updatePestControl/$1", ['filter' => 'authFilter']);
     $routes->match(['POST', 'options'], "deletePestControl", "PestControl::deletePestControl", ['filter' => 'authFilter']);




     // routes for User Modes
     $routes->match(['GET', 'options'], "getUserModes", "User::getUserModes", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserMode", "User::createUserMode", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserModeById/(:num)", "User::getUserModeById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserModeById/(:num)", "User::updateUserModeById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserModeById/(:num)", "User::deleteUserModeById/$1", ['filter' => 'jwt']);


     // routes for User Designations
     $routes->match(['GET', 'options'], "getUserDesign", "User::getUserDesign");
     $routes->match(['POST', 'options'], "createUserDesign", "User::createUserDesign");
     $routes->match(['GET', 'options'], "getUserDesignById/(:num)", "User::getUserDesignById/$1");
     $routes->match(['POST', 'options'], "updateUserDesignById/(:num)", "User::updateUserDesignById/$1");
     $routes->match(['POST', 'options'], "deleteUserDesignModeById/(:num)", "User::deleteUserDesignModeById/$1");

     // routes for User Positions
     $routes->match(['GET', 'options'], "getUserPosit", "User::getUserPosit");
     $routes->match(['POST', 'options'], "createUserPosit", "User::createUserPosit");
     $routes->match(['GET', 'options'], "getUserPositById/(:num)", "User::getUserPositById/$1");
     $routes->match(['POST', 'options'], "updateUserPositById/(:num)", "User::updateUserPositById/$1");
     $routes->match(['POST', 'options'], "deleteUserPositModeById/(:num)", "User::deleteUserPositModeById/$1");

     // routes for User Calender
     $routes->match(['GET', 'options'], "getUserCalen", "User::getUserCalen", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserCalen", "User::createUserCalen", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserCalenById/(:num)", "User::getUserCalenById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserCalenById/(:num)", "User::updateUserCalenById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserCalenModeById/(:num)", "User::deleteUserCalenModeById/$1", ['filter' => 'jwt']);

     // routes for User Department
     $routes->match(['GET', 'options'], "getUserDept", "User::getUserDept");
     $routes->match(['POST', 'options'], "createUserDept", "User::createUserDep");
     $routes->match(['GET', 'options'], "getUserDeptById/(:num)", "User::getUserDeptById/$1");
     $routes->match(['POST', 'options'], "updateUserDeptById/(:num)", "User::updateUserDeptById/$1");
     $routes->match(['POST', 'options'], "deleteUserDeptModeById/(:num)", "User::deleteUserDeptModeById/$1");


     // routes for User Sub Department
     $routes->match(['GET', 'options'], "getUserSubDept", "User::getUserSubDept");
     $routes->match(['POST', 'options'], "createUserSubDept", "User::createUserSubDept");
     $routes->match(['GET', 'options'], "getUserSubDeptById/(:num)", "User::getUserSubDeptById/$1");
     $routes->match(['POST', 'options'], "updateUserSubDeptById/(:num)", "User::updateUserSubDeptById/$1");
     $routes->match(['POST', 'options'], "deleteUserSubDeptModeById/(:num)", "User::deleteUserSubDeptModeById/$1");


     // routes for Grades
     $routes->match(['GET', 'options'], "getGrade", "User::getGrade");
     $routes->match(['POST', 'options'], "createGrade", "User::createGrade");
     $routes->match(['GET', 'options'], "getGradeById/(:num)", "User::getGradeById/$1");
     $routes->match(['POST', 'options'], "updateGradeById/(:num)", "User::updateGradeById/$1");
     $routes->match(['POST', 'options'], "deleteGradeById/(:num)", "User::deleteGradeById/$1");



     // routes for User Pay Group
     $routes->match(['GET', 'options'], "getUserPayGroup", "User::getUserPayGroup");
     $routes->match(['POST', 'options'], "createUserPayGroup", "User::createUserPayGroup");
     $routes->match(['GET', 'options'], "getUserPayGroupById/(:num)", "User::getUserPayGroupById/$1");
     $routes->match(['POST', 'options'], "updateUserPayGroupById/(:num)", "User::updateUserPayGroupById/$1");
     $routes->match(['POST', 'options'], "deleteUserPayGroupModeById/(:num)", "User::deleteUserPayGroupModeById/$1");


     // routes for User Pay Group
     $routes->match(['GET', 'options'], "getRegion", "User::getRegion");
     $routes->match(['POST', 'options'], "createRegion", "User::createRegion");
     $routes->match(['GET', 'options'], "getRegionId/(:num)", "User::getRegionId/$1");
     $routes->match(['POST', 'options'], "updateRegionId/(:num)", "User::updateRegionId/$1");
     $routes->match(['POST', 'options'], "deleteRegionId/(:num)", "User::deleteRegionId/$1");

     // routes for User Currency
     $routes->match(['GET', 'options'], "getUserCurrency", "User::getUserCurrency", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserCurrency", "User::createUserCurrency", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserCurrencyById/(:num)", "User::getUserCurrencyById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserCurrencyById/(:num)", "User::updateUserCurrencyById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserCurrencyModeById/(:num)", "User::deleteUserCurrencyModeById/$1", ['filter' => 'jwt']);

     // routes for User Job Profile]
     $routes->match(['GET', 'options'], "getUserJobProfile", "User::getUserJobProfile", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserJobProfile", "User::createUserJobProfile", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserJobProfileById/(:num)", "User::getUserJobProfileById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserJobProfileById/(:num)", "User::updateUserJobProfileById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserJobProfileById/(:num)", "User::deleteUserJobProfileById/$1", ['filter' => 'jwt']);

     // routes for User Payment Type
     $routes->match(['GET', 'options'], "getUserPaymentType", "User::getUserPaymentType", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserPaymentType", "User::createUserPaymentType", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserPaymentTypeById/(:num)", "User::getUserPaymentTypeById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserPaymentTypeById/(:num)", "User::updateUserPaymentTypeById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserPaymentTypeById/(:num)", "User::deleteUserPaymentTypeById/$1", ['filter' => 'jwt']);

     // routes for User Banks
     $routes->match(['GET', 'options'], "getUserBank", "User::getUserBank", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserBank", "User::createUserBank", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserBankById/(:num)", "User::getUserBankById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserBankById/(:num)", "User::updateUserBankById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserBankById/(:num)", "User::deleteUserBankById/$1", ['filter' => 'jwt']);


     // routes for User Center
     $routes->match(['GET', 'options'], "getUserCenter", "User::getUserCenter", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserCenter", "User::createUserCenter", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserCenterById/(:num)", "User::getUserCenterById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserCenterById/(:num)", "User::updateUserCenterById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserCenterById/(:num)", "User::deleteUserCenterById/$1", ['filter' => 'jwt']);


     // routes for Work Type
     $routes->match(['GET', 'options'], "getUserWorkType", "User::getUserWorkType", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserWorkType", "User::createUserWorkType", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserWorkTypeById/(:num)", "User::getUserWorkTypeById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserWorkTypeById/(:num)", "User::updateUserWorkTypeById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserWorkTypeById/(:num)", "User::deleteUserWorkTypeById/$1", ['filter' => 'jwt']);


     // routes for Med Reg Council
     $routes->match(['GET', 'options'], "getUserMedRegCouncil", "User::getUserMedRegCouncil", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserMedRegCouncil", "User::createUserMedRegCouncil", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserMedRegCouncilById/(:num)", "User::getUserMedRegCouncilById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserMedRegCouncilById/(:num)", "User::updateUserMedRegCouncilById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserMedRegCouncilById/(:num)", "User::deleteUserMedRegCouncilById/$1", ['filter' => 'jwt']);


     // routes for Qualification 
     $routes->match(['GET', 'options'], "getUserQualification", "User::getUserQualification", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserQualification", "User::createUserQualification", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserQualificationById/(:num)", "User::getUserQualificationById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserQualificationById/(:num)", "User::updateUserQualificationById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserQualificationById/(:num)", "User::deleteUserQualificationById/$1", ['filter' => 'jwt']);


     // routes for Specialization 
     $routes->match(['GET', 'options'], "getUserSpecialization", "User::getUserSpecialization", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserSpecialization", "User::createUserSpecialization", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserSpecializationById/(:num)", "User::getUserSpecializationById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserSpecializationById/(:num)", "User::updateUserSpecializationById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserSpecializationById/(:num)", "User::deleteUserSpecializationById/$1", ['filter' => 'jwt']);

     // routes for Earning 
     $routes->match(['GET', 'options'], "getUserEarningName", "User::getUserEarningName", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserEarningName", "User::createUserEarningName", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserEarningNameById/(:num)", "User::updateUserEarningNameById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserEarningNameById/(:num)", "User::updateUserEarningNameById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserEarningNameById/(:num)", "User::deleteUserEarningNameById/$1", ['filter' => 'jwt']);

     // routes for Account 
     $routes->match(['GET', 'options'], "getUserAccount", "User::getUserAccount", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserAccount", "User::createUserAccount", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserAccountById/(:num)", "User::getUserAccountById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserAccountById/(:num)", "User::updateUserAccountById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserAccountById/(:num)", "User::deleteUserAccountById/$1", ['filter' => 'jwt']);

     // routes for Deduction Name 
     $routes->match(['GET', 'options'], "getUserDeductionName", "User::getUserDeductionName", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserDeductionName", "User::createUserDeductionName", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserDeductionById/(:num)", "User::getUserDeductionById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserDeductionById/(:num)", "User::updateUserDeductionById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserDeductionById/(:num)", "User::deleteUserDeductionById/$1", ['filter' => 'jwt']);

     // routes for Loan Type 
     $routes->match(['GET', 'options'], "getUserLoanType", "User::getUserLoanType", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserLoanType", "User::createUserLoanType", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserLoanTypeById/(:num)", "User::getUserLoanTypeById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserLoanTypeById/(:num)", "User::updateUserLoanTypeById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserLoanTypeById/(:num)", "User::deleteUserLoanTypeById/$1", ['filter' => 'jwt']);

     // routes for Leave Template 
     $routes->match(['GET', 'options'], "getUserLeaveTemplate", "User::getUserLeaveTemplate", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserLeaveTemplate", "User::createUserLeaveTemplate", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserLeaveTemplateById/(:num)", "User::getUserLeaveTemplateById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserLeaveTemplateById/(:num)", "User::updateUserLeaveTemplateById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserLeaveTemplateById/(:num)", "User::deleteUserLeaveTemplateById/$1", ['filter' => 'jwt']);

     // routes for Air Ticket Template 
     $routes->match(['GET', 'options'], "getUserAirTicketTemplate", "User::getUserAirTicketTemplate", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserAirTicketTemplate", "User::createUserAirTicketTemplate", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserAirTicketTemplateById/(:num)", "User::getUserAirTicketTemplateById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserAirTicketTemplateById/(:num)", "User::updateUserAirTicketTemplateById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserAirTicketTemplateById/(:num)", "User::deleteUserAirTicketTemplateById/$1", ['filter' => 'jwt']);

     // routes for reason For Leaving 
     $routes->match(['GET', 'options'], "getUserReasonForLeaving", "User::getUserReasonForLeaving", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserReasonForLeaving", "User::createUserReasonForLeaving", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserReasonForLeavingById/(:num)", "User::getUserReasonForLeavingById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserReasonForLeavingById/(:num)", "User::updateUserReasonForLeavingById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserReasonForLeavingById/(:num)", "User::deleteUserReasonForLeavingById/$1", ['filter' => 'jwt']);

     // routes for State 
     $routes->match(['GET', 'options'], "getUserState", "User::getUserState", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createUserState", "User::createUserState", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getUserStateById/(:num)", "User::getUserStateById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateUserStateById/(:num)", "User::updateUserStateById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteUserStateById/(:num)", "User::deleteUserStateById/$1", ['filter' => 'jwt']);


     // routes for SBU 
     // $routes->match(['GET', 'options'], "getSbu", "User::getSbu", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getSbu", "User::getSbu");
     $routes->match(['POST', 'options'], "createSbu", "User::createSbu");
     $routes->match(['GET', 'options'], "getSbuById/(:num)", "User::getSbuById/$1");
     $routes->match(['POST', 'options'], "updateSbuById/(:num)", "User::updateSbuById/$1");
     $routes->match(['POST', 'options'], "deleteSbuById/(:num)", "User::deleteSbuById/$1");


     // routes for City 
     $routes->match(['GET', 'options'], "getCity", "User::getCity");
     $routes->match(['POST', 'options'], "createCity", "User::createCity");
     $routes->match(['GET', 'options'], "getCityById/(:num)", "User::getCityById/$1");
     $routes->match(['POST', 'options'], "updateCityById/(:num)", "User::updateCityById/$1");
     $routes->match(['POST', 'options'], "deleteCityById/(:num)", "User::deleteCityById/$1");


     // routes for Location 
     $routes->match(['GET', 'options'], "getLocation", "User::getLocation");
     $routes->match(['POST', 'options'], "createLocation", "User::createLocation");
     $routes->match(['GET', 'options'], "getLocationById/(:num)", "User::getLocationById/$1");
     $routes->match(['POST', 'options'], "updateLocationById/(:num)", "User::updateLocationById/$1");
     $routes->match(['POST', 'options'], "deleteLocationById/(:num)", "User::deleteLocationById/$1");


     // routes for Cluster 
     $routes->match(['GET', 'options'], "getCluster", "User::getCluster");
     $routes->match(['POST', 'options'], "createCluster", "User::createCluster");
     $routes->match(['GET', 'options'], "getClusterById/(:num)", "User::getClusterById/$1");
     $routes->match(['POST', 'options'], "updateClusterById/(:num)", "User::updateClusterById/$1");
     $routes->match(['POST', 'options'], "deleteClusterById/(:num)", "User::deleteClusterById/$1");


     // routes for Dept Category 
     $routes->match(['GET', 'options'], "getDeptCategory", "User::getDeptCategory");
     $routes->match(['POST', 'options'], "createDeptCategory", "User::createDeptCategory");
     $routes->match(['GET', 'options'], "getDeptCategoryById/(:num)", "User::getDeptCategoryById/$1");
     $routes->match(['POST', 'options'], "updateDeptCategoryById/(:num)", "User::updateDeptCategoryById/$1");
     $routes->match(['POST', 'options'], "deleteDeptCategoryById/(:num)", "User::deleteDeptCategoryById/$1");


     // routes for Main Dept 
     $routes->match(['GET', 'options'], "getMainDept", "User::getMainDept");
     $routes->match(['POST', 'options'], "createMainDept", "User::createMainDept");
     $routes->match(['GET', 'options'], "getMainDeptById/(:num)", "User::getMainDeptById/$1");
     $routes->match(['POST', 'options'], "updateMainDeptById/(:num)", "User::updateMainDeptById/$1");
     $routes->match(['POST', 'options'], "deleteMainDeptById/(:num)", "User::deleteMainDeptById/$1");


     // routes for Sub Dept 
     $routes->match(['GET', 'options'], "getSubDept", "User::getSubDept");
     $routes->match(['POST', 'options'], "createSubDept", "User::createSubDept");
     $routes->match(['GET', 'options'], "getSubDeptById/(:num)", "User::getSubDeptById/$1");
     $routes->match(['POST', 'options'], "updateSubDeptById/(:num)", "User::updateSubDeptById/$1");
     $routes->match(['POST', 'options'], "deleteSubDeptById/(:num)", "User::deleteSubDeptById/$1");


     // routes for Branches 
     $routes->match(['GET', 'options'], "getBranch", "User::getBranch", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createBranch", "User::createBranch", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getBranchById/(:num)", "User::getBranchById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateBranchById/(:num)", "User::updateBranchById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteBranchById/(:num)", "User::deleteBranchById/$1", ['filter' => 'jwt']);

     // routes for ShiftRoasters 
     $routes->match(['GET', 'options'], "getShiftRoster", "User::getShiftRoster");
     $routes->match(['POST', 'options'], "createShiftRoster", "User::createShiftRoster");
     $routes->match(['GET', 'options'], "getShiftRosterById/(:num)", "User::getShiftRosterById/$1");
     $routes->match(['POST', 'options'], "updateShiftRosterById/(:num)", "User::updateShiftRosterById/$1");
     $routes->match(['POST', 'options'], "deleteShiftRosterById/(:num)", "User::deleteShiftRosterById/$1");


     // routes for Religion
     $routes->match(['GET', 'options'], "getReligion", "User::getReligion");
     $routes->match(['POST', 'options'], "createReligion", "User::createReligion");
     $routes->match(['GET', 'options'], "getReligionById/(:num)", "User::getReligionById/$1");
     $routes->match(['POST', 'options'], "updateReligionById/(:num)", "User::updateReligionById/$1");
     $routes->match(['POST', 'options'], "deleteReligionById/(:num)", "User::deleteReligionById/$1");


     // routes for Caste
     $routes->match(['GET', 'options'], "getCaste", "User::getCaste");
     $routes->match(['POST', 'options'], "createCaste", "User::createCaste");
     $routes->match(['GET', 'options'], "getCasteById/(:num)", "User::getCasteById/$1");
     $routes->match(['POST', 'options'], "updateCasteById/(:num)", "User::updateCasteById/$1");
     $routes->match(['POST', 'options'], "deleteCasteById/(:num)", "User::deleteCasteById/$1");


     // routes for Degree
     $routes->match(['GET', 'options'], "getDegree", "User::getDegree");
     $routes->match(['POST', 'options'], "createDegree", "User::createDegree");
     $routes->match(['GET', 'options'], "getDegreeById/(:num)", "User::getDegreeById/$1");
     $routes->match(['POST', 'options'], "updateDegreeById/(:num)", "User::updateDegreeById/$1");
     $routes->match(['POST', 'options'], "deleteDegreeById/(:num)", "User::deleteDegreeById/$1");


     // routes for Blood Group
     $routes->match(['GET', 'options'], "getBloodGroup", "User::getBloodGroup");
     $routes->match(['POST', 'options'], "createBloodGroup", "User::createBloodGroup");
     $routes->match(['GET', 'options'], "getBloodGroupById/(:num)", "User::getBloodGroupById/$1");
     $routes->match(['POST', 'options'], "updateBloodGroupById/(:num)", "User::updateBloodGroupById/$1");
     $routes->match(['POST', 'options'], "deleteBloodGroupById/(:num)", "User::deleteBloodGroupById/$1");



     // routes for Holidays
     $routes->match(['GET', 'options'], "getHolidays", "User::getHolidays");
     $routes->match(['POST', 'options'], "createHoliday", "User::createHoliday");
     $routes->match(['GET', 'options'], "getHolidayById/(:num)", "User::getHolidayById/$1");
     $routes->match(['POST', 'options'], "updateHolidayById/(:num)", "User::updateHolidayById/$1");
     $routes->match(['POST', 'options'], "deleteHolidayById/(:num)", "User::deleteHolidayById/$1");



     // routes for Organization
     $routes->match(['GET', 'options'], "getOrgan", "User::getOrgan", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createOrgan", "User::createOrgan", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getOrganById/(:num)", "User::getOrganById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateOrganById/(:num)", "User::updateOrganById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteOrganById/(:num)", "User::deleteOrganById/$1", ['filter' => 'jwt']);


     // routes for Modalities
     $routes->match(['GET', 'options'], "getModalities", "User::getModalities");
     $routes->match(['POST', 'options'], "createModality", "User::createModality");
     $routes->match(['GET', 'options'], "getModalityById/(:num)", "User::getModalityById/$1");
     $routes->match(['POST', 'options'], "updateModalityById/(:num)", "User::updateModalityById/$1");
     $routes->match(['POST', 'options'], "deleteModalityById/(:num)", "User::deleteModalityById/$1");


     // routes for Modalities
     $routes->match(['GET', 'options'], "getSubModalities", "User::getSubModalities");
     $routes->match(['POST', 'options'], "createSubModality", "User::createSubModality");
     $routes->match(['GET', 'options'], "getSubModalityById/(:num)", "User::getSubModalityById/$1");
     $routes->match(['POST', 'options'], "updateSubModalityById/(:num)", "User::updateSubModalityById/$1");
     $routes->match(['POST', 'options'], "deleteSubModalityById/(:num)", "User::deleteSubModalityById/$1");



     // routes for Employee
     $routes->match(['GET', 'options'], "getEmp", "User::getEmp", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "createEmp", "User::createEmp", ['filter' => 'jwt']);
     $routes->match(['GET', 'options'], "getEmpById/(:num)", "User::getEmpById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "updateEmpById/(:num)", "User::updateEmpById/$1", ['filter' => 'jwt']);
     $routes->match(['POST', 'options'], "deleteEmpById/(:num)", "User::deleteEmpById/$1", ['filter' => 'jwt']);


     $routes->match(['POST', 'options'], 'save-user', 'User::saveUser');
     $routes->match(['GET', 'OPTIONS'], 'getAllUsers', 'User::getAllUsers');
     $routes->match(['GET', 'OPTIONS'], 'getUserById/(:num)', 'User::getUserById/$1');


     $routes->match(['POST', 'OPTIONS'], 'update-user/(:num)', 'User::updateUser/$1');
     $routes->match(['DELETE', 'OPTIONS'], 'delete-user/(:num)', 'User::deleteUser/$1');
     $routes->match(['DELETE', 'OPTIONS'], 'delete-doc/(:num)', 'User::deleteDoc/$1');
     $routes->match(['POST', 'OPTIONS'], 'update-doc/(:num)', 'User::updateDoc/$1');



     // routes for attendande

     $routes->match(['GET', 'OPTIONS'], 'getUserAttendance', 'User::getUserAttendance', ['filter' => 'jwt']);

     $routes->match(['GET', 'OPTIONS'], 'getMonthlyAttendance', 'User::getMonthlyAttendance', ['filter' => 'jwt']);


     $routes->match(['POST', 'options'], "changepassword", "User::changepassword", ['filter' => 'jwt']);


     $routes->match(['POST', 'options'], "resetUserById/(:segment)", "User::resetUserById/$1", ['filter' => 'jwt']);

     // routes for NewUser.php     
     $routes->match(['POST', 'OPTIONS'], 'createNewEmployee', 'NewUser::createNewEmployee', ['filter' => 'jwt']);
     $routes->match(['GET', 'OPTIONS'], 'getEmployees', 'NewUser::getEmployees', ['filter' => 'jwt']);
     $routes->match(['GET', 'OPTIONS'], 'getEmployeeById/(:num)', 'NewUser::getEmployeeById/$1', ['filter' => 'jwt']);
     $routes->match(['GET', 'OPTIONS'], 'getEmployeeByEmpCode/(:any)', 'NewUser::getEmployeeByEmpCode/$1', ['filter' => 'jwt']); // ✅ FIXED
     $routes->match(['POST', 'OPTIONS'], 'updateEmployee/(:num)', 'NewUser::updateEmployee/$1', ['filter' => 'jwt']);
     $routes->match(['DELETE', 'OPTIONS'], 'deleteEmployee/(:any)', 'NewUser::deleteEmployee/$1', ['filter' => 'jwt']);
     $routes->match(['DELETE', 'OPTIONS'], 'deleteEmployeeDocument/(:any)', 'NewUser::deleteEmployeeDocument/$1', ['filter' => 'jwt']);
     $routes->match(['GET', 'OPTIONS'], 'employee-document/(:any)', 'NewUser::viewEmployeeDocument/$1');

     // DutyRoster routes
     //createDutyRoster
     $routes->match(['POST', 'OPTIONS'], 'createDutyRoster', 'DutyRoster::createDutyRoster', ['filter' => 'jwt']);
     $routes->match(['GET', 'OPTIONS'], 'getDutyRosters', 'DutyRoster::getDutyRosters', ['filter' => 'jwt']);
     $routes->match(['GET', 'OPTIONS'], 'getDutyRosterById/(:num)', 'DutyRoster::getDutyRosterById/$1', ['filter' => 'jwt']);
     $routes->match(['POST', 'OPTIONS'], 'updateDutyRoster/(:num)', 'DutyRoster::updateDutyRoster/$1', ['filter' => 'jwt']);

     $routes->match(['GET', 'OPTIONS'], 'getDutyRosterByEmpIdAndSelectedMonth/(:num)/(:any)', 'DutyRoster::getDutyRosterByEmpIdAndSelectedMonth/$1/$2', ['filter' => 'jwt']);
     $routes->match(['POST', 'OPTIONS'], 'updateDutyRosterBulk', 'DutyRoster::updateDutyRosterBulk', ['filter' => 'jwt']);
     $routes->match(['GET', 'OPTIONS'], 'getShifts', 'DutyRoster::getShifts', ['filter' => 'jwt']);
     $routes->match(['GET', 'OPTIONS'], 'getEmpByIdNew/(:num)', 'DutyRoster::getEmpByIdNew/$1');

     $routes->match(['GET', 'OPTIONS'], 'getEmployeeAttendance/(:num)/(:any)', 'DutyRoster::getEmployeeAttendance/$1/$2', ['filter' => 'jwt']);
     //   $routes->match(['GET', 'OPTIONS'], 'users', 'DoctUsers::index');
     //   $routes->match(['GET', 'OPTIONS'], 'users/(:num)', 'DoctUsers::show/$1');
     //   $routes->match(['POST', 'OPTIONS'], 'users', 'DoctUsers::create');
     //   $routes->match(['POST', 'OPTIONS'], 'users/(:num)', 'DoctUsers::update/$1');
     //   $routes->match(['DELETE', 'OPTIONS'], 'users/(:num)', 'DoctUsers::delete/$1');
     //centralLogin
     $routes->match(['POST', 'OPTIONS'], 'centralLogin', 'CentralLoginController::centralLogin');
});
