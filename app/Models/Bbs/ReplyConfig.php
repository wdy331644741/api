<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class ReplyConfig extends Model
{
    protected $table = 'bbs_replay_configs';
    protected $guarded = ['created_at','updated_at'];
}
