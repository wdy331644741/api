<?php

return [
    "default" => [
        "default" => true,
        'childrens' => [
            "activity" => [
                "default" => false,
                "childrens" => [
                    "list" => false
                ]
            ]
        ]
    ],
    "youke" => [
        "default" => true,
    ],
    "admin" => [
        "default" => true,
    ],
    "caozuoyuan" => [
        "default" => true,
        'child' => [
            'act' => [
                'def' => true,
                'child' => [
                    'put' => false,
                    'del' => false,
                ]
            ]
        ]
    ]

];

