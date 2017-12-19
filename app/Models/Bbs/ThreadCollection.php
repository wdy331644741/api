<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class ThreadCollection extends Model
{
    protected $table = 'bbs_thread_collections';
    protected $guarded = ['created_at', 'update_at'];

    public function thread() {
        return $this->hasOne('App\Models\Bbs\Thread', 'id', 'tid')->where(['isverify'=>1]);
    }
}
