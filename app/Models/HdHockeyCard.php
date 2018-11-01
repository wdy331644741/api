<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HdHockeyCard extends Model
{
    protected $guarded = ['created_at', 'update_at'];
    public $table = 'hd_hockey_card';
}

