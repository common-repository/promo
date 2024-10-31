<?php namespace PromoSync;

/** @var \Herbert\Framework\Router $router */

$router->post([
  'as'   => 'simpleRoute',
  'uri'  => '/promo-sync/{webhook_token}/webhook',
  'uses' => __NAMESPACE__.'\Controllers\ApiController@index',
]);