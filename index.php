<?php
/*

  Plugin Name: Verotel/CardBilling/Bill/GayCharge/BitsafePay FlexPay for WooCommerce
  Plugin URI: http://www.verotel.com/en/index.html?lang=en
  Description: Pay with card using Verotel or CardBilling or Bill or GayCharge or BitsafePay service.
  Author: Verotel ITS
  Version: 1.9
  Author URI: http://www.verotel.com/en/index.html?lang=en
  WC requires at least: 6.2.1
  WC tested up to: 6.2.1
 */
/**
 * Provides a Verotel / CardBilling / Bill / GayCharge / BitsafePay Gateway.
 *
 * @class        WC_Gateway_FlexPay
 * @extends      WC_Payment_Gateway
 * @version      1.9
 * @package      Verotel
 * @author       Verotel ITS
 */

require __DIR__ . "/includes/vendor/autoload.php";

$plugin_initiator = new FlexPayWoo\Initializer();
$plugin_initiator->init();
