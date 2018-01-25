<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HdRatecoupon extends Model
{
    public $table = 'hd_ratecoupon';
    protected $guarded = ['created_at', 'update_at'];
}
