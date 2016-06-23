<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentType extends Model
{
    protected $table = 'cms_content_types';

    protected $hidden = ['deleted_at','updated_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function contents(){
        return $this->hasMany('App\Models\Cms\Content','type_id','id');
    }
}
