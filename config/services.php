<?php

/*
|--------------------------------------------------------------------------
| CONFIG SERVICES
|--------------------------------------------------------------------------
| Cau hinh thong tin ket noi cac dich vu ben ngoai (GHN, mail providers...).
| Token/URL cua third-party thuong duoc map vao file nay.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ghn' => [
        'base_url' => env('GHN_BASE_URL', 'https://online-gateway.ghn.vn'),
        'token' => env('GHN_TOKEN'),
        'shop_id' => env('GHN_SHOP_ID'),
        'from_name' => env('GHN_FROM_NAME'),
        'from_phone' => env('GHN_FROM_PHONE'),
        'from_address' => env('GHN_FROM_ADDRESS'),
        'from_district_id' => env('GHN_FROM_DISTRICT_ID'),
        'from_ward_code' => env('GHN_FROM_WARD_CODE'),
        'return_phone' => env('GHN_RETURN_PHONE'),
        'return_address' => env('GHN_RETURN_ADDRESS'),
        'return_district_id' => env('GHN_RETURN_DISTRICT_ID'),
        'return_ward_code' => env('GHN_RETURN_WARD_CODE'),
    ],

    'viettel_post' => [
        'base_url' => env('VIETTEL_POST_BASE_URL', 'https://partner.viettelpost.vn'),
        'token' => env('VIETTEL_POST_TOKEN'),
        'shop_id' => env('VIETTEL_POST_SHOP_ID'),
        'username' => env('VIETTEL_POST_USERNAME'),
        'password' => env('VIETTEL_POST_PASSWORD'),
        'customer_id' => env('VIETTEL_POST_CUSTOMER_ID'),
        'sender_groupaddress_id' => env('VIETTEL_POST_SENDER_GROUPADDRESS_ID'),
    ],

];
