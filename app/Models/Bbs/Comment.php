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

    public  function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if(isset($attributes['userId'])) {
            $this->userId = $attributes['userId'];
        }

    }

    public function users(){
        return $this->hasOne('App\Models\Bbs\User','user_id','user_id');
    }

    public function thread() {
        return $this->hasOne('App\Models\Bbs\Thread', 'id', 'tid');
    }

    public function zan(){
        $userId = $this->userId;

        return $this->hasOne('App\Models\Bbs\CommentZan','cid','id')->where(['status'=>0,'user_id'=>$userId]);
    }
    public function officeReply(){
        //$userId = $this->userId;
        return $this->hasMany('App\Models\Bbs\CommentReply','comment_id','id')->where(['is_verify'=>1,"reply_type"=>"official"]);

    }

    public function replyUser(){
        return $this->hasOne('App\Models\Bbs\User','user_id','t_user_id');
    }

}
