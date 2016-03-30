<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;

    protected $dates = ['delete_at'];

    public function rules(){
        return $this->hasMany('App\Models\Rule');
    }

    public function triggers(){
        return $this->hasOne('App\Models\Trigger');
    }

}
