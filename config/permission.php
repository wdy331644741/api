<?php
return [
    0 => [ // 游客
        'default' => false,
        'items' => [
            'account' => [
                'default' => false,
                'items' => [
                    'login' => [
                        'default' => true,    
                    ],
                    'captcha' => [
                        'default' => true,
                    ]
                ]
            ]
        ]
    ],
    1 => [ // 超级管理员
        'default' => true,
        'items' => []
    ],
    2 => [ // 运营组
        'default' => true,
        'items' => [
            'admin' => [
                'default' => false,
            ]
        ]
    ],
];