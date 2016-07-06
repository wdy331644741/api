<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lib\JsonRpcClient;
use Config;

class JsonRpc extends Model
{

    public function __construct()
    {
        parent::__construct();
        $this->config = Config::get('jsonrpc.server');
    }

    public function account() {
        return new JsonRpcClient($this->config['account']['url'], $this->config['account']['config']);   
    }
}