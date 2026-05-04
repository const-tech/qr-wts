<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default driver
    |--------------------------------------------------------------------------
    |
    | The driver used to talk to the remote WhatsApp gateway.
    | Built-in: "cwts" (c-wts.com REST API), "null" (testing).
    |
    */
    'driver' => env('WHATSAPP_GATEWAY_DRIVER', 'cwts'),

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    */
    'drivers' => [

        'cwts' => [
            'class'     => \Almarwa\WhatsappGateway\Drivers\CwtsDriver::class,
            'base_url'  => env('WHATSAPP_GATEWAY_BASE_URL', 'https://c-wts.com'),
            'timeout'   => 30,
            'verify_ssl' => env('WHATSAPP_GATEWAY_VERIFY_SSL', false),

            // The c-wts.com signup URL the customer is sent to so they can
            // create their own instance and obtain {instance_id, access_token}.
            'signup_url' => env('WHATSAPP_GATEWAY_SIGNUP_URL', 'https://c-wts.com/signup'),
            'login_url'  => env('WHATSAPP_GATEWAY_LOGIN_URL',  'https://c-wts.com/login'),

            // Public REST endpoints (relative to base_url). These match the
            // documented c-wts.com /docs page. Edit here only if the remote
            // gateway changes its routes — no code changes needed.
            'endpoints' => [
                'status'  => '/api/status',
                'qrcode'  => '/api/qrcode',
                'send'    => '/api/send',
                // Optional/private reseller endpoints — leave empty unless the
                // remote provides them and you are a reseller.
                'register' => env('WHATSAPP_GATEWAY_REGISTER_ENDPOINT'),
                'restart'  => env('WHATSAPP_GATEWAY_RESTART_ENDPOINT'),
                'packages' => env('WHATSAPP_GATEWAY_PACKAGES_ENDPOINT'),
                'upgrades' => env('WHATSAPP_GATEWAY_UPGRADES_ENDPOINT'),
            ],

            // The reseller / admin token. Only used if you have a private
            // reseller API at c-wts.com that lets you create instances on
            // behalf of customers. Leave empty for the standard "claim
            // credentials" flow.
            'admin_token' => env('WHATSAPP_GATEWAY_ADMIN_TOKEN'),
        ],

        'null' => [
            'class' => \Almarwa\WhatsappGateway\Drivers\NullDriver::class,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription flow
    |--------------------------------------------------------------------------
    |
    | "reseller"  — (default) the customer enters only their basic info,
    |               your server calls the c-wts.com reseller API to create
    |               an instance + access_token automatically, then redirects
    |               to the QR pairing page. Requires admin_token + register
    |               endpoint to be configured.
    | "claim"     — fallback flow when you don't have reseller access:
    |               the customer signs up at c-wts.com themselves and pastes
    |               {instance_id, access_token} on our page.
    |
    */
    'flow' => env('WHATSAPP_GATEWAY_FLOW', 'reseller'),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled'    => true,
        'prefix'     => env('WHATSAPP_GATEWAY_ROUTE_PREFIX', 'whatsapp'),
        'name'       => 'whatsapp-gateway.',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'enabled'    => true,
        'table'      => 'wa_subscriptions',
        'connection' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    */
    'branding' => [
        'app_name'      => env('APP_NAME', 'Al Marwa'),
        'support_phone' => env('WHATSAPP_GATEWAY_SUPPORT_PHONE', '0506499275'),
        'home_url'      => env('WHATSAPP_GATEWAY_HOME_URL'),
        'logo'          => env('WHATSAPP_GATEWAY_LOGO'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Free package preview (shown on landing)
    |--------------------------------------------------------------------------
    |
    | Static preview shown on the landing page. The package list is not
    | fetched from c-wts.com because /docs does not expose a public packages
    | endpoint — packages are managed inside c-wts.com directly.
    |
    */
    'free_package' => [
        'id'           => 'free',
        'name'         => 'الباقة المجانية',
        'price'        => 0,
        'currency'     => 'SAR',
        'duration_days' => 14,
        'features' => [
            'تفعيل فوري عبر QR Code',
            'إرسال رسائل واتساب من تطبيقك',
            'قوالب جاهزة (تذكيرات / تأكيدات / إلغاء)',
            'دعم فني عربي',
        ],
    ],

];
