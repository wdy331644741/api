<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsString extends Model
{
    public function items() {
        return $this->morphMany('App\Model\CmsItem', 'valuable');
    }
}
