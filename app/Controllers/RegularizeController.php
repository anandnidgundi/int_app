<?php

namespace App\Controllers;

use App\Models\RegularizeModel;
use CodeIgniter\API\ResponseTrait;

class RegularizeController extends BaseController
{
     use ResponseTrait;

     protected $model;

     public function __construct()
     {
          $this->model = new RegularizeModel();
     }

     public function downloadAttachment($filename = null)
     {
          // Ensure CORS headers are always present on this endpoint (helps when preflight or filters don't add them)
          $origin = $this->request->getHeaderLine('Origin') ?: null;
          $allowOrigin = $origin ?: '*';
          // Make sure we don't duplicate the Access-Control-Allow-Origin header if a global filter or server already set it
          if (method_exists($this->response, 'removeHeader')) {
               $this->response->removeHeader('Access-Control-Allow-Origin');
          }
          $this->response->setHeader('Access-Control-Allow-Origin', $allowOrigin)
               ->setHeader('Access-Control-Allow-Credentials', 'true')
               ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
               ->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With')
               ->setHeader('Access-Control-Expose-Headers', 'Content-Disposition')
               ->setHeader('Vary', 'Origin');

          // Allow preflight OPTIONS without requiring Authorization so browsers can obtain CORS headers
          if ($this->request->getMethod() === 'options') {
               // send minimal allowed headers and return
               log_message('error', 'Regularize::downloadAttachment OPTIONS preflight', ['origin' => $origin]);
               // ensure we don't accidentally append an extra Access-Control-Allow-Origin header
               if (method_exists($this->response, 'removeHeader')) {
                    $this->response->removeHeader('Access-Control-Allow-Origin');
               }
               $this->response->setHeader('Access-Control-Allow-Origin', $origin ?: '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With')
                    ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                    ->setHeader('Access-Control-Allow-Credentials', 'true');

               // return a minimal response using the controller response object to avoid header duplication
               return $this->response->setStatusCode(200)->setBody('');
          }

          // Log request headers for debugging Authorization/CORS issues
          $hasAuthHeader = (bool) $this->request->header('Authorization')?->getValue();
          log_message('error', 'Regularize::downloadAttachment request', ['method' => $this->request->getMethod(), 'origin' => $origin, 'hasAuthorization' => $hasAuthHeader]);

          $auth = $this->validateAuthorization();
          if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) {
               // Ensure error responses also include CORS headers so browsers can see them
               if (method_exists($auth, 'removeHeader')) {
                    $auth->removeHeader('Access-Control-Allow-Origin');
               }
               $auth->setHeader('Access-Control-Allow-Origin', $origin ?: '*');
               $auth->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With');
               $auth->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
               $auth->setHeader('Access-Control-Allow-Credentials', 'true');
               return $auth;
          }
          if (empty($filename)) return $this->failValidationErrors('filename required');

          // sanitize filename
          $safe = basename($filename);
          $filePath = WRITEPATH . 'uploads/regularize/' . $safe;
          if (!file_exists($filePath)) return $this->failNotFound('File not found');

          // verify file recorded in DB (optional but recommended)
          $db = \Config\Database::connect();
          $row = $db->table('regularize_attachments')->where('file_path', 'uploads/regularize/' . $safe)->get()->getRowArray();
          if (!$row) return $this->failNotFound('Attachment record not found');

          // PUBLIC ACCESS: allow everyone to download attachments (no authorization check)
          // Keep a light provenance check/logging: ensure parent request exists and log access.
          $req = $this->model->find($row['request_id']);
          if (!$req) {
               log_message('warning', 'Regularize::downloadAttachment request record missing', ['request_id' => $row['request_id']]);
          } else {
               log_message('info', 'Regularize::downloadAttachment public access', ['request_id' => $row['request_id'], 'emp_code' => $req['emp_code'] ?? null]);
          }

          // Determine MIME type robustly (some PHP builds may not have mime_content_type)
          $mime = 'application/octet-stream';
          if (function_exists('mime_content_type')) {
               $m = @mime_content_type($filePath);
               if (!empty($m)) $mime = $m;
          } elseif (function_exists('finfo_open')) {
               $finfo = finfo_open(FILEINFO_MIME_TYPE);
               if ($finfo) {
                    $m = finfo_file($finfo, $filePath);
                    if (!empty($m)) $mime = $m;
                    finfo_close($finfo);
               }
          } elseif (!empty($row['mime'])) {
               // fall back to stored mime in DB
               $mime = $row['mime'];
          } else {
               // as a last resort, infer from extension for common types
               $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
               $map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
               if (isset($map[$ext])) $mime = $map[$ext];
          }
          // Stream the file rather than loading into memory to avoid OOM on large files
          $size = @filesize($filePath);

          // Use original name from DB for Content-Disposition so the filename shown to users is correct
          $origName = $row['original_name'] ?? $safe;
          // sanitize quoted filename (remove problematic characters)
          $quotedName = str_replace(["\"", "\\"], ['', ''], $origName);
          $encodedName = rawurlencode($origName);

