<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Integer, App\Models\String, App\Models\Html;

class CmsItem extends Model
{
    public function value($version){
        $class = $this->type;
        $type = new $class;

        return $type->where('item_id', $this->id)->where('version', $version)->first();
    }
}
