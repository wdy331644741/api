<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbIntegralmall extends Model
{
    public $table = 'integralmalls';
    protected $guarded = ['created_at', 'updated_at'];
}
