<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Idiom extends Model
{
    protected $table ="cms_idioms";

    protected $hidden = ['deleted_at'];

    use SoftDeletes;

    protected $dates = ['deleted_at'];
}
