<?php

namespace App\Models\Open;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Weixin extends Model
{
    use SoftDeletes;

    protected $dates = ['delete_at'];
}
