<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | List of available payment gateways that can be used for processing
    | payments. These gateways are registered in the PaymentManager.
    |
    */
    'gateways' => [
        'paypal',
        'stripe',
        'creditcard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    | The default payment gateway to use when none is specified.
    |
    */
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'stripe'),

    /*
    |--------------------------------------------------------------------------
    | Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for each payment gateway. These values can be overridden
    | in the .env file.
    |
    */
    'paypal' => [
        'enabled' => env('PAYPAL_ENABLED', true),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
    ],

    'stripe' => [
        'enabled' => env('STRIPE_ENABLED', true),
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'creditcard' => [
        'enabled' => env('CREDITCARD_ENABLED', true),
        'api_key' => env('CREDITCARD_API_KEY'),
        'api_secret' => env('CREDITCARD_API_SECRET'),
    ],
];
