<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    public $table = 'records';
    protected $guarded = ['created_at', 'update_at'];
}
