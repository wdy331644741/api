<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignInSystem extends Model
{
    public $table = 'sign_in_system';
    protected $guarded = ['created_at', 'update_at'];
}
