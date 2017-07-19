<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class Tasks extends Model
{
    protected $table = "bbs_tasks";

    protected $guarded = ['created_at','updated_at'];
}