          try {
               // Set all headers including CORS before sending file
               $origin = $this->request->getHeaderLine('Origin') ?: '*';

               log_message('error', 'Regularize::downloadAttachment serving file', ['file' => $filePath, 'displayName' => $origName, 'size' => $size, 'mime' => $mime]);

               // Clear any output buffers to ensure a clean stream
               while (ob_get_level()) {
                    @ob_end_clean();
               }

               // Send headers using raw PHP header() function since we're using exit
               // This ensures headers are actually sent before the file content
               header('Content-Type: ' . $mime);
               header('Content-Disposition: inline; filename="' . $quotedName . '"; filename*=UTF-8\'\'' . $encodedName);
               header('Content-Length: ' . ($size ?: 0));
               header('Content-Transfer-Encoding: binary');
               header('Accept-Ranges: bytes');
               header('Access-Control-Allow-Origin: ' . $origin);
               header('Access-Control-Allow-Credentials: true');
               header('Access-Control-Expose-Headers: Content-Disposition, Content-Type, Content-Length');
               header('Cache-Control: public, max-age=3600');

               // Stream the file directly to output and terminate to prevent additional output
               readfile($filePath);
               exit;
          } catch (\Throwable $e) {
               log_message('error', 'Regularize::downloadAttachment failed to stream file: ' . $e->getMessage());
               return $this->fail('Failed to serve file');
          }
     }

     public function apply()
     {
          $auth = $this->validateAuthorization();
          if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) return $auth;
          $appliedBy = $auth['user_code'] ?? 'System';

          // Quick handling for OPTIONS preflight: return 200 early so CORS headers can be applied by filter
          if ($this->request->getMethod() === 'options') {
               return $this->respond([], 200);
          }

          // Debug: quick echo flow when ?debug=1 is present (do not enable in production permanently)
          if ($this->request->getGet('debug') === '1') {
               $post = $this->request->getPost();
               $files = $_FILES;
               $headers = function_exists('getallheaders') ? getallheaders() : [];
               return $this->respond(['method' => $this->request->getMethod(), 'post' => $post, 'files' => $files, 'headers' => $headers]);
          }

          // Log incoming request for debugging CORS / missing payload issues
          log_message('debug', 'Regularize::apply called, method=' . $this->request->getMethod() . ', Content-Type=' . ($this->request->getServer('CONTENT_TYPE') ?? ''));
          log_message('debug', 'Regularize::apply _POST count=' . count($this->request->getPost()) . ' _FILES count=' . count($_FILES));

          // Support new payload structure: data (JSON string) + files[]
          $dataField = $this->request->getPost('data');
          $payload = [];
          if ($dataField) {
               $decoded = json_decode($dataField, true);
               if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $payload = $decoded;
               } else {
                    // if invalid JSON, fall back to POST keys
                    $payload = $this->request->getPost();
               }
          } else {
               // old clients: parse top-level form fields
               $payload = $this->request->getPost();
          }

          // Attach uploaded files array handling: prefer $_FILES['files'] if present, else fallback to single 'attachment'
          $uploadedFiles = [];
          if (!empty($_FILES) && isset($_FILES['files'])) {
               // PHP builds $_FILES['files'] as array of subarrays (name, tmp_name, etc.)
               $filesArr = $_FILES['files'];
               if (is_array($filesArr['name'])) {
                    foreach ($filesArr['name'] as $i => $name) {
                         if ($filesArr['error'][$i] === UPLOAD_ERR_OK) {
                              $uploadedFiles[] = [
                                   'name' => $filesArr['name'][$i],
                                   'tmp_name' => $filesArr['tmp_name'][$i],
                                   'type' => $filesArr['type'][$i],
                                   'size' => $filesArr['size'][$i]
                              ];
                         }
                    }
               } else {
                    // single file (not an array)
                    if ($filesArr['error'] === UPLOAD_ERR_OK) {
                         $uploadedFiles[] = [
                              'name' => $filesArr['name'],
                              'tmp_name' => $filesArr['tmp_name'],
                              'type' => $filesArr['type'],
                              'size' => $filesArr['size']
                         ];
                    }
               }
          } elseif (!empty($_FILES) && isset($_FILES['attachment'])) {
               // backward compatibility: single attachment field
               $f = $_FILES['attachment'];
               if ($f['error'] === UPLOAD_ERR_OK) {
                    $uploadedFiles[] = ['name' => $f['name'], 'tmp_name' => $f['tmp_name'], 'type' => $f['type'], 'size' => $f['size']];
               }
          }

          // Put the first file (if any) into $fileUpload via CodeIgniter's UploadedFile handling when needed later
          // (we'll keep using $this->request->getFile('attachment') for backward compat but also allow files[])


          if (empty($payload['emp_code']) || empty($payload['for_date'])) {
               return $this->failValidationErrors('emp_code and for_date are required');
          }

          $forDate = date('Y-m-d', strtotime($payload['for_date']));
          if ($forDate === false) return $this->failValidationErrors('Invalid for_date');

          // Attachment required (file) - support new files[] or old 'attachment'
          $fileUpload = null;
          if (!empty($uploadedFiles)) {
               // We'll use the first uploaded file for existing logic
               $first = $uploadedFiles[0];
               // create a simple file-like array for checks
               $tmp = $first['tmp_name'];
               $fileSize = $first['size'];
               $fileMime = $first['type'] ?? mime_content_type($tmp);
               // emulate UploadedFile behavior by using tmp path and real name later when moving
          } else {
               // fallback to old request->getFile('attachment')
               $fileUpload = $this->request->getFile('attachment');
               if (!$fileUpload || !$fileUpload->isValid()) {
                    return $this->failValidationErrors('attachment is required');
               }
               $fileSize = $fileUpload->getSize();
               $fileMime = $fileUpload->getClientMimeType() ?: $fileUpload->getMimeType();
               $tmp = $fileUpload->getTempName();
          }

          // Server-side basic validation: allowed types and max size 2MB
          $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
          $maxBytes = 2 * 1024 * 1024; // 2MB

          if ($fileSize > $maxBytes) {
               return $this->failValidationErrors('Attachment exceeds 2MB size limit');
          }

          if (!in_array($fileMime, $allowed)) {
               return $this->failValidationErrors('Only JPG, PNG or PDF allowed');
          }

          // For PDF files, do a minimal header check and detect embedded scripts
          if ($fileMime === 'application/pdf') {
               if ($tmp && file_exists($tmp)) {
                    $size = filesize($tmp);

                    $h = file_get_contents($tmp, false, null, 0, 4);
                    if ($h !== '%PDF') {
                         return $this->failValidationErrors('Invalid or corrupted PDF');
                    }

                    // Read the entire file for deeper inspection (file size limited by earlier max size check)
                    $contents = file_get_contents($tmp);

                    // Quick scan across the whole PDF for common script/action tokens
                    if (preg_match('/\/(JavaScript|JS|OpenAction|AA|Launch|RichMedia|EmbeddedFile|AcroForm)/i', $contents)) {
                         return $this->failValidationErrors('PDF contains embedded scripts and is not allowed');
                    }

                    // Inspect object streams (stream ... endstream)
                    if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                         foreach ($matches as $m) {
                              $streamRaw = $m[1][0];
                              $streamBlockOffset = $m[0][1];

                              // look at the small dictionary before the 'stream' keyword for hints of filters
                              $beforeStart = max(0, $streamBlockOffset - 300);
                              $before = substr($contents, $beforeStart, 300);

                              // If the dictionary indicates ASCIIHexDecode, try to hex-decode and inspect
                              if (preg_match('/ASCIIHexDecode/i', $before)) {
                                   $hex = preg_replace('/[^0-9A-Fa-f]/', '', $streamRaw);
                                   $decoded = @hex2bin($hex);
                                   if ($decoded !== false && preg_match('/(javascript|\/javascript|openaction|launch|acroform|embeddedfile|richmedia)/i', $decoded)) {
                                        return $this->failValidationErrors('PDF contains embedded scripts and is not allowed');
                                   }
                                   // try to inflate after decoding in case it's flate inside
                                   $infl = @gzinflate($decoded);
                                   if ($infl !== false && preg_match('/(javascript|\/javascript|openaction|launch|acroform|embeddedfile|richmedia)/i', $infl)) {
                                        return $this->failValidationErrors('PDF contains embedded scripts and is not allowed');
                                   }
                                   continue;
                              }

                              // If FlateDecode is present in dictionary, attempt to decompress and inspect
                              if (preg_match('/FlateDecode/i', $before)) {
                                   $data = $streamRaw;
                                   // trim potential leading CR/LF
                                   if (substr($data, 0, 2) === "\r\n") $data = substr($data, 2);
                                   elseif (substr($data, 0, 1) === "\n") $data = substr($data, 1);

                                   $inflated = @gzinflate($data);
                                   if ($inflated === false) $inflated = @gzuncompress($data);
                                   if ($inflated === false) $inflated = @gzdecode($data);

                                   if ($inflated !== false) {
                                        if (preg_match('/(javascript|\/javascript|openaction|launch|acroform|embeddedfile|richmedia)/i', $inflated)) {
                                             return $this->failValidationErrors('PDF contains embedded scripts and is not allowed');
                                        }
                                   }
                                   continue;
                              }

                              // As a fallback, scan the raw stream block for script tokens (handles uncompressed script objects)
                              if (preg_match('/(javascript|\/javascript|openaction|launch|acroform|embeddedfile|richmedia)/i', $streamRaw)) {
                                   return $this->failValidationErrors('PDF contains embedded scripts and is not allowed');
                              }
                         }
                    }
               }
          }

          // duplicate pending for same emp/date
          $existing = $this->model
               ->where('emp_code', $payload['emp_code'])
               ->where('for_date', $forDate)
               ->where('status', 'Pending')
               ->first();

          if ($existing) {
               return $this->failValidationErrors('A pending regularize request already exists for this date', 409);
          }

          $data = [
               'emp_code'   => $payload['emp_code'],
               'for_date'   => $forDate,
               'punch_in'   => $payload['punch_in'] ?? null,
               'punch_out'  => $payload['punch_out'] ?? null,
               'reason'     => $payload['reason'] ?? null,
               'status'     => 'Pending',
               'applied_by' => $appliedBy,
               'applied_on' => date('Y-m-d H:i:s')
          ];

          $file = $this->request->getFile('attachment');
          if ($file && $file->isValid() && !$file->hasMoved()) {
               $uploadDir = WRITEPATH . 'uploads/regularize/';
               if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
               $newName = $file->getRandomName();
               if ($file->move($uploadDir, $newName)) {
                    $data['attachment'] = 'uploads/regularize/' . $newName;
               }
          }
          if ($this->model->insert($data)) {
               $requestId = $this->model->getInsertID();

               // Prepare upload directory
               $uploadDir = WRITEPATH . 'uploads/regularize/';
               if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

               $db = \Config\Database::connect();

               // 1) Handle uploadedFiles[] parsed from $_FILES (new payload format)
               if (!empty($uploadedFiles)) {
                    $updatedMain = false;
                    foreach ($uploadedFiles as $f) {
                         $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $f['name']);
                         $dest = $uploadDir . $safeName;
                         if (@move_uploaded_file($f['tmp_name'], $dest)) {
                              $db->table('regularize_attachments')->insert([
                                   'request_id'   => $requestId,
                                   'file_path'    => 'uploads/regularize/' . $safeName,
                                   'original_name' => $f['name'],
                                   'mime'         => $f['type'] ?? null,
                                   'size'         => $f['size'] ?? null,
                                   'created_at'   => date('Y-m-d H:i:s')
                              ]);

                              // update main request row with first file for backward compatibility
                              if (!$updatedMain) {
                                   try {
                                        $this->model->update($requestId, [
                                             'attachment_path' => 'uploads/regularize/' . $safeName,
                                             'attachment_name' => $f['name'],
                                             'attachment_mime' => $f['type'] ?? null,
                                             'attachment_size' => $f['size'] ?? null
                                        ]);
                                   } catch (\Exception $e) {
                                        log_message('error', 'Regularize::apply failed to update main request attachment columns: ' . $e->getMessage());
                                   }
                                   $updatedMain = true;
                              }
                         } else {
                              log_message('error', 'Regularize::apply failed to move uploaded file: ' . ($f['name'] ?? '(unknown)'));
                         }
                    }
               }

               // 2) Handle legacy single-file via CI UploadedFile (attachment)
               $file = $this->request->getFile('attachment');
               if ($file && $file->isValid() && !$file->hasMoved()) {
                    $newName = $file->getRandomName();
                    if ($file->move($uploadDir, $newName)) {
                         $db->table('regularize_attachments')->insert([
                              'request_id'   => $requestId,
                              'file_path'    => 'uploads/regularize/' . $newName,
                              'original_name' => $file->getClientName(),
                              'mime'         => $file->getClientMimeType(),
                              'size'         => $file->getSize(),
                              'created_at'   => date('Y-m-d H:i:s')
                         ]);

                         // Also update main table attachment columns if they exist (back-compat)
                         try {
                              $this->model->update($requestId, [
                                   'attachment_path' => 'uploads/regularize/' . $newName,
                                   'attachment_name' => $file->getClientName(),
                                   'attachment_mime' => $file->getClientMimeType(),
                                   'attachment_size' => $file->getSize()
                              ]);
                         } catch (\Exception $e) {
                              log_message('error', 'Failed to update regularize_requests with attachment columns: ' . $e->getMessage());
                         }
                    }
               }

               return $this->respondCreated(['message' => 'Regularize request submitted', 'id' => $requestId]);
          }

          return $this->fail($this->model->errors() ?: 'Failed to submit request');
     }

     public function approve($id = null)
     {
          $auth = $this->validateAuthorization();
          if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) return $auth;
          $approver = $auth['user_code'] ?? null;
          if (!$approver) return $this->failValidationErrors('approved_by is required');

          if (empty($id)) {
               $id = $this->request->getVar('id') ?? $this->request->getUri()->getSegment(3);
               if (empty($id)) return $this->failValidationErrors('id is required');
          }

          $req = $this->model->find($id);
          if (!$req) return $this->failNotFound('Request not found');
          if (!empty($req['status']) && strtoupper($req['status']) !== 'PENDING') {
               return $this->fail('Only pending requests can be approved');
          }

          $update = [
               'status' => 'Approved',
               'approved_by' => $approver,
               'approved_on' => date('Y-m-d H:i:s'),
          ];

          // attempt attendance upsert; non-blocking on failure (still mark approved)
          $upsertResult = $this->model->upsertAttendanceFromRequest($req);

          if ($this->model->update($id, $update)) {
               return $this->respond(['message' => 'Regularize approved', 'attendance_upsert' => $upsertResult]);
          }

          return $this->fail('Failed to approve request');
     }

     public function reject($id = null)
     {
          if (empty($id)) {
               $id = $this->request->getVar('id') ?? $this->request->getUri()->getSegment(3);
               if (empty($id)) return $this->failValidationErrors('id is required');
          }

          $payload = $this->request->getJSON(true) ?? $this->request->getPost();
          $rejectedBy = $payload['rejected_by'] ?? ($this->request->getVar('rejected_by') ?? null);
          $remarks = $payload['remarks'] ?? null;

          if (!$rejectedBy) return $this->failValidationErrors('rejected_by is required');

          $data = [
               'status' => 'Rejected',
               'rejected_by' => $rejectedBy,
               'rejected_on' => date('Y-m-d H:i:s'),
               'remarks' => $remarks
          ];

          if ($this->model->update($id, $data)) {
               return $this->respond(['message' => 'Regularize request rejected']);
          }

          return $this->fail('Failed to reject request');
     }

     public function myRequests($empCode = null)
     {
          if (empty($empCode)) return $this->failValidationErrors('emp_code is required');
          return $this->respond($this->model->getByEmployee($empCode));
     }

     public function list()
     {
          $auth = $this->validateAuthorization();
          if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) return $auth;

          $userCode = $auth['user_code'] ?? null;
          $role = strtolower($auth['role'] ?? $auth['roles'] ?? '');
          $status = strtolower($this->request->getGet('status') ?? 'all');

          // pagination + search params
          $page = max(1, (int)($this->request->getGet('page') ?? 1));
          $perPage = (int)($this->request->getGet('per_page') ?? 10);
          $perPage = $perPage < 1 ? 10 : ($perPage > 100 ? 100 : $perPage);
          $search = trim($this->request->getGet('search') ?? '');

          $db = \Config\Database::connect();

          // Use query builder for efficient count + paging
          $builder = $db->table('regularize_requests r');

          // Permissions & status filter
          if ($status === 'my') {
               $builder->where('r.emp_code', $userCode);
          } else {
               // Normalize upper-case role for explicit checks (supports tokens with varying case)
               $roleUp = strtoupper($auth['role'] ?? $auth['roles'] ?? '');

               // Super-admins and HR see all employees
               if (in_array($roleUp, ['SUPER_ADMIN', 'HR'])) {
                    // no additional emp_code filter (show all)
               }
               // HOD_DOCTORS and REPORTING_MANAGER see their reporting employees
               elseif (in_array($roleUp, ['HOD_DOCTORS', 'REPORTING_MANAGER'])) {
                    try {
                         // Try travelapp DB first (some environments keep employee master there)
                         $empCodes = [];

                         try {
                              $ta = \Config\Database::connect('travelapp');

                              // discover columns and pick a reporting-manager column if present
                              $cols = [];
                              try {
                                   $colsRes = $ta->query("SHOW COLUMNS FROM `new_emp_master`")->getResultArray();
                                   $cols = array_column($colsRes, 'Field');
                              } catch (\Throwable $colErr) {
                                   log_message('warning', 'Regularize::list cannot inspect travelapp.new_emp_master columns: ' . $colErr->getMessage());
                              }

                              $candidates = [
                                   'reporting_manager_empcode',
                                   'reporting_manager_emp_code',
                                   'reporting_manager',
                                   'reporting_manager_emp_id',
                                   'reporting_manager_id',
                                   'reporting_manager_code'
                              ];

                              $reportCol = null;
                              foreach ($candidates as $c) {
                                   if (!empty($cols) && in_array($c, $cols)) {
                                        $reportCol = $c;
                                        break;
                                   }
                              }

                              if ($reportCol) {
                                   $res = $ta->table('new_emp_master')
                                        ->select('emp_code')
                                        ->where($reportCol, $userCode)
                                        ->get()
                                        ->getResultArray();
                                   $empCodes = array_column($res, 'emp_code');
                              } else {
                                   // try common name even if SHOW COLUMNS failed
                                   try {
                                        $res = $ta->table('new_emp_master')
                                             ->select('emp_code')
                                             ->where('reporting_manager_empcode', $userCode)
                                             ->get()
                                             ->getResultArray();
                                        $empCodes = array_column($res, 'emp_code');
                                   } catch (\Throwable $t) {
                                        log_message('warning', 'Regularize::list travelapp lookup failed (no suitable report-col): ' . $t->getMessage());
                                   }
                              }
                         } catch (\Throwable $taErr) {
                              log_message('warning', 'Regularize::list travelapp connection failed: ' . $taErr->getMessage());
                              $empCodes = [];
                         }

                         // If travelapp returned none, try local employees table (same server DB)
                         if (empty($empCodes)) {
                              try {
                                   $colsLocal = [];
                                   try {
                                        $colsResLocal = $db->query("SHOW COLUMNS FROM `employees`")->getResultArray();
                                        $colsLocal = array_column($colsResLocal, 'Field');
                                   } catch (\Throwable $colErr) {
                                        // ignore
                                   }

                                   $localCandidates = [
                                        'reporting_manager_empcode',
                                        'reporting_manager_emp_code',
                                        'reporting_manager',
                                        'reporting_manager_emp_id',
                                        'reporting_manager_id',
                                        'reporting_manager_code'
                                   ];

                                   $localReportCol = null;
                                   foreach ($localCandidates as $c) {
                                        if (!empty($colsLocal) && in_array($c, $colsLocal)) {
                                             $localReportCol = $c;
                                             break;
                                        }
                                   }

                                   if ($localReportCol) {
                                        $res2 = $db->table('employees')
                                             ->select('employee_code')
                                             ->where($localReportCol, $userCode)
                                             ->get()
                                             ->getResultArray();
                                        $empCodes = array_column($res2, 'employee_code');
                                   } else {
                                        // try the most common column name directly as a last-ditch
                                        $res2 = $db->table('employees')
                                             ->select('employee_code')
                                             ->where('reporting_manager_empcode', $userCode)
                                             ->get()
                                             ->getResultArray();
                                        $empCodes = array_column($res2, 'employee_code');
                                   }
                              } catch (\Throwable $localErr) {
                                   log_message('warning', 'Regularize::list local employees lookup failed: ' . $localErr->getMessage());
                                   $empCodes = [];
                              }
                         }

                         // Apply filter based on discovered emp codes (or fallback)
                         if (is_array($empCodes) && !empty($empCodes)) {
                              $builder->whereIn('r.emp_code', $empCodes);
                         } else {
                              // no reporting employees found - show manager's own requests as fallback
                              $builder->where('r.emp_code', $userCode);
                              log_message('info', 'Regularize::list no reporting employees found for user ' . $userCode . ', returning own requests only.');
                         }
                    } catch (\Exception $e) {
                         log_message('error', 'Regularize::list reporting manager lookup failed (fatal): ' . $e->getMessage());
                         // Final fallback: show only user's own requests to avoid exposing others
                         $builder->where('r.emp_code', $userCode);
                    }
               }
               // All other roles see only their own requests
               else {
                    $builder->where('r.emp_code', $userCode);
               }

               if ($status !== 'all') {
                    $builder->where('r.status', ucfirst($status));
               }
          }

          // If search provided, try to match employee names from travelapp and apply like conditions
          $matchedEmpCodes = [];
          if ($search !== '') {
               try {
                    $ta = \Config\Database::connect('travelapp');
                    $res = $ta->table('new_emp_master')
                         ->select('emp_code')
                         ->groupStart()
                         ->like('comp_name', $search)
                         ->orLike('fname', $search)
                         ->orLike('lname', $search)
                         ->groupEnd()
                         ->get()
                         ->getResultArray();

                    $matchedEmpCodes = array_column($res, 'emp_code');
               } catch (\Exception $e) {
                    log_message('error', 'Regularize::list search travelapp failed: ' . $e->getMessage());
               }

               $builder->groupStart();
               // numeric searches can match emp_code directly
               $builder->like('r.emp_code', $search);
               $builder->orLike('r.reason', $search);
               // also match by applied_by / approved_by / rejected_by when names match
               if (!empty($matchedEmpCodes)) {
                    $builder->orWhereIn('r.emp_code', $matchedEmpCodes);
                    $builder->orWhereIn('r.applied_by', $matchedEmpCodes);
                    $builder->orWhereIn('r.approved_by', $matchedEmpCodes);
                    $builder->orWhereIn('r.rejected_by', $matchedEmpCodes);
               }
               $builder->groupEnd();
          }

          // clone builder to get total count before limit/offset
          $countBuilder = clone $builder;
          $totalRow = $countBuilder->select('COUNT(*) AS cnt')->get()->getRowArray();
          $total = (int) ($totalRow['cnt'] ?? 0);

          // Fetch paged rows
          $offset = ($page - 1) * $perPage;
          $rows = $builder->select('r.*')
               ->orderBy('r.for_date', 'DESC')
               ->limit($perPage, $offset)
               ->get()
               ->getResultArray();

          // Enrich with employee names
          try {
               $rows = $this->model->enrichWithEmployeeNames($rows);
          } catch (\Exception $e) {
               log_message('error', 'Regularize::list enrich failed: ' . $e->getMessage());
          }

          // Attach related attachments
          $ids = array_column($rows, 'id');
          if (!empty($ids)) {
               $atts = $db->table('regularize_attachments')
                    ->whereIn('request_id', $ids)
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();

               $attachmentsMap = [];
               foreach ($atts as $a) {
                    $attachmentsMap[$a['request_id']][] = $a;
               }

               foreach ($rows as &$row) {
                    $row['attachments'] = $attachmentsMap[$row['id']] ?? [];
               }
               unset($row);
          }

          $meta = [
               'total' => $total,
               'per_page' => $perPage,
               'page' => $page,
               'total_pages' => ($perPage ? (int)ceil($total / $perPage) : 1)
          ];

          return $this->respond(['data' => $rows, 'meta' => $meta]);
     }

     // public function list()
     // {
     //      $auth = $this->validateAuthorization();
     //      if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) return $auth;

     //      $userCode = $auth['user_code'] ?? null;
     //      $role = strtolower($auth['role'] ?? $auth['roles'] ?? '');
     //      $status = strtolower($this->request->getGet('status') ?? 'all');

     //      // pagination + search params
     //      $page = max(1, (int)($this->request->getGet('page') ?? 1));
     //      $perPage = (int)($this->request->getGet('per_page') ?? 10);
     //      $perPage = $perPage < 1 ? 10 : ($perPage > 100 ? 100 : $perPage);
     //      $search = trim($this->request->getGet('search') ?? '');

     //      $db = \Config\Database::connect();

     //      // Use query builder for efficient count + paging
     //      $builder = $db->table('regularize_requests r');

     //      // Permissions & status filter
     //      if ($status === 'my') {
     //           $builder->where('r.emp_code', $userCode);
     //      } else {
     //           if (!in_array($role, ['admin', 'super_admin', 'hod', 'hr'])) {
     //                $builder->where('r.emp_code', $userCode);
     //           }
     //           if ($status !== 'all') {
     //                $builder->where('r.status', ucfirst($status));
     //           }
     //      }

     //      // If search provided, try to match employee names from travelapp and apply like conditions
     //      $matchedEmpCodes = [];
     //      if ($search !== '') {
     //           try {
     //                $ta = \Config\Database::connect('travelapp');
     //                $res = $ta->table('new_emp_master')
     //                     ->select('emp_code')
     //                     ->groupStart()
     //                     ->like('comp_name', $search)
     //                     ->orLike('fname', $search)
     //                     ->orLike('lname', $search)
     //                     ->groupEnd()
     //                     ->get()
     //                     ->getResultArray();

     //                $matchedEmpCodes = array_column($res, 'emp_code');
     //           } catch (\Exception $e) {
     //                log_message('error', 'Regularize::list search travelapp failed: ' . $e->getMessage());
     //           }

     //           $builder->groupStart();
     //           // numeric searches can match emp_code directly
     //           $builder->like('r.emp_code', $search);
     //           $builder->orLike('r.reason', $search);
     //           // also match by applied_by / approved_by / rejected_by when names match
     //           if (!empty($matchedEmpCodes)) {
     //                $builder->orWhereIn('r.emp_code', $matchedEmpCodes);
     //                $builder->orWhereIn('r.applied_by', $matchedEmpCodes);
     //                $builder->orWhereIn('r.approved_by', $matchedEmpCodes);
     //                $builder->orWhereIn('r.rejected_by', $matchedEmpCodes);
     //           }
     //           $builder->groupEnd();
     //      }

     //      // clone builder to get total count before limit/offset
     //      $countBuilder = clone $builder;
     //      $totalRow = $countBuilder->select('COUNT(*) AS cnt')->get()->getRowArray();
     //      $total = (int) ($totalRow['cnt'] ?? 0);

     //      // Fetch paged rows
     //      $offset = ($page - 1) * $perPage;
     //      $rows = $builder->select('r.*')
     //           ->orderBy('r.for_date', 'DESC')
     //           ->limit($perPage, $offset)
     //           ->get()
     //           ->getResultArray();

     //      // Enrich with employee names
     //      try {
     //           $rows = $this->model->enrichWithEmployeeNames($rows);
     //      } catch (\Exception $e) {
     //           log_message('error', 'Regularize::list enrich failed: ' . $e->getMessage());
     //      }

     //      // Attach related attachments
     //      $ids = array_column($rows, 'id');
     //      if (!empty($ids)) {
     //           $atts = $db->table('regularize_attachments')
     //                ->whereIn('request_id', $ids)
     //                ->orderBy('id', 'ASC')
     //                ->get()
     //                ->getResultArray();

     //           $attachmentsMap = [];
     //           foreach ($atts as $a) {
     //                $attachmentsMap[$a['request_id']][] = $a;
     //           }

     //           foreach ($rows as &$row) {
     //                $row['attachments'] = $attachmentsMap[$row['id']] ?? [];
     //           }
     //           unset($row);
     //      }

     //      $meta = [
     //           'total' => $total,
     //           'per_page' => $perPage,
     //           'page' => $page,
     //           'total_pages' => ($perPage ? (int)ceil($total / $perPage) : 1)
     //      ];

     //      return $this->respond(['data' => $rows, 'meta' => $meta]);
     // }



     public function pending()
     {
          $auth = $this->validateAuthorization();
          if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) return $auth;

          // Fetch pending requests
          $list = $this->model->getPending();
          $list = $list ?: [];

          // Attach related attachments for pending requests
          $ids = array_column($list, 'id');
          if (!empty($ids)) {
               $db = \Config\Database::connect();
               $atts = $db->table('regularize_attachments')
                    ->whereIn('request_id', $ids)
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();

               $attachmentsMap = [];
               foreach ($atts as $a) {
                    $attachmentsMap[$a['request_id']][] = $a;
               }

               foreach ($list as &$row) {
                    $row['attachments'] = $attachmentsMap[$row['id']] ?? [];
               }
               unset($row);
          }

          return $this->respond($list);
     }

     // public function list()
     // {
     //      $auth = $this->validateAuthorization();
     //      if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) return $auth;

     //      $userCode = $auth['user_code'] ?? null;
     //      $role = strtolower($auth['role'] ?? $auth['roles'] ?? '');
     //      $status = strtolower($this->request->getGet('status') ?? 'all');

     //      // Build base list according to permissions
     //      if ($status === 'my') {
     //           $list = $this->model->getByEmployee($userCode);
     //      } else {
     //           if (!in_array($role, ['admin', 'super_admin', 'hod', 'hr'])) {
     //                $builder = $this->model->where('emp_code', $userCode);
     //                if ($status !== 'all') $builder->where('status', ucfirst($status));
     //                $list = $builder->findAll();
     //           } else {
     //                $builder = $this->model;
     //                if ($status !== 'all') $builder = $builder->where('status', ucfirst($status));
     //                $list = $builder->findAll();
     //           }
     //      }

     //      $list = $list ?: [];

     //      // Attach related attachments for all requests in one query
     //      $ids = array_column($list, 'id');
     //      if (!empty($ids)) {
     //           $db = \Config\Database::connect();
     //           $atts = $db->table('regularize_attachments')
     //                ->whereIn('request_id', $ids)
     //                ->orderBy('id', 'ASC')
     //                ->get()
     //                ->getResultArray();

     //           $attachmentsMap = [];
     //           foreach ($atts as $a) {
     //                $attachmentsMap[$a['request_id']][] = $a;
     //           }

     //           // Merge attachments into list rows
     //           foreach ($list as &$row) {
     //                $row['attachments'] = $attachmentsMap[$row['id']] ?? [];
     //           }
     //           unset($row);
     //      }

     //      return $this->respond(['data' => $list]);
     // }

     // Return a single request and its attachments

     public function get($id = null)
     {
          $auth = $this->validateAuthorization();
          if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) return $auth;
          if (empty($id)) return $this->failValidationErrors('id is required');

          $req = $this->model->find($id);
          if (!$req) return $this->failNotFound('Request not found');

          $db = \Config\Database::connect();
          $atts = $db->table('regularize_attachments')->where('request_id', $id)->orderBy('id', 'ASC')->get()->getResultArray();

          return $this->respond(['data' => $req, 'attachments' => $atts]);
     }

     // Update an existing request (only when status is Pending)
     public function update($id = null)
     {
          $auth = $this->validateAuthorization();
          if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) return $auth;
          $userCode = $auth['user_code'] ?? null;
          $role = strtolower($auth['role'] ?? $auth['roles'] ?? '');

          if (empty($id)) return $this->failValidationErrors('id is required');

          $req = $this->model->find($id);
          if (!$req) return $this->failNotFound('Request not found');

          if (strtolower($req['status']) !== 'pending') {
               return $this->fail('Only pending requests can be edited', 403);
          }

          // owner or privileged
          if ($req['emp_code'] !== $userCode && !in_array($role, ['admin', 'manager', 'hod', 'hr'])) {
               return $this->failForbidden('Not authorized to edit this request');
          }

          // parse incoming payload (same structure as apply)
          $dataField = $this->request->getPost('data');
          $payload = [];
          if ($dataField) {
               $decoded = json_decode($dataField, true);
               if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $payload = $decoded;
               } else {
                    $payload = $this->request->getPost();
               }
          } else {
               $payload = $this->request->getPost();
          }

          // Basic validation
          $err = [];
          if (empty($payload['reason']) || strlen(trim($payload['reason'])) < 5) $err['reason'] = 'Please provide a reason (min 5 characters).';
          if (!empty($payload['punch_in']) && !empty($payload['punch_out'])) {
               list($ih, $im) = array_map('intval', explode(':', $payload['punch_in']));
               list($oh, $om) = array_map('intval', explode(':', $payload['punch_out']));
               if ($ih * 60 + $im >= $oh * 60 + $om) $err['time'] = 'Out time should be later than In time.';
          }

          if (!empty($err)) return $this->failValidationErrors($err);

          // Update fields
          $update = [
               'punch_in' => $payload['punch_in'] ?? $req['punch_in'],
               'punch_out' => $payload['punch_out'] ?? $req['punch_out'],
               'reason' => $payload['reason'] ?? $req['reason'],
               'edited_by' => $userCode,
               'edited_on' => date('Y-m-d H:i:s'),
               'edit_count' => ($req['edit_count'] ?? 0) + 1
          ];

          // Handle files[] and attachment similar to apply()
          $uploadedFiles = [];
          if (!empty($_FILES) && isset($_FILES['files'])) {
               $filesArr = $_FILES['files'];
               if (is_array($filesArr['name'])) {
                    foreach ($filesArr['name'] as $i => $name) {
                         if ($filesArr['error'][$i] === UPLOAD_ERR_OK) {
                              $uploadedFiles[] = [
                                   'name' => $filesArr['name'][$i],
                                   'tmp_name' => $filesArr['tmp_name'][$i],
                                   'type' => $filesArr['type'][$i],
                                   'size' => $filesArr['size'][$i]
                              ];
                         }
                    }
               } else {
                    if ($filesArr['error'] === UPLOAD_ERR_OK) {
                         $uploadedFiles[] = [
                              'name' => $filesArr['name'],
                              'tmp_name' => $filesArr['tmp_name'],
                              'type' => $filesArr['type'],
                              'size' => $filesArr['size']
                         ];
                    }
               }
          }

          $db = \Config\Database::connect();
          $uploadDir = WRITEPATH . 'uploads/regularize/';
          if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

          // If new files uploaded, save first file and update main attachment_* fields for compatibility
          $updatedMain = false;
          if (!empty($uploadedFiles)) {
               foreach ($uploadedFiles as $f) {
                    $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $f['name']);
                    $dest = $uploadDir . $safeName;
                    if (@move_uploaded_file($f['tmp_name'], $dest)) {
                         $db->table('regularize_attachments')->insert([
                              'request_id' => $id,
                              'file_path' => 'uploads/regularize/' . $safeName,
                              'original_name' => $f['name'],
                              'mime' => $f['type'] ?? null,
                              'size' => $f['size'] ?? null,
                              'created_at' => date('Y-m-d H:i:s')
                         ]);

                         if (!$updatedMain) {
                              $update['attachment_path'] = 'uploads/regularize/' . $safeName;
                              $update['attachment_name'] = $f['name'];
                              $update['attachment_mime'] = $f['type'] ?? null;
                              $update['attachment_size'] = $f['size'] ?? null;
                              $updatedMain = true;
                         }
                    } else {
                         log_message('error', 'Regularize::update failed to move uploaded file: ' . ($f['name'] ?? '(unknown)'));
                    }
               }
          }

          // Legacy single-file path
          $file = $this->request->getFile('attachment');
          if ($file && $file->isValid() && !$file->hasMoved()) {
               $newName = $file->getRandomName();
               if ($file->move($uploadDir, $newName)) {
                    $db->table('regularize_attachments')->insert([
                         'request_id' => $id,
                         'file_path' => 'uploads/regularize/' . $newName,
                         'original_name' => $file->getClientName(),
                         'mime' => $file->getClientMimeType(),
                         'size' => $file->getSize(),
                         'created_at' => date('Y-m-d H:i:s')
                    ]);

                    if (!$updatedMain) {
                         $update['attachment_path'] = 'uploads/regularize/' . $newName;
                         $update['attachment_name'] = $file->getClientName();
                         $update['attachment_mime'] = $file->getClientMimeType();
                         $update['attachment_size'] = $file->getSize();
                         $updatedMain = true;
                    }
               }
          }

          if ($this->model->update($id, $update)) {
               $newReq = $this->model->find($id);
               return $this->respond(['message' => 'Request updated', 'data' => $newReq]);
          }

          return $this->fail('Failed to update request');
     }

     public function checkExisting()
     {
          $auth = $this->validateAuthorization();
          if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) return $auth;

          $empCode = $this->request->getGet('emp_code');
          $forDate = $this->request->getGet('for_date');

          if (empty($empCode) || empty($forDate)) {
               return $this->failValidationErrors('emp_code and for_date are required');
          }

          $normalizedDate = date('Y-m-d', strtotime($forDate));
          if ($normalizedDate === false) {
               return $this->failValidationErrors('Invalid for_date');
          }

          // Check for any existing request (pending, approved, or rejected)
          $existing = $this->model
               ->where('emp_code', $empCode)
               ->where('for_date', $normalizedDate)
               ->first();

          if ($existing) {
               // Attach attachments
               $db = \Config\Database::connect();
               $atts = $db->table('regularize_attachments')
                    ->where('request_id', $existing['id'])
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();

               return $this->respond([
                    'exists' => true,
                    'data' => $existing,
                    'attachments' => $atts
               ]);
          }

          return $this->respond(['exists' => false]);
     }

     // public function downloadAttachment($filename = null)
     // {
     //      // Ensure CORS headers are always present on this endpoint (helps when preflight or filters don't add them)
     //      $origin = $this->request->getHeaderLine('Origin') ?: null;
     //      $allowOrigin = $origin ?: '*';
     //      // Make sure we don't duplicate the Access-Control-Allow-Origin header if a global filter or server already set it
     //      if (method_exists($this->response, 'removeHeader')) {
     //           $this->response->removeHeader('Access-Control-Allow-Origin');
     //      }
     //      $this->response->setHeader('Access-Control-Allow-Origin', $allowOrigin)
     //           ->setHeader('Access-Control-Allow-Credentials', 'true')
     //           ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
     //           ->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With')
     //           ->setHeader('Access-Control-Expose-Headers', 'Content-Disposition')
     //           ->setHeader('Vary', 'Origin');

     //      // Allow preflight OPTIONS without requiring Authorization so browsers can obtain CORS headers
     //      if ($this->request->getMethod() === 'options') {
     //           // send minimal allowed headers and return
     //           log_message('error', 'Regularize::downloadAttachment OPTIONS preflight', ['origin' => $origin]);
     //           // ensure we don't accidentally append an extra Access-Control-Allow-Origin header
     //           if (method_exists($this->response, 'removeHeader')) {
     //                $this->response->removeHeader('Access-Control-Allow-Origin');
     //           }
     //           $this->response->setHeader('Access-Control-Allow-Origin', $origin ?: '*')
     //                ->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With')
     //                ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
     //                ->setHeader('Access-Control-Allow-Credentials', 'true');

     //           // return a minimal response using the controller response object to avoid header duplication
     //           return $this->response->setStatusCode(200)->setBody('');
     //      }

     //      // Log request headers for debugging Authorization/CORS issues
     //      $hasAuthHeader = (bool) $this->request->header('Authorization')?->getValue();
     //      log_message('error', 'Regularize::downloadAttachment request', ['method' => $this->request->getMethod(), 'origin' => $origin, 'hasAuthorization' => $hasAuthHeader]);

     //      $auth = $this->validateAuthorization();
     //      if ($auth instanceof \CodeIgniter\HTTP\ResponseInterface) {
     //           // Ensure error responses also include CORS headers so browsers can see them
     //           if (method_exists($auth, 'removeHeader')) {
     //                $auth->removeHeader('Access-Control-Allow-Origin');
     //           }
     //           $auth->setHeader('Access-Control-Allow-Origin', $origin ?: '*');
     //           $auth->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With');
     //           $auth->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
     //           $auth->setHeader('Access-Control-Allow-Credentials', 'true');
     //           return $auth;
     //      }
     //      if (empty($filename)) return $this->failValidationErrors('filename required');

     //      // sanitize filename
     //      $safe = basename($filename);
     //      $filePath = WRITEPATH . 'uploads/regularize/' . $safe;
     //      if (!file_exists($filePath)) return $this->failNotFound('File not found');

     //      // verify file recorded in DB (optional but recommended)
     //      $db = \Config\Database::connect();
     //      $row = $db->table('regularize_attachments')->where('file_path', 'uploads/regularize/' . $safe)->get()->getRowArray();
     //      if (!$row) return $this->failNotFound('Attachment record not found');

     //      // Check viewing permissions: owner or privileged
     //      $userCode = $auth['user_code'] ?? null;
     //      $role = strtolower($auth['role'] ?? $auth['roles'] ?? '');

     //      // Fetch the request to check ownership
     //      $req = $this->model->find($row['request_id']);
     //      if (!$req) return $this->failNotFound('Request not found');

     //      // Allow access if user is: owner, applied_by, or has privileged role
     //      $isOwner = $req['emp_code'] === $userCode;
     //      $isApplier = $req['applied_by'] === $userCode;
     //      $isPrivileged = in_array($role, ['admin', 'super_admin', 'manager', 'hod', 'hr']);

     //      if (!$isOwner && !$isApplier && !$isPrivileged) {
     //           log_message('error', 'Regularize::downloadAttachment authorization failed', [
     //                'userCode' => $userCode,
     //                'role' => $role,
     //                'emp_code' => $req['emp_code'],
     //                'applied_by' => $req['applied_by']
     //           ]);
     //           return $this->failForbidden('Not authorized to view this file');
     //      }

     //      // Determine MIME type robustly (some PHP builds may not have mime_content_type)
     //      $mime = 'application/octet-stream';
     //      if (function_exists('mime_content_type')) {
     //           $m = @mime_content_type($filePath);
     //           if (!empty($m)) $mime = $m;
     //      } elseif (function_exists('finfo_open')) {
     //           $finfo = finfo_open(FILEINFO_MIME_TYPE);
     //           if ($finfo) {
     //                $m = finfo_file($finfo, $filePath);
     //                if (!empty($m)) $mime = $m;
     //                finfo_close($finfo);
     //           }
     //      } elseif (!empty($row['mime'])) {
     //           // fall back to stored mime in DB
     //           $mime = $row['mime'];
     //      } else {
     //           // as a last resort, infer from extension for common types
     //           $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
     //           $map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
     //           if (isset($map[$ext])) $mime = $map[$ext];
     //      }

     //      // Stream the file rather than loading into memory to avoid OOM on large files
     //      $size = @filesize($filePath);

     //      // Use original name from DB for Content-Disposition so the filename shown to users is correct
     //      $origName = $row['original_name'] ?? $safe;
     //      // sanitize quoted filename (remove problematic characters)
     //      $quotedName = str_replace(["\"", "\\"], ['', ''], $origName);
     //      $encodedName = rawurlencode($origName);

     //      try {
     //           // Set all headers including CORS before sending file
     //           $origin = $this->request->getHeaderLine('Origin') ?: '*';

     //           log_message('error', 'Regularize::downloadAttachment serving file', ['file' => $filePath, 'displayName' => $origName, 'size' => $size, 'mime' => $mime]);

     //           // Clear any output buffers to ensure a clean stream
     //           while (ob_get_level()) {
     //                @ob_end_clean();
     //           }

     //           // Send headers using raw PHP header() function since we're using exit
     //           // This ensures headers are actually sent before the file content
     //           header('Content-Type: ' . $mime);
     //           header('Content-Disposition: inline; filename="' . $quotedName . '"; filename*=UTF-8\'\'' . $encodedName);
     //           header('Content-Length: ' . ($size ?: 0));
     //           header('Content-Transfer-Encoding: binary');
     //           header('Accept-Ranges: bytes');
     //           header('Access-Control-Allow-Origin: ' . $origin);
     //           header('Access-Control-Allow-Credentials: true');
     //           header('Access-Control-Expose-Headers: Content-Disposition, Content-Type, Content-Length');
     //           header('Cache-Control: public, max-age=3600');

     //           // Stream the file directly to output and terminate to prevent additional output
     //           readfile($filePath);
     //           exit;
     //      } catch (\Throwable $e) {
     //           log_message('error', 'Regularize::downloadAttachment failed to stream file: ' . $e->getMessage());
     //           return $this->fail('Failed to serve file');
     //      }
     // }
}
