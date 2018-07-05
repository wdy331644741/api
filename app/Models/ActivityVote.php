<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityVote extends Model
{
    //
    public $table = 'activity_vote';
    protected $guarded = ['created_at', 'update_at'];
    
}
