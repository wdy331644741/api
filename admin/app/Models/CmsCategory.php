<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsCategory extends Model
{
    public function items() {
        return $this->hasMany('App\Models\CmsItem', 'category_id');
    }


}
