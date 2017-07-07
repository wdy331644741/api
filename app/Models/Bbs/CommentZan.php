<?php

namespace App\Models\Bbs;

use Illuminate\Database\Eloquent\Model;

class CommentZan extends Model
{
    //
    protected $table = 'bbs_comment_zans';
    protected $guarded = ['created_at','updated_at'];
}
