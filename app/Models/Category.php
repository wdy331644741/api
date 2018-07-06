<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Category extends Model
{
    public $table = 'q_categories';
    protected $guarded = ['created_at', 'updated_at'];

    public function categoryQuestion() {
        return $this->hasMany('App\Models\CategoryQuestion', 'c_id', 'id');
    }
}
