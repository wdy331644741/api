<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statistics extends Model
{
    public $table = 'statistics';
    protected $guarded = ['created_at', 'update_at'];
}
