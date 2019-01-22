<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HdWeeksGuessLog extends Model
{
    public $table = 'hd_weeks_guess_log';
    protected $guarded = ['created_at', 'updated_at'];
}

