<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\InPrizetype;

class InPrize extends Model
{
    protected $table = 'in_prizes';

    protected $hidden = ['deleted_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function prizetypes(){
            return $this->hasOne('App\Models\InPrizetype', 'id', 'type_id');
    }
}
