<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriggerLog extends Model
{
    protected $guarded = ['created_at'];
    public $table = 'trigger_log';
}

