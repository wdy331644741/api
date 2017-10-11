<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class GroupTask extends Model
{
    protected $table = "bbs_group_tasks";

    protected $guarded = ['created_at','updated_at'];

    public function tasks()
    {
        return $this->hasMany('App\Models\Bbs\Tasks','group_id','id');
    }
}
