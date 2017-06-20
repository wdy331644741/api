<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pm extends Model
{
    protected $table = 'bbs_pms';

    protected $hidden = ['deleted_at'];

    protected $guarded = ['created_at', 'update_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function comments(){
        return $this->hasOne('App\Models\Bbs\Comment','id','comment_id');
    }

    public function threads(){
        return $this->hasOne('App\Models\Bbs\Thread','id','tid');
    }

    public function fromUsers(){
        return $this->hasOne('App\Models\Bbs\User','user_id','from_user_id');
    }

}
