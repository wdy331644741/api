<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InPrizetype extends Model
{
    protected $table = 'in_prizetypes';

    protected $hidden = ['deleted_at'];

    protected $guarded = ['created_at', 'update_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function prizes(){
        return $this->hasMany('App\Models\InPrize', 'type_id', 'id');
    }
}
