<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalAttribute extends Model
{
    protected $guarded = ['created_at', 'update_at'];
}
