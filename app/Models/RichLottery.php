<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RichLottery extends Model
{
    public $table = 'rich_lottery';
    protected $guarded = ['created_at', 'update_at'];
}
