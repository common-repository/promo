<?php


return [

    /**
     * The Herbert version constraint.
     */
    'constraint' => '~0.9.9',

    /**
     * Auto-load all required files.
     */
    'requires' => [
        __DIR__ . '/app/showPromoLogic.php',
        __DIR__ . '/app/functions.php'
    ],
    
    /**
     * The tables to manage.
     */
    'tables' => [
    ],


    /**
     * Activate
     */
    'activators' => [
        __DIR__ . '/app/activate.php'
    ],

    /**
     * Activate
     */
    'deactivators' => [
        __DIR__ . '/app/deactivate.php'
    ],

    /**
     * The shortcodes to auto-load.
     */
    'shortcodes' => [
        __DIR__ . '/app/shortcodes.php'
    ],

    /**
     * The widgets to auto-load.
     */
    'widgets' => [
        __DIR__ . '/app/widgets.php'
    ],

    /**
     * The widgets to auto-load.
     */
    'enqueue' => [
        __DIR__ . '/app/enqueue.php'
    ],

    /**
     * The routes to auto-load.
     */
    'routes' => [
        'PromoSync' => __DIR__ . '/app/routes.php'
    ],

    /**
     * The panels to auto-load.
     */
    'panels' => [
        'PromoSync' => __DIR__ . '/app/panels.php'
    ],

    /**
     * The APIs to auto-load.
     */
    'apis' => [
        'PromoSync' => __DIR__ . '/app/api.php'
    ],

    /**
     * The view paths to register.
     *
     * E.G: 'PromoSync' => __DIR__ . '/views'
     * can be referenced via @PromoSync/
     * when rendering a view in twig.
     */
    'views' => [
        'PromoSync' => __DIR__ . '/resources/views'
    ],

    /**
     * The view globals.
     */
    'viewGlobals' => [

    ],

    /**
     * The asset path.
     */
    'assets' => '/resources/assets/',

    /**
     * Custom config variables
     */
    'website_links' => [
        'about' => 'https://selfserve.promo.co/about',
        'help' => 'https://selfserve.promo.co/help',
        'faq' => 'https://selfserve.promo.co/faq',
        'contact' => 'https://selfserve.promo.co/contact',
        'terms' => 'https://selfserve.promo.co/terms',
        'pricing' => 'https://selfserve.promo.co/pricing',
        'signup' => 'https://selfserve.promo.co/register',
        'forgot' => 'https://selfserve.promo.co/forgot'
    ],

    'db_prefix' => 'wp_promo_',

    'promo_post_type' => 'promo',
    
    'promo_meta_key' => 'wp_promo_id',
    
    'promo_meta_prefix' => 'promo_',
    'promo_meta_private_prefix' => '_promo_',
    
    'promo_webhook_token' => 'wp_promo_webhook_token',

    'promoApiBase' => 'http://api.promo.co',

    'promoApiEndpoints' => [
        'sign_in' => '/v3/access_token',
        'wordpress_account' => '/v3/wordpress_accounts/{wordpress_id}',
        'get_wordpress_account' => '/v3/businesses/{business_id}/wordpress_account',
        'businesses' => '/v3/businesses',
        'promotions' => '/v3/businesses/{business_id}/promotions',
        'promotion' => '/v3/promotions/{promo_id}',
        'embed_code' => '/v2/embed_code',
        'confirm_newsletter' => '/v3/newsletter_subscriptions/{confirm_token}'
    ]

];
