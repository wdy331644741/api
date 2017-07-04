<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class ThreadCollection extends Model
{
    protected $table = 'bbs_thread_collections';
    protected $guarded = ['created_at', 'update_at'];
}