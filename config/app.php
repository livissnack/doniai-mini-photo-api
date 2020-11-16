<?php

return [
    'id' => 'user_api',
    'env' => env('APP_ENV', 'prod'),
    'debug' => env('APP_DEBUG', false),
    'version' => '1.1.1',
    'timezone' => 'PRC',
    'master_key' => env('MASTER_KEY'),
    'params' => [
        'wechat_mini_app_id' => env('WECHAT_MINI_APPID'),
        'wechat_mini_app_secret' => env('WECHAT_MINI_APPSECRET'),
        'wechat_base_url' => env('WECHAT_SERVER_API_BASEURL'),
        'ali_wuliu_base_url' => env('ALI_CLOUD_API_WULIU_BASEURL'),
        'ali_app_code' => env('ALI_CLOUD_APP_CODE'),
        'ali_oss_access_key' => env('ALI_OSS_ACCESS_KEY'),
        'ali_oss_access_secret' => env('ALI_OSS_ACCESS_SECRET'),
        'ali_oss_bucket_name' => env('ALI_OSS_BUCKET_NAME', 'doniai-mini'),
    ],
    'aliases' => [],
    'components' => [
        '!httpServer' => ['port' => 9501,  'max_request' => 1000000, 'use_globals' => true],
        'db' => [env('DB_URL')],
        'redis' => [env('REDIS_URL')],
        'logger' => ['level' => env('LOGGER_LEVEL', 'info')],
        'restClient' => ['proxy' => env('REST_CLIENT_PROXY', '')],
    ],
    'services' => [
        'baiduService' => [
            'base_url' => env('BAIDU_AI_URL'),
            'access_key' => env('BAIDU_ACCESS_KEY'),
            'secret_key' => env('BAIDU_SECRET_KEY'),
        ]
    ],
    'listeners' => [],
    'plugins' => [
        'cors',
        'tracer',
        'slowlog',
        'debugger',
        'logger',
    ]
];
