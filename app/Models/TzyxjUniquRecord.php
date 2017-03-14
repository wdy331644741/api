<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TzyxjUniquRecord extends Model
{
    public $table = 'tzyxj_unique_record';
    protected $guarded = ['created_at', 'update_at'];
}
