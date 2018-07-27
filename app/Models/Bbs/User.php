<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    protected $table = 'bbs_users';

    protected $guarded = ['created_at','updated_at'];

    use SoftDeletes;
    protected $dates = ['deleted_at'];

    public function threads(){
        return $this->hasMany('App\Models\Bbs\Thread','user_id','user_id');
    }

    public function black(){
        return $this->hasOne('App\Models\Bbs\ReplyConfig','id','black_type_id');
    }

    public function comments(){
        return $this->hasMany('App\Models\Bbs\Comment','user_id','user_id');
    }


}
