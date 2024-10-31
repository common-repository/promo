<?php namespace PromoSync\Controllers;

use PromoSync\Helper;
use PromoSync\Models\Promo;
use Herbert\Framework\Http;
use Herbert\Framework\Exceptions\HttpErrorException;
use Herbert\Framework\Notifier;

class AdminController {
  
  public function index () {
    // Check if there’s a promo token in DB table wp_options
    if (Promo::hasToken() && Promo::hasBusiness()) { // if there’s a token
      $business = Promo::getBusiness();

     $plugin_error = Promo::getWordpressAccountError();

      if (!empty($plugin_error)) {
        return view('@PromoSync/plugin_error.twig', [
          "business" => $business,
          "logo" => Helper::assetUrl('/img/icon.ico'),
          "links" => Helper::get('website_links'),
          "error" => $plugin_error
        ]);
        
      } else {
        $business_id = $business["id"];
        $promotions = Promo::getPromotions($business_id, true, 'live', true);
        $messages = !empty($_SESSION['messages']) ? $_SESSION['messages'] : array();
        $_SESSION['messages'] = null;

        if (array_key_exists("error", $promotions)) {
          $promotions["promotions"] = array();
          $messages[] = $promotions;
        }

        $archive = Promo::getInstalledPromoArchivePage();
        
        if (!$archive && !Promo::ignoreCreateListing()) {
          // If the user hasn't been prompted to create their listing page yet, ask them. 
          Promo::ignoreCreateListing(true);
          return view('@PromoSync/create_listing.twig');
        } else {
          // show settings view
          return view('@PromoSync/settings.twig', [
            "business" => $business,
            "promotions" => $promotions["promotions"],
            "webhook_url" => Promo::getWebhookUrl(),
            "logo" => Helper::assetUrl('/img/icon.ico'),
            "links" => Helper::get('website_links'),
            "messages" => $messages,
            "archive" => $archive ? get_permalink($archive->ID) : null,
          ]);
          
        }
      }
    } else if (Promo::hasToken()) {
      $businesses = Promo::getBusinesses();
      // show select business view
      return view('@PromoSync/select_business.twig', [
        "businesses" => $businesses["businesses"],
        "logo" => Helper::assetUrl('/img/icon.ico'),
        "links" => Helper::get('website_links')
      ]);
    } else {
      // show authorize view
      return view('@PromoSync/authorize.twig', [
        "logo" => Helper::assetUrl('/img/icon.ico'),
        "links" => Helper::get('website_links')
      ]);
    }
  }
  
  // Executed when plugin gets activated
  public function activate () {
    
  }

  // Executed when plugin gets deactivated
  public function deactivate () {
    // Remove promo token from wp_options
    // Remove promo post type and data
  }

  // Executed when plugin gets uninstalled
  public function uninstall () {
    // Remove all previously created promos?
  }

  public function sync () {
    $business = Promo::getBusiness();
        
    // get current promos
    $promos = Promo::getPromotions($business["id"]);
    if (array_key_exists("success", $promos)) {
      $create_count = 0;
      $update_count = 0;
      $error_count = 0;
      // for each promo
      foreach ($promos["promotions"] as $promotion) {
        $sync_result = Promo::syncPromotion($promotion);
        if (array_key_exists("message", $sync_result)) {
          if (array_key_exists("success", $sync_result)) {
            if ($sync_result["action"] == "create") {
              $create_count++;
            } else {
              $update_count++;
            }
          } else {
            $error_count++;
            $_SESSION['messages'][] = $sync_result;
          }
        }
      }
      $_SESSION['messages'][] = array("success"=>true,"message"=>$create_count . " new Promo posts created");
      $_SESSION['messages'][] = array("success"=>true,"message"=>$update_count . " Promo posts updated");
    } else {
      if (!empty($promos["message"])) {
        $promos["message"] = "Something went wrong. Please try again.";
      }
      Notifier::error($promos["message"], true);
    }
    return redirect_response(panel_url('PromoSync::mainPanel'));
  }
  
  public function saveandsync () {
    Promo::updateWordpressAccount();
    return self::sync();
  }

  /*
   * Removes all promos
   */
  public function reset () {
    $result = Promo::removeAllPromotionPosts();
    if (array_key_exists("success", $result)) {
      Notifier::success($result["message"]);
    } else {
      Notifier::error($result["message"]);
    }
    return redirect_response(panel_url('PromoSync::mainPanel'));
  }
  
  public function authorize (Http $http) {
    $email = $http->get('email', '');
    $password = $http->get('password', '');
    
    // Verifies that the submitted promo credentials are valid
    $result = Promo::signIn($email, $password);
    // If valid
    if (array_key_exists("success", $result)) {
      // it saves the received token to the database and shows settings view
      // 
      // 
      $token = $result["token"];
      $token_type = $result["token_type"];
      $token_result = Promo::saveToken($token, $token_type);

      if (array_key_exists("success", $token_result)) {
        Notifier::success('Login successful. Select your business to continue.', true);
      } else {
        Notifier::success($token_result["message"], true);
      }
    } else {
      $error = $result["message"];
      // it queues an error message and shows the authorize view
      Notifier::error($error, true);
    }
    return redirect_response(panel_url('PromoSync::mainPanel'));
  }

  // Removes the token from the DB and shows the authorize view
  public function deauthorize () {
    Promo::disconnectWordpressAccount();  // make sure we do this first before expiring token
    Promo::expireToken();
    Promo::ignoreCreateListing(false);    // remove this flag in case the try again. 
    Promo::removeBusiness();
    Promo::removeAllPromotionPosts();
    Notifier::success('Your Promo account is now disconnected from WordPress', true);
    return redirect_response(panel_url('PromoSync::mainPanel'));
  }

  public function selectBusiness (Http $http) {
    $business_id = $http->get('business_id', '');

    if (!$business_id || empty($business_id)) {
      Notifier::error("Please select one of your businesses", true);
    } else {
      // Verifies that the submitted promo credentials are valid
      $result = Promo::saveBusiness($business_id);
      // If valid
      if (array_key_exists("success", $result)) {
        
        // check to see this conflicts with another Wordpress install first: 
        $plugin_error = Promo::getWordpressAccountError(true);
        if (empty($plugin_error)) {
          Promo::updateWordpressAccount();
          //Notifier::success('Your Promo Plugin is now ready to go', true);
          return self::sync();
        }
      } else {
        // it queues an error message and shows the authorize view
        Notifier::error($result["message"], true);
      }
    }
    return redirect_response(panel_url('PromoSync::mainPanel'));
  }
  
  public function generateArchive (Http $http) {
    $page = Promo::getInstalledPromoArchivePage();
    if (empty($page)) {
      Promo::createPromoArchivePage();
      Notifier::success('Your Promo listing page has been created.');
    }
   return redirect_response(panel_url('PromoSync::mainPanel'));   
  }
  
}