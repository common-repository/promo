<?php

/** @var  \Herbert\Framework\Application $container */

use PromoSync\Helper;
use PromoSync\Models\Promo;

function promo_custom_class ($classes) {
  $metakey = Helper::get('promo_meta_key');
  $promo_id = get_post_meta(get_the_ID(), $metakey, true);
  if ($promo_id) $classes[] = 'post-promo';
  return $classes;
}

add_filter('post_class', 'promo_custom_class');

function promo_custom_css () {
  echo '<style>
  .promo-embed-error {
    color: #ddd;
  }
  </style>';
}

add_action('wp_head', 'promo_custom_css');

function promo_append_embed_code ($content) {
  global $post;
  $options = array();
  $metakey = Helper::get('promo_meta_key');
  $promo_id = get_post_meta(get_the_ID(), $metakey, true);
  $options["type"] = (is_single() && $promo_id)?"big":"small";
  if ($promo_id) {
    $options["promo_id"] = $promo_id;
    $embed_code = Promo::getEmbedCode($options);
    if (array_key_exists("embed_code", $embed_code)) {
      $content = $embed_code["embed_code"];
    } else {
      $message = $embed_code["message"]?$embed_code["message"]:"Unknown error";
      $content = "<p class='promo-embed-error'>Unable to get promotion information (" . $message . ")</p>";
    }
  }
  return $content;
}

add_filter('the_content', 'promo_append_embed_code');
add_filter('the_excerpt', 'promo_append_embed_code');

function exclude_private_tag ($query) {
  $term = get_term_by('slug', 'promo-private', 'post_tag');
  if ($term && $query->is_home()) {
    $query->set('tag__not_in', array($term->term_id));
  }
}
add_action('pre_get_posts', 'exclude_private_tag');

function custom_promo_in_home_loop ($query) {
  $post_type = Helper::get("promo_post_type");
  if (is_home() && $query->is_main_query()) {
    $query->set('post_type', array(
      'post', $post_type
    ));
  }
  return $query;
}
//add_filter( 'pre_get_posts', 'custom_promo_in_home_loop' );


function namespace_add_custom_types( $query ) {
  if(is_tag()) {
  	$post_type = Helper::get("promo_post_type");
  	$types = $query->get('post_type');
  	if (is_array($types)) {
  		$types[] = $post_type;
  	} else {
  		$types = array('post', $post_type);
  	}
  	$query->set('post_type', $types);
	}
  return $query;
}
add_filter( 'pre_get_posts', 'namespace_add_custom_types' );

// TODO: Add metatags for facebook, twitter and pinterest
// https://www.elegantthemes.com/blog/tips-tricks/how-to-add-open-graph-tags-to-wordpress
function add_meta_tags () {
  global $post;
  if ($post) {
    
    $promotion = Promo::getPromoByPostId($post->ID);
    
    if (is_single() && !empty($promotion)) {
      if (!empty($promotion["promotionable"]["attributes"]["description"])) {
        $meta = strip_tags($promotion["promotionable"]["attributes"]["description"]);
      } else {
        $meta = strip_tags($promotion["promotionable"]["attributes"]["name"]);
      }
      $meta = strip_shortcodes($meta);
      $meta = str_replace(array("\n", "\r", "\t"), ' ', $meta);
      $meta = substr($meta, 0, 125);

      $title = strip_tags($promotion["promotionable"]["attributes"]["name"]);

      $createdAt = strip_tags($promotion["attributes"]["createdAt"]);
      $url = Promo::getPromoWordPressPath($promotion);

      $promotion_type = $promotion["promotionable"]["type"];



      // media
      $has_media = isset($promotion["promotionable"]["media"]);
      if ($has_media) {
        $is_media_video = $promotion["promotionable"]["media"]["type"]=="S3Video"?true:false;
        if ($is_media_video) {
          $media_video = $promotion["promotionable"]["media"]["attributes"]["url"];
          $media_thumbnail = $promotion["promotionable"]["media"]["thumbnail"]["attributes"]["url"];
        } else {
          $media_thumbnail = $promotion["promotionable"]["media"]["attributes"]["url"];
        }
      }

      // $media = strip_tags($promotion["createdAt"]);
      // add meta robots noindex if promotion disabled search engine index
      if (!$promotion['attributes']['searchEngineCrawlable']) {
        echo '<meta name="robots" content="noindex, nofollow"/>' . "\n";
      }

      // Facebook og metatags

      echo '<meta name="description" content="' . $meta . '" />' . "\n";
      echo '<meta property="og:url" content="' . $url . '" />' . "\n";
      echo '<meta property="og:type" content="product" />' . "\n";
      echo '<meta property="og:title" content="' . $title . '" />' . "\n";
      echo '<meta property="og:description" content="' . $meta . '" />' . "\n";
      echo '<meta property="article:published" content="' . $createdAt . '" />' . "\n";
      if ($has_media) {
        if ($is_media_video) {
          echo '<meta property="og:image" content="' . $media_video . '" />' . "\n";
        } else {
          echo '<meta property="og:image" content="' . $media_thumbnail . '" />' . "\n";
        }
      }

      // Twitter metatags

      echo '<meta name="twitter:site" content="@UsePromo" />' . "\n";
      echo '<meta name="twitter:title" content="' . $title . '" />' . "\n";
      echo '<meta name="twitter:description" content="' . $meta . '" />' . "\n";
      
      if ($promotion_type == "Announcement" || $promotion_type == "Rental") {
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:image:src" content="' . $media_thumbnail . '" />' . "\n";
      } else {
        echo '<meta name="twitter:card" content="product" />' . "\n";
        echo '<meta name="twitter:domain" content="' . $url . '" />' . "\n";
        echo '<meta name="twitter:image" content="' . $media_thumbnail . '" />' . "\n";
      }
      
      if (Promo::isPromoPrivate($post->ID)) {
      	echo '<meta name="robots" content="noindex" />'."\n";
      }
    }
  }
}
add_action('wp_head', 'add_meta_tags' , 2);