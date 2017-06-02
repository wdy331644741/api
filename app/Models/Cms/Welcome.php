<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;

class Welcome extends Model
{
    protected $table='cms_welcomes';

    protected $guarded = ['created_at', 'update_at'];
}
