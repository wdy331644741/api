<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    public function rules(){
        return $this->hasMany('App\Models\Rule');
    }
}
