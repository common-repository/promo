<?php namespace PromoSync;

/** @var \Herbert\Framework\Shortcode $shortcode */


/*
Mini promo list: [promoembed new_tab=true target_url=http://example.com]
Full promo list: [promoembed extended=true show_header=true]
Extended list: [promoembed mode=extended]
Single promo: [promoembed id=6]
*/

// removed by jchong. replaced with below. 
//$shortcode->add('PromoEmbed', 'PromoSync::promoEmbed');


// new for v3 -jchong feb10/16
$shortcode->add('promolist', 'PromoSync::promoListShortcode');
$shortcode->add('promo', 'PromoSync::promoShortcode');

// confirm newsletter
$shortcode->add('confirmnewsletter', 'PromoSync::promoConfirmation', [
    'token' => 'token'
  ]);