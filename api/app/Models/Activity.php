<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    protected $dates = ['deleted_at'];

    public function rules(){
        return $this->hasMany('App\Models\Rule');
    }
}
