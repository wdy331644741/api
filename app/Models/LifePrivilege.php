<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LifePrivilege extends Model
{
    public $table = 'life_privilege';
    protected $guarded = ['created_at', 'updated_at'];
}

