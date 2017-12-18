<?php
return [
    'alias_name' => 'payment_activity',
    'day_rule' => [
        [
            'min' => 1,
            'max' => 90,
            'award' => [
                'min'=>[
                    'num' => 0.015,
                    'period' => 1
                ],
                'mid' => [
                    'num' => 0.018,
                    'period' => 3
                ],
                'max' => [
                    'num' => 0.02,
                    'period' => 6
                ]
            ]
        ],[
            'min' => 90,
            'max' => 360,
            'award' => [
                'min'=>[
                    'num' => 0.015,
                    'period' => 3
                ],
                'mid' => [
                    'num' => 0.018,
                    'period' => 6
                ],
                'max' => [
                    'num' => 0.02,
                    'period' => 12
                ]
            ]
        ],[
            'min' => 360,
            'max' => 1000000,
            'award' => [
                'min'=>[
                    'num' => 0.015,
                    'period' => 6
                ],
                'mid' => [
                    'num' => 0.018,
                    'period' => 12
                ],
                'max' => [
                    'num' => 0.02,
                    'period' => 18
                ]
            ]
        ]
    ]
];
