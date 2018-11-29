<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SendPush extends Model
{
    public $table = 'send_push';
    public $guarded = ['created_at', 'updated_at'];
}
