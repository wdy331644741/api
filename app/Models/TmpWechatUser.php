<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TmpWechatUser extends Model
{
    protected  $table = 'tmp_wecaht_users';
    protected $guarded = ['created_at','updated_at'];

}
