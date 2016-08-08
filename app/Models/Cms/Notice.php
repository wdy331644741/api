<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notice extends Model
{
    protected $table='cms_notices';
    
    use SoftDeletes;
    protected $dates = ['deleted_at'];
}
