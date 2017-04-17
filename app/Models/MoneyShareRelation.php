<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoneyShareRelation extends Model
{
    public $table = 'money_share_relations';
    protected $guarded = ['created_at', 'update_at'];
}
