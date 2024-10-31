<?php

use PromoSync\Controllers\ApiController;
use Herbert\Framework\Http;
use PromoSync\Helper;
use PromoSync\Models\Promo;

// Extends the default timeout for wp_remote_* requests to 10 seconds, instead of 5
apply_filters('http_request_timeout', 10);

//Add route tag
add_action('init', function () {
  add_rewrite_tag('%promo_webhook%', '(.+)');
});

add_action('parse_request', 'capture_promo_webhook');
function capture_promo_webhook ($wp) {
  if (array_key_exists("promo_webhook", $wp->query_vars)) {
    $webhook_token = $wp->query_vars["promo_webhook"];
    $response = ApiController::index($webhook_token, Http::capture());
    die($response);
  }
  return $wp;
}

add_action('init', 'register_promo_custom_post_type', 9);   // lower priority to make sure this is called before Admin panels loaded -jchong

function register_promo_custom_post_type () {
  $post_type = Helper::get("promo_post_type");
  if (!post_type_exists($post_type)) {
    Promo::registerCustomPostType();
  }
}

if ( ! function_exists( 'unregister_post_type' ) ) :
function unregister_post_type ( $post_type ) {
  global $wp_post_types;
  if ( isset( $wp_post_types[ $post_type ] ) ) {
    unset( $wp_post_types[ $post_type ] );
    return true;
  }
  return false;
}
endif;

//add_filter('post_thumbnail_html', 'PromoSync\Models\Promo::filterPostThumbnailHtml', 10, 2);
add_filter('admin_post_thumbnail_html', 'PromoSync\Models\Promo::filterPostThumbnailHtml', 10, 2);

add_filter('plugin_action_links_' . PROMO_BASENAME, 'promo_settings_link');  
function promo_settings_link($links) {
  array_unshift($links, '<a href="' . admin_url('admin.php?page=promo-index') . '">Settings</a>');
  return $links;
}

function get_confirm_token() {
   return $_GET['token'];
}

function get_confirm_type() {
   return $_GET['act'];
}