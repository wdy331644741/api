<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class HdWorldCupConfig extends Model
{
    public $table = 'hd_world_cup_config';
    protected $guarded = ['created_at', 'update_at'];
    protected $hidden = ['deleted_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];
}
