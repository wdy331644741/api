<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InviteLimitTask extends Model
{
    public $table = 'friend_30_limit_task';
    protected $guarded = ['created_at', 'updated_at'];
}
