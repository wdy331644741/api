<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    public $table = 'admins';
    protected $guarded = ['created_at', 'update_at'];
    public function privilege() {
        return $this->hasOne('App\Models\Privilege', 'id', 'privilege_id');
    }
}

