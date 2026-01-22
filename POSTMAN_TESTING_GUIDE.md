# Postman Testing Guide for Employee Creation API

## API Endpoint
`POST /api/employees/create` (or your specific endpoint)

## Testing Multiple File Uploads

### Method 1: Real File Uploads (Recommended)
Use this method to test actual file uploads like in production.

#### Postman Setup:
1. **Request Type**: POST
2. **Body Type**: form-data
3. **Fields to add**:

```
Key: data
Type: Text
Value: {
  "employee_code": "EMP001",
  "employee_name": "John Doe",
  "designation": "Software Engineer",
  "department": "IT",
  "email": "john@example.com",
  "mobile": "1234567890",
  "documents": []
}

Key: files
Type: File
Value: [Select your first file]

Key: files
Type: File  
Value: [Select your second file]

Key: files
Type: File
Value: [Select your third file]

Key: files
Type: File
Value: [Select your fourth file]

Key: document_names[]
Type: Text
Value: Aadhaar Card

Key: document_names[]
Type: Text
Value: PAN Card

Key: document_names[]
Type: Text
Value: Educational Certificate

Key: document_names[]
Type: Text
Value: Experience Letter
```

### Method 2: JSON with Pre-made Document Paths (For Testing)
Use this when you want to simulate documents that are already uploaded.

#### Postman Setup:
1. **Request Type**: POST
2. **Headers**: Content-Type: application/json
3. **Body Type**: raw (JSON)

```json
{
  "employee_code": "EMP002",
  "employee_name": "Jane Smith", 
  "designation": "HR Manager",
  "department": "HR",
  "email": "jane@example.com",
  "mobile": "9876543210",
  "documents": [
    {
      "document_name": "Aadhaar Card",
      "file_type": "image/jpeg",
      "file_size": 18784,
      "original_name": "DOC_1756283245002_aadhaar.jpeg"
    },
    {
      "document_name": "PAN Card", 
      "file_type": "image/png",
      "file_size": 139506,
      "original_name": "DOC_1756283249579_pan.png"
    },
    {
      "document_name": "Educational Certificate",
      "file_type": "image/png", 
      "file_size": 135196,
      "original_name": "DOC_1756283254453_certificate.png"
    },
    {
      "document_name": "Experience Letter",
      "file_type": "image/png",
      "file_size": 139506, 
      "original_name": "DOC_1756283259002_experience.png"
    }
  ]
}
```

## Backend Processing

### How the Backend Handles Different Scenarios:

1. **Real File Uploads (Method 1)**:
   - Files are processed using `processFile()` method
   - Generates unique filenames with timestamp
   - Moves files to `writable/uploads/employees/` directory
   - Creates database entries with actual file paths

2. **JSON Documents (Method 2)**:
   - Assumes files are already uploaded/available
   - Uses provided `original_name` to construct file paths
   - Creates database entries with pre-made paths

### Database Structure (employee_documents table):
```sql
CREATE TABLE `employee_documents` (
  `doc_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) DEFAULT NULL,
  `document_name` text DEFAULT NULL,
  `document_path` text DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`doc_id`),
  KEY `emp_id` (`emp_id`),
  CONSTRAINT `employee_documents_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE
);
```

## Expected API Response

### Success Response:
```json
{
  "status": true,
  "message": "Employee created successfully",
  "employee_id": 51,
  "employee_code": "EMP001",
  "data": { /* employee data */ },
  "educations_count": 0,
  "experiences_count": 0,
  "files_uploaded": 4,
  "json_documents": 0,
  "total_documents": 4,
  "upload_method": "real_files",
  "created_by": "user123",
  "created_at": "2025-08-27 16:30:00"
}
```

### Upload Method Indicators:
- `"upload_method": "real_files"` - Actual files were uploaded
- `"upload_method": "json_data"` - JSON document data was used
- `"upload_method": "none"` - No documents were processed

## Troubleshooting

### If Only Single File is Uploaded:
1. Check that you're using `files` as the key name (not `files[]`)
2. Ensure multiple file entries have the same key name
3. Verify Content-Type is `multipart/form-data`

### If No Files are Processed:
1. Check the logs for `[FILE_UPLOAD]` entries
2. Verify file sizes are within PHP limits
3. Ensure files are valid and not corrupted

### File Upload Limits:
- `upload_max_filesize`: 4000M
- `post_max_size`: 4000M  
- `max_file_uploads`: 200

## Debug Logging
The backend logs detailed information about file processing:
- `[FILE_UPLOAD]` - File upload process tracking
- `[EMP_DOC_INSERT]` - Document database insertion tracking

Check logs at: `writable/logs/log-YYYY-MM-DD.log`
