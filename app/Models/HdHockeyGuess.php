<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HdHockeyGuess extends Model
{
    protected $guarded = ['created_at', 'update_at'];
    public $table = 'hd_hockey_guess';
}

