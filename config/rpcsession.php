<?php

return [
    'handler' => env('SESSION_DRIVER'),
    'path' => env('SESSION_SAVE_PATH'),
    'name' => env('SESSION_NAME'),
    'domain' => env('SESSION_DOMAIN'),
    'accountgroup' => env('ACCOUNT_BASE_HOST'),
    'token_lefetime' => env('APP_TOKEN_MAXLIFETIME'),
];
