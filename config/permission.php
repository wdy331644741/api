<?php
return [
    0 => [ // 游客
        'default' => true,
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
    3 => [ // 产品组
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
            ],
            'feedback' => [
                'default' => true,
            ],
            'admin' => [
                'default' => false,
            ]
        ]
    ],
    4 => [ // 渠道组
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
            ],
            'channel' => [
                'default' => true,
            ],
            'img' => [
                'default' => true,
            ],
            'admin' => [
                'default' => false,
            ]
        ]
    ],
];