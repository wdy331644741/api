<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    protected $table = 'bbs_comments';

    protected $guarded = ['created_at','updated_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function users(){
        return $this->hasOne('App\Models\Bbs\User','user_id','user_id');
    }

    public function thread() {
        return $this->belongsTo('App\Models\Bbs\Thread', 'tid', 'id');
    }

}
