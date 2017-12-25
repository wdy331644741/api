<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GanenInfo extends Model
{
    public $table = 'ganen_info';
    protected $guarded = ['created_at', 'update_at'];
}
