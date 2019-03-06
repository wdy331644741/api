<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HdCustom extends Model
{
    public $table = 'hd_custom';
    protected $guarded = ['created_at', 'updated_at'];

    public function customAwards()
    {
        return $this->hasMany('App\Models\HdCustomAward', 'custom_id', 'id');
    }
}
