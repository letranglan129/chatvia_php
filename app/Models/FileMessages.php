<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;

class FileMessages extends Model
{
    protected $table = 'file_messages';

    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';

    protected $allowedFields = ['message_id', 'href', 'name', 'size'];

    public $builder;

    public function __construct()
    {
        parent::__construct();
        // $this->load->database();
        $db = \Config\Database::connect();
        $this->builder = $db->table('file_messages');
    }
}
