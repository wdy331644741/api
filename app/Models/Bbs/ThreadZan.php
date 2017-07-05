<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class ThreadZan extends Model
{
    //
    protected $table = 'bbs_thread_zans';
    protected $guarded = ['created_at','updated_at'];
}
