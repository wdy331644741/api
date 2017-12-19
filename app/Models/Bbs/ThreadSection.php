<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class ThreadSection extends Model
{
    protected $table = 'bbs_thread_sections';
    protected $guarded = ['created_at','updated_at'];
}
