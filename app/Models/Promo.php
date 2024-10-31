<?php namespace PromoSync\Models;

use PromoSync\Helper;

class Promo {

  /*
   * Returns Promo API base url
   */
  public static function getApiUrl ($path, $args = null) {
    if ($path) {
      if (is_array($args)) {
        foreach ($args as $key => $value) {
          $path = str_replace('{' . $key . '}', $value, $path);
        }
      }
    }
    
    if (file_exists(Helper::path('api.config.php'))) {
      $apiurl = @require Helper::path('api.config.php');
    }

    if (empty($apiurl)) {
      $apiurl = Helper::get('promoApiBase');
    }

    if ($path) $apiurl = $apiurl . $path;
    return $apiurl;
  }

  /*
   * Returns url for webhook where Promo sends requests to
   */
  public static function getWebhookUrl () {
    $token = get_option(self::getTokenField());
    $webhooktoken = md5($token);
    $params = array("promo_webhook" => $webhooktoken);
    $url_path = "/index.php?" . http_build_query($params);
    return get_site_url() . $url_path;
  }

  public static function getWebhookToken () {
    return get_option(Helper::get('promo_webhook_token'));
  }

  /*
   * Gets domain name where Wordpress website is hosted
   * i.e.: example.com
   */
  public static function getDomainName () {
    // TODO: Test this function to make sure that it work on more complex case scenarios
    return preg_replace('#^https?://#', '', get_site_url());
  }

  /*
   * Returns string with the Wordpress path in promotion
   */
  public static function getPromoWordPressPath ($promotion) {
    return get_site_url() . $promotion["attributes"]["wordpressPath"];
  }
  
  /*
   * Returns string URL of the promotion thumbnail
   */
  public static function getPromoThumbnailUrl ($promotion) {
    $media = $promotion["promotionable"]["media"];
    if ($media["type"] == "S3Video") {
      $media = $media["thumbnail"];
    }
    return $media["attributes"]["url"];
  }
  
  public static function getPromoWPThumbnailUrl($post_id) {
    return self::getPromoWPAttribute($post_id, 'image_url');
  }

  public static function getPromoWPVisibility($post_id) {
    return self::getPromoWPAttribute($post_id, 'visibility');
  }
  
  public static function isPromoPrivate($post_id) {
    return self::getPromoWPVisibility($post_id) == 'private';
  }
  
  public static function getPromoWPAttribute($post_id, $attribute) {
    return get_post_meta($post_id, Helper::get('promo_meta_prefix').$attribute, true);
  }
  
  public static function filterPostThumbnailHtml ($html, $post_id) {
    $url = self::getPromoWPThumbnailUrl($post_id);
    if ($url) {
      return '<img src="'.$url.'"/>';
    }
    return $html;
  }
  
  /*
   * Returns the url path of a promo post
   * i.e.: /best-promo-ever
   */
  public static function getPostUrlPath ($post_id) {
    $permalink = get_permalink($post_id);
    $url_path = str_replace(get_site_url(), "", $permalink);
    return $url_path;
  }

  /*
   * Returns a WP post given a promo id
   */
  public static function getPostByPromoId ($promotion_id) {
    $promo_meta_key = Helper::get('promo_meta_key');
    $promo_post_type = Helper::get('promo_post_type');
    $args = array(
      "post_type" => $promo_post_type,
      "meta_query" => array(
        array(
          "key" => $promo_meta_key,
          "value" => $promotion_id
        )
      )
    );

    $posts_array = get_posts($args);
    
    if (!empty($posts_array)) {
      return $posts_array[0];
    } else {
      return array();
    }
  }

  /*
   * Returns the promo associated with a post id
   */
  public static function getPromoByPostId ($post_id) {
    $promo = array();
    $promo_id = get_post_meta($post_id, Helper::get('promo_meta_key'), true);
    if ($promo_id) {
      $promotion_resp = self::getPromotion($promo_id);
      if (array_key_exists("success", $promotion_resp)) {
        $promo = $promotion_resp["promotion"];
      }
    }
    return $promo;
  }

  /*
   * Determines if the promotion is in draft status
   */
  public static function isDraft ($promotion) {
    return $promotion["attributes"]["status"] === "draft";
  }

