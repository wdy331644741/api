<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class ThreadAttachment extends Model
{
    protected $table = 'bbs_thread_attachments';
    protected $guarded = ['created_at', 'update_at'];
}
