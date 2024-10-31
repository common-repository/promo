<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Promo
 * Plugin URI:        https://selfserve.promo.co/wordpress
 * Description:       Looking to promote your business, products, or services online? Want a lightning fast way to create promotions that integrate to your wordpress website and automatically pushes to social networks and email list? Look no further!! Our mobile marketing platform will save you time and help you grow your business. Get started for free today.
 * Version:           1.0.0
 * Author:            Viral Foundry
 * Author URI:        http://viralfoundry.com/
 * License:           MIT
 */

define('PROMO_BASENAME', plugin_basename(__FILE__));

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/getherbert/framework/bootstrap/autoload.php';
