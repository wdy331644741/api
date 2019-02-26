<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HdTwelve extends Model
{
    public $table = 'hd_custom_log';
    protected $guarded = ['created_at', 'update_at'];
}
