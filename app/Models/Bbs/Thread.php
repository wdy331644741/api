<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Thread extends Model
{

    protected $table = 'bbs_threads';

    protected $hidden = ['deleted_at'];

    protected $guarded = ['created_at', 'update_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $userId;

    public  function __construct(array $attributes = [])
    {
        parent::__construct();
        if($attributes) {
            $this->userId = $attributes['userId'];
        }
    }

    public function users(){
        return $this->hasOne('App\Models\Bbs\User','user_id','user_id');
    }

    public function sections(){
        return $this->hasOne('App\Models\Bbs\ThreadSection','id','type_id');
    }

    public function comments(){
        return $this->hasMany('App\Models\Bbs\Comment','tid','id');
    }
    public function commentAndVerify(){

        $userId = $this->userId;
        return $this->hasMany('App\Models\Bbs\Comment','tid','id')->where(['isverify'=>1])->orwhere(function($query)use($userId){$query->where(['user_id'=>$userId]);});
    }
}
