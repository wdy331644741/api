<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbIntegralmallLog extends Model
{
    public $table = 'integralmall_logs';
    protected $guarded = ['created_at', 'updated_at'];
}
