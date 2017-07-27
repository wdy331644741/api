<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LifePrivilegeConfig extends Model
{
    public $table = 'life_privilege_config';
    protected $guarded = ['created_at', 'updated_at'];
}