  /*
   * v2/embed_code
   * :promotion_id,
   * :business_id,
   * :show_header_footer,
   * :target_url,
   * :new_tab,
   * :full
   */
  public static function getEmbedCode ($options) {

    if (!isset($options["type"])) $options["type"] = "big";
    $business = self::getBusiness();
    $permanentRootUrl = $business["attributes"]["permanentRootUrl"];

    $promo = self::getPromotion($options["promo_id"]);
    $promo_slug = $promo["promotion"]["attributes"]["slug"];

    if ($promo['promotion']['attributes']['status'] == 'closed') {
      $post = self::getConfirmPage();
      if ($post)
        $pageId = $post->ID;

      return array("embed_code" => '<script type="text/javascript" src="https://asset.promo.co/embed.js?businessPermanentRootUrl=' . $permanentRootUrl . '&promotionSlug=' . $promo_slug . '&type=' . $options["type"] . '&mode=wordpress&wordpressPageId='.$pageId.'"></script>');
    } else {
      return array("embed_code" => '<script type="text/javascript" src="https://asset.promo.co/embed.js?businessPermanentRootUrl=' . $permanentRootUrl . '&promotionSlug=' . $promo_slug . '&type=' . $options["type"] . '&mode=wordpress"></script>');
    }
  }

  /*
   * Returns a string with the embed code for a promo
   */
  public static function getEmbedShortcode ($promotion_id) {
    return '[PromoEmbed promo_id=' . $promotion_id . ']';
  }

  /*
   * Returns the field name under which token value is stored
   */
  public static function getTokenField () {
    return Helper::get('db_prefix') . 'token';
  }
  
  /*
   * Returns the field name under which token type is stored
   */
  public static function getTokenTypeField () {
    return Helper::get('db_prefix') . 'token_type';
  }
  
  /*
   * Returns true if we should ignore the create listing view. 
   * Pass true|false to set this
   * Pass nothing to get it
   */
  public static function ignoreCreateListing ($b=null) {
    $key = Helper::get('db_prefix') . 'ignore_listing';
    if (is_bool($b)) {
      update_option($key, $b !== false ? 'yes' : false);
    }
    return get_option($key);
  }
  
  /*
   * Returns the value of the token saved in WP
   */
  public static function getToken () {
    return get_option(self::getTokenField());
  }

  /*
   * Returns the token type saved in WP
   */
  public static function getTokenType () {
    return get_option(self::getTokenTypeField());
  }

  /*
   * Determines if there’s a saved token
   */
  public static function hasToken () {
    $token = get_option(self::getTokenField());
    return $token?true:false;
  }

  /*
   * Stores the token and token type in WP
   */
  public static function saveToken ($token, $token_type) {
    update_option(self::getTokenField(), $token);
    update_option(self::getTokenTypeField(), $token_type);
    return array("success" => true);
  }

  /*
   * Expires the token by setting the values saved in WP to false
   */
  public static function expireToken () {
    update_option(self::getTokenField(), false);
    update_option(self::getTokenTypeField(), false);
  }

  /*
   * Returns the business field for storing in WP
   */
  public static function getBusinessField () {
    return Helper::get('db_prefix') . 'business';
  }

  /*
   * Determines if there’s a business saved
   * TODO: Check against the API if it's a valid business
   */
  public static function hasBusiness () {
    $business = get_option(self::getBusinessField());
    return $business?true:false;
  }

  /*
   * Returns the business data saved in WP
   */
  public static function getBusiness () {
    return get_option(self::getBusinessField());
  }

  /*
   * Checks if business is valid and stores the business data in WP
   */
  public static function saveBusiness ($business_id) {
    // Checking if business id is valid for the authorized account
    $businesses = self::getBusinesses();
    
    if (array_key_exists("success", $businesses)) {
      foreach ($businesses["businesses"] as $business) {
        if ($business["id"] == $business_id) {
          update_option(self::getBusinessField(), $business);
          return array("success" => true, "business" => $business);
        }
      }
    }

    return array(
      "error" => true,
      "message" => "Couldn’t verify your business. Please try again."
    );
  }

  /*
   * Removes business data stored in WP
   */
  public static function removeBusiness () {
    update_option(self::getBusinessField(), null);
  }

  /*
   * GET http://{{apiBase}}/v3/businesses
   * Gets businesses associated with the user
   */
  public static function getBusinesses () {
    $businessApi = self::getApiUrl(Helper::get('promoApiEndpoints')['businesses']);
    $response = self::reqAuth("get", $businessApi, []);
    if (!is_wp_error($response)) {
      $response_body = json_decode($response["body"], true);
      if ($response["response"]["code"] === 200) {
        $businesses = self::formatArrayResponse($response_body);
        $output = array(
          "success" => true,
          "businesses" => $businesses
        );
        $output["business"]["id"] = !empty($response_body["data"]) ? $response_body["data"][0]["id"] : 0;
      } else {
        $output = array(
          "error" => true,
          "message" => $response_body["error"]["message"]
        );
      }
    } else {
      $output = array(
        "error" => true,
        "message" => $response->get_error_message()
      );
    }
    return $output;
  }

