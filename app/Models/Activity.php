<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{

    protected $hidden = ['deleted_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function rules(){
        return $this->hasMany('App\Models\Rule','activity_id','id');
    }

    public function triggers(){
        return $this->hasOne('App\Models\Trigger');
    }

    public function awards(){
        return $this->hasMany('App\Models\Award','activity_id','id');
    }

}
