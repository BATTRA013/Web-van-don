<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$token = (string) config('services.viettel_post.token');
$tracking = '138734476692';
$payload = [
    'TYPE' => 1,
    'ORDER_NUMBER' => $tracking,
];

$bases = [
    'https://partner.viettelpost.vn',
    'https://partner2.viettelpost.vn',
    'https://api.viettelpost.vn',
];

$paths = [
    '/v2/order/getOrderInfo',
    '/v2/order/getOrderByOrderNumber',
    '/api/v2/order/getOrderInfo',
    '/api/v2/order/getOrderByOrderNumber',
];

foreach ($bases as $base) {
    foreach ($paths as $path) {
        try {
            $client = Http::baseUrl($base)
                ->withHeaders([
                    'Token' => $token,
                    'Content-Type' => 'application/json',
                ])
                ->acceptJson()
                ->timeout(20);

            $post = $client->post($path, $payload);
            $get = $client->get($path, $payload);

            echo $base.$path.' | POST '.$post->status().' | GET '.$get->status().PHP_EOL;
        } catch (Throwable $e) {
            echo $base.$path.' | EX '.$e->getMessage().PHP_EOL;
        }
    }
}