  /**
   * TODO: Gets business stored in WP
   */
  public static function getBusiness_deprecated () {
    $businessApi = self::getApiUrl(Helper::get('promoApiEndpoints')['businesses']);
    $response = self::reqAuth("get", $businessApi, []);
    if (!is_wp_error($response)) {
      $response_body = json_decode($response["body"], true);
      if ($response["response"]["code"] === 200) {
        $businesses = self::formatArrayResponse($response_body);
        $output = array(
          "success" => true,
          "business" => $businesses[0]
        );
        $output["business"]["id"] = $response_body["data"][0]["id"];
      } else {
        $output = array(
          "error" => true,
          "message" => $response_body["error"]["message"]
        );
      }
    } else {
      $output = array(
        "error" => true,
        "message" => $response->get_error_message()
      );
    }
    return $output;
  }

  /**
   * POST http://{{apiBase}}/v3/access_token
   *
   * { 
   *   "grant_type" => "password",
   *   "username" => $email,
   *   "password" => $password
   * }
   *
   * Returns Promo authorization token
   */
  public static function signIn ($email, $password) {
    $output = null;
    $signApi = self::getApiUrl(Helper::get('promoApiEndpoints')['sign_in']);
    $data = array(
      "grant_type" => "password",
      "username" => $email,
      "password" => $password
    );

    $response = self::req("post", $signApi, $data);
    if (!is_wp_error($response)) {
      $response_body = json_decode($response["body"], true);
      if ($response["response"]["code"] === 201) {
        $output = array(
          "success" => true,
          "token" => $response_body["access_token"],
          "token_type" => $response_body["token_type"]
        );
      } else {
        if (isset($response_body["error"])) {
          $message = $response_body["error_description"];
        }
        if (empty($message)) {
          $message = $response["response"]["code"] . " - " . $response["response"]["message"];
        }
        $output = array(
          "error" => true,
          "message" => $message
        );
      }
    } else {
      $output = array(
        "error" => true,
        "message" => $response->get_error_message()
      );
    }
    return $output;
  }

  /*
   * Gets WordPress Account from API
   */
  public static function getWordpressAccount () {
    $business = self::getBusiness();
    $business_id = $business["id"];
    $wpAccApi = self::getApiUrl(Helper::get('promoApiEndpoints')['get_wordpress_account'], array("business_id" => $business_id));
    $response = self::reqAuth("get", $wpAccApi, []);

    if (!is_wp_error($response)) {
      $response_body = json_decode($response["body"], true);
      $output = array(
        "success" => true,
        "wordpress_account" => $response_body["data"],
        "business_id" => $business_id
      );
    } else {
      $output = array(
        "error" => true,
        "message" => $response->get_error_message()
      );
    }
    return $output;
  }

  /**
   * PATCH http://{{apiBase}}/v3/wordpress_account
   */
  public static function updateWordpressAccount () {
    $wpAcc = self::getWordpressAccount();
    if (array_key_exists("success", $wpAcc)) {
      $wpAccId = $wpAcc["wordpress_account"]["id"];
      $wpAccApi = self::getApiUrl(Helper::get('promoApiEndpoints')['wordpress_account'], array("wordpress_id" => $wpAccId));
      $confirmPage = self::getConfirmPage();
      $data = array(
        "data" => array(
          "type" => "WordpressAccount",
          "id" => $wpAccId,
          "attributes" => array(
            "rootUrl" => self::getDomainName(),
            "webhookUrl" => self::getWebhookUrl(),
            "newsletterConfirmPageId" => $confirmPage ? $confirmPage->ID : ''
          )
        )
      );
      $response = self::reqAuth("patch", $wpAccApi, $data);
      if (!is_wp_error($response)) {
        $response_body = json_decode($response["body"], true);

        if ($response["response"]["code"] === 200) {
          $output = array(
            "success" => true,
            "wordpress_account" => $response_body
          );
        } else {
          $output = array(
            "error" => true,
            "message" => $response_body["error"]
          );
        }
      } else {
        $output = array(
          "error" => true,
          "message" => $response->get_error_message()
        );
      }
    } else {
      $output = array(
        "error" => true,
        "message" => $wpAcc["message"]
      );
    }
    return $output;
  }
  
  // TODO refactor to reuse code above
  public static function disconnectWordpressAccount() {
    $wpAcc = self::getWordpressAccount();
    if (array_key_exists("success", $wpAcc)) {
      $wpAccId = $wpAcc["wordpress_account"]["id"];
      $wpAccApi = self::getApiUrl(Helper::get('promoApiEndpoints')['wordpress_account'], array("wordpress_id" => $wpAccId));
      $data = array(
        "data" => array(
          "type" => "WordpressAccount",
          "id" => $wpAccId,
          "attributes" => array(
            "rootUrl" => '',
            "webhookUrl" => ''
          )
        )
      );
      $response = self::reqAuth("delete", $wpAccApi, $data);
      // echo '<pre>'.print_r($response, true).'</pre>';
      // exit;
      if (!is_wp_error($response)) {
        $response_body = json_decode($response["body"], true);
        if ($response["response"]["code"] === 200) {
          $output = array(
            "success" => true,
            "wordpress_account" => $response_body
          );
        } else {
          $output = array(
            "error" => true,
            "message" => $response_body["error"]["message"]
          );
        }
      } else {
        $output = array(
          "error" => true,
          "message" => $response->get_error_message()
        );
      }
    } else {
      $output = array(
        "error" => true,
        "message" => $wpAcc["message"]
      );
    }
    return $output;    
  }
  
