<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class CommentReply extends Model
{
    //
    protected $table = 'bbs_comment_reply';

    protected $guarded = ['created_at','updated_at'];

    public  function user(){

        return $this->hasOne('App\Models\Bbs\User','user_id','from_id')->select("user_id","nickname","isadmin");
    }
    public  function replycomment(){

        return $this->hasOne('App\Models\Bbs\Comment','id','comment_id');
    }


}
