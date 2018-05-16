<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HdCollectCard extends Model
{
    public $table = 'hd_collect_card';
    protected $guarded = ['created_at', 'updated_at'];
}
