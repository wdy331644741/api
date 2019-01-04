<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HdWeeksGuess extends Model
{
    public $table = 'hd_weeks_guess';
    protected $guarded = ['created_at', 'updated_at'];
}

