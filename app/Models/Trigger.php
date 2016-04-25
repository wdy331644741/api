<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trigger extends Model
{
    public function activitys(){
        return $this->hasOne('App\Models\Activity');
    }
}
