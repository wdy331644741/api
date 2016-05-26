<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $table = 'cms_contents';

    protected $hidden = ['deleted_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];
}
