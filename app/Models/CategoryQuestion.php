<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class CategoryQuestion extends Model
{
    public $table = 'q_categories_question';
    protected $guarded = ['created_at', 'updated_at'];
    protected $hidden = ['created_at', 'updated_at'];
}
