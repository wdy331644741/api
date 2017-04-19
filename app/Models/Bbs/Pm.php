<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class Pm extends Model
{
    protected $table = 'bbs_pms';

    public function comments(){
        return $this->hasOne('App\Models\Bbs\Comment','id','cid');
    }

    public function threads(){
        return $this->hasOne('App\Models\Bbs\Thread','id','tid');
    }

    public function fromUsers(){
        return $this->hasOne('App\Models\Bbs\User','user_id','from_user_id');
    }

}
