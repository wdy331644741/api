<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HdCustomAward extends Model
{
    public $table = 'hd_custom_award';
    protected $guarded = ['created_at', 'updated_at'];
}