  public static function getWordpressAccountError($ignoreEmptyApiUrl=false) {
    $error = array();
    $wpa = Promo::getWordpressAccount();
    $biz = Promo::getBusiness();
    
    $thisUrl = self::getDomainName();
    $apiUrl = $wpa['wordpress_account']['attributes']['rootUrl'];
    $apiUrlNoProtocol = str_replace(array('https://','http://'), '', $apiUrl);
    $defaultUrl = $biz['attributes']['primaryRootUrl'];
    $defaultUrlNoProtocol = str_replace(array('https://','http://'), '', $defaultUrl);

    $button = '<form method="post" action="%s" style="display: inline-block;"><input type="submit" name="submit" class="button button-%s" value="%s" /></form> ';

    $possibleErrors = array(
      'empty' => array(
        'subheading' => 'This Wordpress installation has been unlinked from the Promo App.',
        'messages' => array(
          'This means that by default your Promos are being shared to <a target="_blank" href="'.$defaultUrl.'">'.$defaultUrlNoProtocol.'</a>.',
          'Would you like to sync your Promo account and all future promotions to this Wordpress installation instead?',
          sprintf($button, panel_url('PromoSync::mainPanel', array('action'=>'saveandsync')), 'primary', 'YES, Synchronize All My Promos').
          sprintf($button, panel_url('PromoSync::mainPanel', array('action'=>'deauthorize')), 'secondary', 'No, Logout')
        ),
      ),
      'conflict' => array(
        'subheading' => 'This Promo Business is linked to a different Wordpress account.',
        'messages' => array(
          'Your Promos are currently being synced and shared with <a target="_blank" href="'.$apiUrl.'">'.$apiUrlNoProtocol.'</a>.',
          'Would you like disconnect the other Wordpress account and sync your Promo account and all future promotions to this Wordpress installation instead?',
          sprintf($button, panel_url('PromoSync::mainPanel', array('action'=>'saveandsync')), 'primary', 'YES, Make This Website My Default').
          sprintf($button, panel_url('PromoSync::mainPanel', array('action'=>'deauthorize')), 'secondary', 'No, Logout')
        ),
      )
    );

    if (empty($apiUrl) && !$ignoreEmptyApiUrl) {
      $error = $possibleErrors['empty'];
    } else if ($thisUrl != $apiUrlNoProtocol && !empty($apiUrlNoProtocol)) {
      $error = $possibleErrors['conflict'];
    }
    
    return $error;
  }

  public static function findInclude ($included, $id, $type) {
    foreach ($included as $include) {
      if ($include["type"] == $type && $include["id"] == $id) {
        return $include;
      }
    }
    return null;
  }

  /*
   * Returns formated included
   */
  public static function _formatIncluded ($included) {
    foreach ($included as $i => $include) {
      if (isset($include["relationships"])) {
        foreach ($include["relationships"] as $key => $rel) {
          // Saves space by not adding product array to ShippingCountry array
          if ($include["type"] == "ShippingCountry" && $key == "product") continue;
          
          $rel_id = null; $rel_type = null;
          
          if (isset($rel["data"]["id"])) $rel_id = $rel["data"]["id"];
          if (isset($rel["data"]["type"])) $rel_type = $rel["data"]["type"];
          if ($rel_id && $rel_type) {
            $included[$i][$key] = self::findInclude($included, $rel_id, $rel_type);
          } else if ($include["type"] == "S3Video" && $key == "versions") {
            $rel_id = null; $rel_type = null;
            if (isset($rel["data"]) &&  count($rel["data"])) {
              $rel_id = $rel["data"][0]["id"];
              $rel_type = $rel["data"][0]["type"];
            }
            $included[$i]["thumbnail"] = self::findInclude($included, $rel_id, $rel_type);
          } else if ($include["type"] == "S3Image" && $key == "original") {
            $rel_id = null; $rel_type = null;
            if (isset($rel["data"]) && count($rel["data"])) {
              $rel_id = $rel["data"]["id"];
              $rel_type = $rel["data"]["type"];
            }
            $included[$i]["original"] = self::findInclude($included, $rel_id, $rel_type);
          } else if ($key == "shippingCountries") {
            $rel_id = null; $rel_type = null;
            $included[$i][$key] = array();
            if (is_array($rel["data"]) && count($rel["data"])) {
              foreach($rel["data"] as $country) {
                $rel_id = $country["id"];
                $rel_type = $country["type"];
                $included[$i][$key][] = self::findInclude($included, $rel_id, $rel_type);
              }
            }
          }
        }
      }
    }
    return $included;
  }

