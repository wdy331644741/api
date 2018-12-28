<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InExchangeLog extends Model
{
    protected $table = 'in_exchange_logs';

    protected $hidden = ['deleted_at'];

    protected $guarded = ['created_at', 'update_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];


    public function prizes(){
        return $this->hasOne('App\Models\InPrize', 'id', 'pid');
    }

    public function prizetypes(){
        return $this->hasOne('App\Models\InPrizetype', 'id', 'type_id');
    }

}
