<?php namespace PromoSync;

/** @var \Herbert\Framework\API $api */

use PromoSync\Helper;
use PromoSync\Models\Promo;

/**
 * Gives you access to the Helper class from Twig
 * {{ MyPlugin.helper('assetUrl', 'icon.png') }}
 */
$api->add('helper', function ()
{
    $args = func_get_args();
    $method = array_shift($args);

    return forward_static_call_array(__NAMESPACE__ . '\\Helper::' . $method, $args);
});

/**
 * Returns a promo embed script tag
 */
$api->add('promoEmbed', function ($options) {
  
  $default = array(
    "type" => "single", // "single", "mini", "full"
    "promo_id" => null, // works with "single" type embeds
    "new_tab" => false, // works with "mini" embeds
    "target_url" => null, // works with "mini" embeds and "new_tab" => true
    "show_header" => false // works with "full" embeds
  );

  $atts = wp_parse_args($options, $default);

  $tag = Promo::getEmbedCode($atts);

  if (array_key_exists("success", $tag)) {
    return $tag["embed_code"];
  } else {
    return $tag["message"];
  }
});

/**
 * shortcode for a list of promos
 */
$api->add('promoListShortcode', function ($options) {
  $default = array(
    "size" => "6",            // [<integer>]                    0 defaults to all public promos
    "category" => "",         // [announcement | sale | rent]   empty defaults to all categories
    "columns" => "3",         // [<integer>]                    bounded between 1 and 6
    "layout" => "vertical", // [vertical | horizontal]
  );
  
  $atts = wp_parse_args($options, $default);
  
  // sanitize atts
  $atts["size"] = max(0, intval($atts["size"]));
  $atts["category"] = in_array($atts["category"], array("announcement", "sale", "rent")) ? $atts["category"] : $default["category"];
  $atts["columns"] = min(6, max(1, intval($atts["columns"])));
  $atts["layout"] = in_array($atts["layout"], array("vertical", "horizontal")) ? $atts["layout"] : $default["layout"];

  $posts = get_posts(array(
    'post_type' => Helper::get("promo_post_type"),
    'posts_per_page' => -1,
  ));

  $promos = array();
  
  foreach ($posts as $post) {
    if (!Promo::isPromoPrivate($post->ID)) {
      $promos[] = array(
        "layout" => $atts["layout"],
        "title" => $post->post_title,
        "image" => Promo::getPromoWPThumbnailUrl($post->ID),
        "url" => get_permalink($post->ID),
      );
      
      if ($atts["size"] !== 0 && count($promos) >= $atts["size"]) {
        break;
      }
    }
    
  }

  return herbert('twig')->render('@PromoSync/promo_list.twig', [
    "promos" => $promos,
    "columns" => $atts["columns"],
  ]);    
});

/**
 * shortcode for a list of promos
 */
$api->add('promoShortcode', function ($options) {
  $default = array(
    "id" => "0",            // [<integer>]                    0 defaults to nothing shown (id is required)
    "layout" => "vertical", // [vertical | horizontal]
    "max-width" => "400px", // [<css width value>]
    "align" => "center",    // [left | right | center]
  );
  
  $atts = wp_parse_args($options, $default);
  $html = "";  
  
  // sanitize atts
  $atts["id"] = max(0, intval($atts["id"]));
  $atts["layout"] = in_array($atts["layout"], array("vertical", "horizontal")) ? $atts["layout"] : $default["layout"];
  $atts["align"] = in_array($atts["align"], array("left", "right", "center")) ? $atts["align"] : $default["align"];

  if ($atts["id"]) {
    $post = get_post($atts["id"]);
    
    if (!$post) {
      // try to get it assuming the given id is a promoId
      $post = Promo::getPostByPromoId($atts["id"]);
    }
    
    $style = $atts["max-width"] ? "max-width: ".$atts["max-width"]."; " : "";
    
    if ($post && $post->post_type == Helper::get("promo_post_type")) {
      $promo = array(
        "layout" => $atts["layout"],
        "align" => $atts["align"],
        "inline_style" => $style,
        "title" => $post->post_title,
        "image" => Promo::getPromoWPThumbnailUrl($post->ID),
        "url" => get_permalink($post->ID),
      );

      $html = herbert('twig')->render('@PromoSync/promo.twig', $promo);    
    }
  }
  return $html;
});

/**
 * shortcode for a confirm newsletter
 */
$api->add('promoConfirmation', function ($options) {
  $confirmation_token = get_confirm_token();
  $confirmed = Promo::confirmNewsLetter();
  if ($confirmed && $confirmed['success']) {
    $email = $confirmed['email'];
    if ($confirmed['deleted']) {
      $html = herbert('twig')->render('@PromoSync/unsubscribe_newsletter.twig', ['email' => $email]);
    } else {
      $html = herbert('twig')->render('@PromoSync/confirm_newsletter.twig', ['email' => $email]);
    }
  } else {
    $html = herbert('twig')->render('@PromoSync/subscription_not_found.twig');
  }
  return $html;
});
