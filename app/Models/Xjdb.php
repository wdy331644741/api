<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Xjdb extends Model
{

    public $table = 'xjdb';
    protected $guarded = ['created_at', 'update_at'];
}
