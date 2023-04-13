<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;

class Database extends Model
{
    public $db;

    public function __construct()
    {
        parent::__construct();
        $db = \Config\Database::connect();
    }
}
