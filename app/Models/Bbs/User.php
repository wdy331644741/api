<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'bbs_users';

    public function threads(){
        return $this->hasMany('App\Models\Bbs\Thread','user_id','user_id');
    }


}