  public static function formatIncluded ($included) {
    // Runs format included twice so it uses the compound includes
    $f_included = self::_formatIncluded($included);
    $f_included = self::_formatIncluded($f_included);
    return $f_included;
  }

  public static function formatRelationshipData ($promotion_data, $f_included) {
    if (!isset($promotion_data["relationships"])) return;
    foreach ($promotion_data["relationships"] as $key => $rel) {
      $rel_id = $rel["data"]["id"];
      $rel_type = $rel["data"]["type"];
      $promotion_data[$key] = self::findInclude($f_included, $rel_id, $rel_type);
    }
    return $promotion_data;
  }

  public static function formatSingleResponse ($response) {
    $f_included = array();
    if (isset($response["included"])) {
      $f_included = self::formatIncluded($response["included"]);
    }
    $data = self::formatRelationshipData($response["data"], $f_included);
    return $data;
  }

  public static function formatArrayResponse ($response) {
    $data = $response["data"];
    $f_included = array();
    if (isset($response["included"])) {
      $f_included = self::formatIncluded($response["included"]);
    }
    foreach ($response["data"] as $key => $business) {
      $data[$key] = self::formatRelationshipData($business, $f_included);
    }
    return $data;
  }

  /**
   * GET http://{{apiBase}}/v3/businesses/{{business_id}}/promotions
   */
  public static function getPromotions ($business_id, $inSync = false, $status = "live", $withStatusLabel=false) {
    $promotionsApi = self::getApiUrl(Helper::get('promoApiEndpoints')['promotions'], array("business_id" => $business_id));
    $response = self::reqAuth("get", $promotionsApi, array(
      "status" => $status,
      "include" => "promotionable"
    ));
    if (!is_wp_error($response)) {
      $response_body = json_decode($response["body"], true);
      if ($response["response"]["code"] === 200) {
        $promotions = self::formatArrayResponse($response_body);
        $output["promotions"] = $promotions;
        $output["success"] = true;
        if ($inSync || $withStatusLabel) {
          foreach ($output["promotions"] as $key => $promotion) {
            if ($inSync) {
              $output["promotions"][$key]["synced"] = self::promotionSynced($promotion);
            }
            
            if ($withStatusLabel) {
              $status = $output["promotions"][$key]["attributes"]["status"];
              $vis = $output["promotions"][$key]["attributes"]["visibility"];
              $label = ucwords($status);
              if ($vis != 'public') {
                $label .= ' (Private)';
              }

              $promoAtts = $output["promotions"][$key]["promotionable"]["attributes"];
              if (isset($promoAtts["stockLevel"]) && isset($promoAtts["ordersCount"])) {
                $stock = $promoAtts["stockLevel"];
                $orders = $promoAtts["ordersCount"];
                
                if ($orders >= $stock) {
                  $label .= ', Sold Out';
                }
              }

              
              $output["promotions"][$key]["attributes"]["statusLabel"] = $label;
            }
            // echo '<pre>'.var_export($output["promotions"][$key], true).'</pre>';
            // echo '<pre>'.var_export($output["promotions"][$key]["synced"], true).'</pre>';
          }
        }
      } else {
        $output = array(
          "error" => true,
          "message" => $response_body["error"]["message"]
        );
      }
    } else {
      $output = array(
        "error" => true,
        "message" => $response->get_error_message()
      );
    }
    return $output;
  }

  /**
   * GET http://{{apiBase}}/v3/promotions/{{promotionId}}
   */
  public static function getPromotion ($promotion_id, $inSync = false) {
    $promotionApi = self::getApiUrl(Helper::get('promoApiEndpoints')['promotion'], array(
      "promo_id" => $promotion_id
    ));
    $response = self::reqAuth("get", $promotionApi, []);
    if (!is_wp_error($response)) {
      $response_body = json_decode($response["body"], true);
      if ($response["response"]["code"] === 200) {
        $promotion = self::formatSingleResponse($response_body);
        if ($inSync) {
          $promotion["synced"] = self::promotionSynced($promotion);
        }
        $output = array(
          "success" => true,
          "promotion" => $promotion
        );
      } else {
        $output = array(
          "error" => true,
          "message" => $response_body["error"]
        );
      }
    } else {
      $output = array(
        "error" => true,
        "message" => $response->get_error_message()
      );
    }

    return $output;
  }

