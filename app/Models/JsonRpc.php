<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lib\JsonRpcClient;
use Config;

class JsonRpc extends Model
{
    public $config = [];

    public function __construct()
    {
        parent::__construct();
        $this->config = Config::get('jsonrpc.server');
    }

    public function account() {
        return new JsonRpcClient($this->config['account']['url'], $this->config['account']['config']);   
    }
    public function inside() {
        return new JsonRpcClient($this->config['inside']['url'], $this->config['inside']['config']);
    }
}