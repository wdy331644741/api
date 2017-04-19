<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class GlobalConfig extends Model
{
    protected $table = 'bbs_global_configs';
    protected $guarded = ['created_at', 'update_at'];
}
