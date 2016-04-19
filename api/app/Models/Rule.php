<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    public function getRuleByType($type) {
        $model = config('activity.rule_child.'.$type.'.model_path');
        return $this->hasOne($model,'id','rule_id');
    }
}