  /**
   * PATCH http://{{apiBase}}/v3/promotions/{{promotionId}}
   */
  public static function updatePromotionUrl ($promotion_id, $url_path) {

    $promotionApi = self::getApiUrl(Helper::get('promoApiEndpoints')['promotion'], array(
      "promo_id" => $promotion_id
    ));
    $response = self::reqAuth("PATCH", $promotionApi, array(
      "data" => array(
        "type" => "Promotion",
        "id" => $promotion_id,
        "attributes" => array(
          "wordpressPath" => $url_path
        )
      )
    ));

    if (!is_wp_error($response)) {
      $response_body = json_decode($response["body"], true);
      if ($response["response"]["code"] === 200) {
        $output = array(
          "success" => true,
          "promotion" => $response_body
        );
      } else {
        $output = array(
          "error" => true,
          "message" => $response_body["error"]["message"]
        );
      }
    } else {
      $output = array(
        "error" => true,
        "message" => $response->get_error_message()
      );
    }
    return $output;
  }

  public static function updatePostAttributes ($promotion, $post_id) {
    $prefix = Helper::get('promo_meta_prefix');
    $url = self::getPromoThumbnailUrl($promotion);
    $visibility = $promotion["attributes"]["visibility"];
    update_post_meta($post_id, $prefix.'image_url', $url);
    update_post_meta($post_id, $prefix.'visibility', $visibility);
  }

  /*
   * Updates tags of the promo WP post
   */
  public static function updatePostTags ($promotion, $post_id) {
    $tags = array("promo");
    if (array_key_exists("status", $promotion) && $promotion["attributes"]["visibility"] !== "public") {
      $tags[] = "promo-private";
    }
    if (array_key_exists("status", $promotion)) {
      $tags[] = "promo-" . $promotion["attributes"]["status"];
    }
    if (array_key_exists("promotionable", $promotion)) {
      $tags[] = "promo-" . sanitize_title($promotion["promotionable"]["type"]);
    }
    wp_set_post_tags($post_id, $tags, true);
  }

  /*
   * Creates a WP promo post from a promotion
   */
  public static function createPromotionPost ($promotion) {
    $post_type = Helper::get("promo_post_type");
    // Creates a new post based on the passed promotion
    $post_result = wp_insert_post(array(
      "post_type" => $post_type,
      "post_title" => $promotion["promotionable"]["attributes"]["name"],
      "post_content" => !empty($promotion["promotionable"]["attributes"]["description"]) ? $promotion["promotionable"]["attributes"]["description"] : '',
      "post_date" => $promotion["attributes"]["createdAt"],
      "post_status" => "publish"
    ), true);

    if (!is_wp_error($post_result)) {
      $post_id = $post_result;

      // Update post tags depending on promotion
      // TODO: Validate response
      self::updatePostTags($promotion, $post_id);
      
      self::updatePostAttributes($promotion, $post_id);
      
      // Associates the newly created post with the promotion
      $promo_meta_key = Helper::get("promo_meta_key");
      $result = update_post_meta($post_id, $promo_meta_key, $promotion["id"]);
      
      if ($result) {
        
        // Updates the promo wordpress_path attribute
        $url_path = self::getPostUrlPath($post_id);
        $url_resp = self::updatePromotionUrl($promotion["id"], $url_path);

        if (array_key_exists("success", $url_resp)) {
          return array(
            "success" => true,
            "message" => "Post created successfully and linked to Promo " . $promotion["id"],
            "promotion" => $promotion,
            "post_id" => $post_id,
            "action" => "create"
          );
        } else {
          return $url_resp;
        }
      } else {
        return array(
          "error" => true,
          "message" => "Post " . $post_id . " failed to be associated with Promo id"
        );
      }
    } else {
      return array(
        "error" => true,
        "message" => $post_result->get_error_message()
      );
    }
  }

  /*
   * Updates a promo WP post
   */
  public static function updatePromotionPost ($post, $promotion) {
    // Update existing post
    $result = wp_update_post(array(
      'ID' => $post->ID,
      'post_title' => $promotion["promotionable"]["attributes"]["name"],
      'post_date' => $promotion["attributes"]["createdAt"],
      'post_content' => !empty($promotion["promotionable"]["attributes"]["description"]) ? $promotion["promotionable"]["attributes"]["description"] : '',
      "post_status" => "publish"
    ), true);

    if (!is_wp_error($result)) {

      $success = array(
        "success" => true,
        "message" => "Promo " . $promotion["promotionable"]["attributes"]["name"] . " (" . $promotion["id"] . ") updated successfully",
        "promotion" => $promotion,
        "post_id" => $result,
        "action" => "update"
      );

      // Update post tags depending on promotion
      // TODO: Validate response
      self::updatePostTags($promotion, $post->ID);
      
      self::updatePostAttributes($promotion, $post->ID);      

      // Checks to see if wordpress_path attribute needs to be updated
      $url_path = self::getPostUrlPath($post->ID);
      $wpurlpath = self::getPromoWordPressPath($promotion);
      if ($url_path !== $wpurlpath) {
        // Updates the wordpress_path attribute for the promotion
        $url_resp = self::updatePromotionUrl($promotion["id"], $url_path);
        if (array_key_exists("success", $url_resp)) {
          return $success;
        } else {
          return $url_resp;
        }
      } else {
        return $success;
      }
    } else {
      return array(
        "error" => true,
        "message" => $result->get_error_message()
      );
    }
  }

