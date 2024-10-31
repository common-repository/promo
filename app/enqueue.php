<?php namespace PromoSync;

/** @var \Herbert\Framework\Enqueue $enqueue */

$enqueue->admin([
  'as' => 'adminCSS',
  'src' => Helper::assetUrl('/css/admin.css')
]);

$enqueue->front([
  'as'  => 'frontendCSS',
  'src' => Helper::assetUrl('/css/frontend.css')
]);