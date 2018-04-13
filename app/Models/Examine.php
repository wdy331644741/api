<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Examine extends Model
{
    public $table = 'examine';
    protected $guarded = ['created_at', 'updated_at'];
}
