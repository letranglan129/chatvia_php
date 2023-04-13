<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;

class ViewedMessage extends Model
{
    protected $table = 'viewed_messages';

    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';

    protected $allowedFields = ['message_id', 'user_id', 'viewed_at'];

    public $builder;

    public function __construct()
    {
        parent::__construct();
        // $this->load->database();
        $db = \Config\Database::connect();
        $this->builder = $db->table('viewed_messages');
    }
}
