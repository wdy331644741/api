<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Question extends Model
{
    public $table = 'q_questions';
    protected $guarded = ['created_at', 'update_at'];
}