  public static function removePromotionPost ($promo_post) {
    wp_delete_post($promo_post->ID, true);
  }

  public static function removeAllPromotionPosts () {
    $post_type = Helper::get("promo_post_type");
    $args = array("posts_per_page" => -1, "post_type" => $post_type);
    $posts_array = get_posts($args);
    foreach ($posts_array as $promo_post) {
      self::removePromotionPost($promo_post);
    }
    unregister_post_type($post_type);
    self::registerCustomPostType();
    return array(
      "success" => true,
      "message" => "Promos data removed"
    );
  }

  /*
   * Syncs (updates or creates) the promotion with WP
   */
  public static function syncPromotion ($promotion) {
    // If promo isn't public yet, don't bother checking anything
    if (self::isDraft($promotion)) return array( "skip" => true );

    // Check if promo is in sync with any wp post
    $sync = self::promotionSynced($promotion);

    if (!$sync["all"]) {
      $promotion_id = $promotion["id"];
      $promo_post = self::getPostByPromoId($promotion_id);

      if (!empty($promo_post)) {
        // Updating existing post
        return self::updatePromotionPost($promo_post, $promotion);
      } else {
        // Create new post
        return self::createPromotionPost($promotion);
      }
    } else {
      return array(
        "success" => true,
        "message" => "Promotion post already created and launched. (Post_id: " . $sync["post"] . ")",
        "action" => "skip"
      );
    }
  }

  /*
   * Determines if the promotion has been synced
   */
  public static function promotionSynced ($promotion) {
    $sync = array(
      "post" => false,
      "title" => false,
      "url" => false,
      "image" => false,
      "visibility" => false,
      "all" => false
    );

    $promotion_id = $promotion["id"];
    $promo_post = self::getPostByPromoId($promotion_id);
    $wp_domain = self::getDomainName();
    
    if (!empty($promo_post)) {
      $url = self::getPromoWordPressPath($promotion);
      $check_title = $promo_post->post_title === trim($promotion["promotionable"]["attributes"]["name"]);
      $check_url = get_permalink($promo_post->ID) === $url;
      $check_image = self::getPromoWPThumbnailUrl($promo_post->ID) === self::getPromoThumbnailUrl($promotion);
      $check_visibility = self::getPromoWPVisibility($promo_post->ID) === $promotion["attributes"]["visibility"];

      $sync["post"] = $promo_post->ID;
      $sync["title"] = $check_title;
      $sync["url"] = $check_url;
      $sync["image"] = $check_image;
      $sync["visibility"] = $check_visibility;
      $sync["all"] = $check_title && $check_url && $check_image && $check_visibility;
    }
    return $sync;
  }

  /*
   * Makes a public request to the API
   */
  private static function req ($method, $url, $data=[], $opts=[]) {
    $method = strtoupper($method);
    $args = [
      'timeout' => 15,
      'headers' => [
        'Content-type' => 'application/json',
        'Accept' => 'application/json'
      ]
    ];
    if (!empty($opts)) {
      foreach ($opts as $key => $value) {
        $args['headers'][$key] = $value;
      }
    }
    if ($method == "GET") {
      if (is_array($data)) {
        $url .= "?" . http_build_query($data);
      } else if (is_string($data)) {
        $url .= "?" . str_replace("/^\?/", "", $data);
      }
      return wp_remote_get($url, $args);
    }
    if ($method == "POST" || $method == "PUT" || $method == "PATCH" || $method == 'DELETE') {
      $body = json_encode($data);
      $args['body'] = $body;
      $args['method'] = $method;
      if (!empty($opts)) {
        foreach ($opts as $key => $value) {
          $args['headers'][$key] = $value;
        }
      }
      return wp_remote_post($url, $args);
    }
  }

  /*
   * Make an authorized request to the API with the saved token
   */
  private static function reqAuth ($method, $url, $data, $opts=[]) {
    $token = self::getToken();
    $token_type = self::getTokenType();
    $opts = array('Authorization' => $token_type . ' ' . $token);
    return self::req($method, $url, $data, $opts);
  }

