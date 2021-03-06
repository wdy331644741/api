<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HdAmountShareEleven extends Model
{
    protected $hidden = ['deleted_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public $table = 'hd_amount_share_eleven';
}

