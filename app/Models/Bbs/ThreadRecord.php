<?php

namespace App\Models\bbs;

use Illuminate\Database\Eloquent\Model;

class ThreadRecord extends Model
{
    //
    protected $table = 'bbs_thread_record';
    protected $guarded = ['created_at','updated_at'];
}