  /*
   * Registers the custom post type for promos
   */
  public static function registerCustomPostType () {
    $post_type = Helper::get("promo_post_type");
    $result = register_post_type($post_type, array(
      'labels' => array(
        'name' => __( 'Promos' ),
        'singular_name' => __( 'Promo' )
      ),
      'public' => true,
      'show_ui' => false,
      'menu_position' => 5,
      'menu_icon' => Helper::assetUrl('/img/icon.ico'),
      'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
      'taxonomies' => array('post_tag'),
      'has_archive' => false,
      //'rewrite' => true
    ));
  }
  
  public static function getInstalledPromoArchivePage () {
    $prefix = Helper::get('promo_meta_private_prefix');
    $args = array(
      "post_type" => 'page',
      "post_status" => 'any',
      "meta_query" => array(
        array(
          "key" => $prefix.'archive_page',
          "value" => 'autogen'
        )
      )
    );

    $posts = get_posts($args);
    return !empty($posts) ? $posts[0] : null;
  }
  
  public static function createPromoArchivePage () {
    $post_id = wp_insert_post(array(
      'post_title' => 'Promos',
      'post_content' => '[promolist size="0"]',
      'post_type' => 'page',
      'post_status' => 'publish',
    ));

    $prefix = Helper::get('promo_meta_private_prefix');
    update_post_meta($post_id, $prefix.'archive_page', 'autogen');
  }

  public static function createConfirmNewsLetterPage () {
    $post = self::getConfirmPage();
    if (!$post) {
      $post_id = wp_insert_post(array(
        'post_title' => 'Promo Newsletter Confirmation',
        'post_content' => '[confirmnewsletter token="0"]',
        'post_type' => 'page',
        'post_status' => 'publish',
      ));

      $prefix = Helper::get('promo_meta_private_prefix');
      update_post_meta($post_id, $prefix.'confirm_page', 'autogen');      
    }
  }

  public static function removeConfirmNewsLetterPage () {
    $post = self::getConfirmPage();
    if ($post)
      wp_delete_post($post->ID, true);
  }

  public static function getConfirmPage() {
    $prefix = Helper::get('promo_meta_private_prefix');
    $args = array(
      "post_type" => 'page',
      "post_status" => 'any',
      "meta_query" => array(
        array(
          "key" => $prefix.'confirm_page',
          "value" => 'autogen'
        )
      )
    );
    $posts = get_posts($args);
    return !empty($posts) ? $posts[0] : null; 
  }

  public static function confirmNewsLetter() {
    $confirm_token = get_confirm_token();
    $act = get_confirm_type();

    if ($confirm_token && $act === 'unsubscribe') {
      $output = self::unsubscribeNewsletter();
    } else {
      $output = self::confirmSubscription();      
    }    
    return $output;
  }

  public static function unsubscribeNewsletter() {
    $token = get_confirm_token();
    $subscriptionApi = self::getApiUrl(Helper::get('promoApiEndpoints')['confirm_newsletter'], array('confirm_token' => $token));
    $response = self::req("get", $subscriptionApi);
    if (!is_wp_error($response)) {
      $subscriber = json_decode($response["body"], true);
      if ($response['response']['code'] === 200) {
        $email = $subscriber['data']["attributes"]["email"];
        $resp = self::req('delete', $subscriptionApi);
        $delete_subscriber = json_decode($response["body"], true);
        if ($resp['response']['code'] === 204) {
          $output = array(
            "success" => true,
            "email" => $email,
            "deleted" => true
          );
        } else {
          $output = array(
            "error" => true,
            "message" => 'Something when wrong when unsubscribe newsletter.'
          );  
        }
      } else {
        $output = array(
          "error" => true,
          "message" => 'Token not found'
        );
      }
    } else {
      $output = array(
        "error" => true,
        "message" => $response->get_error_message()
      );
    }
    return $output;
  }

  public static function confirmSubscription() {
    $confirm_token = get_confirm_token();
    $confirmApi = self::getApiUrl(Helper::get('promoApiEndpoints')['confirm_newsletter'], array('confirm_token' => $confirm_token));
    if (!$confirm_token) {
      return array(
          'error' => true,
          'message' => 'Token not found'
        );      
    }

    $response = self::req("put", $confirmApi);
    if (!is_wp_error($response)) {
      $response_body = json_decode($response["body"], true);
      if ($response['response']['code'] === 404) {
        $output = array(
            "error" => true,
            "message" => "Token not found"
          );
      } else {
        $output = array(
            "success" => true,
            "email" => $response_body['data']["attributes"]["email"],
            "confirmed" => $response_body['data']["attributes"]["confirmed"]
          );
      }
    } else {
      $output = array(
        "error" => true,
        "message" => $response->get_error_message()
      );
    }
    return $output;    
  }
}
