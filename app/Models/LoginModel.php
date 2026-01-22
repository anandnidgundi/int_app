<?php
namespace App\Models;

use CodeIgniter\Model;

class LoginModel extends Model
{
    protected $table = 'login_sessions'; // Your table name
    protected $primaryKey = 'id';      // Primary key of the table
    protected $allowedFields = [
        'session_id', 
        'ip_address', 
        'bmid',
		'logged_in_time',
        'type'
    ]; // Fields you can insert/update

   public function getLoggedInRecords()
{
    return $this->where('type', 'logged_in')  // Filtering for 'logged_in' type
                ->orderBy('ID', 'DESC')  // Sorting by 'logged_in_time' in descending order
                ->groupBy('bmid')  // Grouping by 'bmid' to get unique records
                ->findAll();  // This will return unique 'bmid' records
}

}