<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class CommentZan extends Model
{
    //
    protected $table = 'bbs_comment_zans';
    protected $guarded = ['created_at','updated_at'];

    public  function user(){

        return $this->hasOne('App\Models\Bbs\User','user_id','user_id');

    }
}
