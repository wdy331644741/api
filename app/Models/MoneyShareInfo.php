<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoneyShareInfo extends Model
{
    public $table = 'money_share_info';
    protected $guarded = ['created_at', 'update_at'];
}

