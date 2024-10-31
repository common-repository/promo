<?php namespace PromoSync;

/** @var \Herbert\Framework\Panel $panel */

$panel->add([
  'type'   => 'panel',
  'as'     => 'mainPanel',
  'title'  => 'Promo',
  'slug'   => 'promo-index',
  'icon'   => Helper::assetUrl('/img/icon.ico'),
  'uses'   => __NAMESPACE__ . '\Controllers\AdminController@index',
  'post.sync' => __NAMESPACE__ . '\Controllers\AdminController@sync',
  'post.saveandsync' => __NAMESPACE__ . '\Controllers\AdminController@saveandsync',
  'post.authorize' => __NAMESPACE__ . '\Controllers\AdminController@authorize',
  'post.select_business' => __NAMESPACE__ . '\Controllers\AdminController@selectBusiness',
  'post.deauthorize' => __NAMESPACE__ . '\Controllers\AdminController@deauthorize',
  'post.reset' => __NAMESPACE__ . '\Controllers\AdminController@reset',
  'post.generate_archive' => __NAMESPACE__ . '\Controllers\AdminController@generateArchive',
]);