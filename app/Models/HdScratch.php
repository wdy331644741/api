<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HdScratch extends Model
{
    public $table = 'hd_scratch';
    protected $guarded = ['created_at', 'updated_at'];
}
