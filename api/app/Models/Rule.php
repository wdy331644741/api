<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    public function rule_register(){
        return $this->hasOne('App\Models\Rule\Register');
    }

    public function rule_invite(){
        return $this->hasOne('App\Models\Rule\Invite');
    }

    public function rule_userlevel(){
        return $this->hasOne('App\Models\Rule\Userlevel');
    }

    public function rule_usercredit(){
        return $this->hasOne('App\Models\Rule\Usercredit');
    }

    public function rule_balance(){
        return $this->hasOne('App\Models\Rule\Balance');
    }

    public function rule_firstcast(){
        return $this->hasOne('App\Models\Rule\Firstcast');
    }

    public function rule_cast(){
        return $this->hasOne('App\Models\Rule\Cast');
    }
}
