<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbIntegralmallAddtimesLog extends Model
{
    public $table = 'integralmall_addtimes_logs';
    protected $guarded = ['created_at', 'updated_at'];
}
