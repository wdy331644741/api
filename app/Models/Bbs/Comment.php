<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    //
    protected $table = 'bbs_comments';

    public function users(){
        return $this->hasOne('App\Models\Bbs\User','user_id','user_id');
    }



}