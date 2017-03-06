<?php
namespace App\Service;

use App\Models\Tzyxj;
use App\Models\TzyxjUniquRecord;

class TzyxjService
{
    static function addRecord($userId, $amount) {
        Tzyxj::create(['user_id' => $userId, 'amount' => $amount, 'week' => date('W')]);
        $res = TzyxjUniquRecord::where(['amount' => $amount, 'week' => date('W')])->first();
        if($res) {
            $res->increment('number', 1, ['user_id' => $userId]);
            $number = $res->number;
        }else{
            TzyxjUniquRecord::create([
                'amount' => $amount,
                'user_id' => $userId,
                'number' => 1,
                'week' => date('W'),
            ]);
            $number = 1;
        }
        return $number;
    }
}
