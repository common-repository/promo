<?php namespace PromoSync\Controllers;

use PromoSync\Helper;
use PromoSync\Models\Promo;
use Herbert\Framework\Http;
use Herbert\Framework\Exceptions\HttpErrorException;
use Herbert\Framework\Notifier;

class ApiController {

  public static function index ($webhook_token, Http $http) {
    
    $event_name = $http->get('attributes')['name'];
    $promotion_id = $http->get('relationships')['promotion']['data']['id'];

    switch ($event_name) {
      case "PromotionLaunched":
        return self::promotionLaunched($promotion_id);
        break;
      case "PromotionUpdated":
        return self::promotionUpdated($promotion_id);
        break;
    }
  }
  
  public static function promotionLaunched ($promotion_id) {
    $promotion_resp = Promo::getPromotion($promotion_id, true);
    if (array_key_exists("success", $promotion_resp)) {
      $promotion = $promotion_resp["promotion"];
      $sync_resp = Promo::syncPromotion($promotion);
      if (array_key_exists("success", $sync_resp)) {
        self::promotionLaunchedSuccess($sync_resp);
      } elseif (array_key_exists("error", $sync_resp)) {
        self::promotionLaunchedError($promotion_id, $sync_resp["message"]);
      }
      return json_encode($sync_resp);
    } else {
      self::promotionLaunchedError($promotion_id, $promotion_resp["message"]);
      return json_encode($promotion_resp);
    }
  }

  private static function promotionLaunchedSuccess ($sync) {
    if (array_key_exists("promotion", $sync) && array_key_exists("post_id", $sync)) {
      $subject = "Promotion post launched";
      $message = "Successfully launched promotion " . $sync["promotion"]["id"] . ".";
      $message .= "\n\n";
      $message .= "View your promotion post here " . get_permalink($sync["post_id"]);
      self::notifyAdmin($subject, $message);
    }
  }

  private static function promotionLaunchedError ($promotion_id, $message) {
    $subject = "Error creating promotion post";
    $message = "Error while trying to create post for promotion " . $promotion_id . ".";
    $message = "\n\n" . $message;
    self::notifyAdmin($subject, $message);
  }

  public static function promotionUpdated ($promotion_id) {
    $promotion_resp = Promo::getPromotion($promotion_id, true);
    if (array_key_exists("success", $promotion_resp)) {
      $promotion = $promotion_resp["promotion"];
      $sync_resp = Promo::syncPromotion($promotion);
      if (array_key_exists("success", $sync_resp)) {
        self::promotionUpdatedSuccess($promotion);
      } elseif (array_key_exists("error", $sync_resp)) {
        self::promotionUpdatedError($promotion_id, $sync_resp["message"]);
      }
      return json_encode($sync_resp);
    } else {
      self::promotionUpdatedError($promotion_id, $promotion_resp["message"]);
      return json_encode($promotion_resp);
    }
  }

  private static function promotionUpdatedSuccess ($sync) {
    if (array_key_exists("promotion", $sync) && array_key_exists("post_id", $sync)) {
      $subject = "Promotion post updated";
      $message = "Successfully updated promotion " . $sync["promotion"]["id"] . ".";
      $message .= "\n\n";
      $message .= "View your promotion post here " . get_permalink($sync["post_id"]);
      self::notifyAdmin($subject, $message);
    }
  }

  private static function promotionUpdatedError ($promotion_id, $message) {
    $subject = "Error updating promotion post";
    $message = "Error while trying to updated post for promotion " . $promotion_id . ".";
    $message .= "\n\n" . $message;
    self::notifyAdmin($subject, $message);
  }

  public static function notifyAdmin ($subject, $message) {
    
    $admin_email = get_option( 'admin_email' );
    $subject = "Promo: " . $subject;
    $nl = "\n\n";
    $heading = "Promo WP Plugin Notification";
    $footer = "Learn more at https://promo.co/wp-plugin";
    $message = $heading . $nl . $message . $nl . $footer;
    
    // return wp_mail($admin_email, $subject, $message);
  }
}