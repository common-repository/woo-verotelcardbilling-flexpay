=== Plugin Name ===

Contributors: @verotel
Tags: WooCommerce, verotel, flexpay, Cardbilling, gateway, pay, Bill, GayCharge, BitsafePay
Requires at least: 5.9.1
Tested up to: 5.9.1
Requires PHP: 7.4
Stable tag: 1.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This is an official Verotel plugin for for WooCommerce. It enables you to use the Verotel / CardBilling / Bill / GayCharge / BitsafePay gateways for your payments.

== Description ==

This plugin for WooCommerce enables you to use the Verotel / CardBilling / Bill / GayCharge / BitsafePay gateways to accept payments.
To use this plugin you will need the WooCommerce plugin for WordPress and have a Verotel or CardBilling or Bill or GayCharge merchant account.
Once the plugin is configured you can start selling your products using Verotel / CardBilling / Bill / GayCharge / BitsafePay services.
In order to process subscriptions you will also need WooCommerce Subscriptions plugin and Control Center API.

== Installation ==

This section describes how to install the plugin and how configure it.

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

== Configuration ==
In order to configure the plugin you will need:
	* Verotel / CardBilling / Bill / GayCharge / BitsafePay 16-digit Merchant ID you are assigned when you have registered with Verotel / CardBilling / Bill / GayCharge / BitsafePay
	* Shop ID
	* Signature Key

1. Login to your Verotel / CardBilling / Bill / GayCharge / BitsafePay Control Center and go to Setup Websites. Choose the website for which you need the configuration.
2. From website detail go to FlexPay options. On this page you find the "Shop ID" and "Signature Key"
3. Go to WooCommerce settings, find the "Verotel/CardBilling FlexPay" plugin settings in the "Payments" section.
4. Enter the "Merchant ID", "Shop ID" and "Signature Key" and save the changes.
5. In order for Verotel / CardBilling / Bill / GayCharge / BitsafePay to notify your WooCommerce shop on successful payment you now need to configure the Postback URL in Control Center.
6. Copy the WooCommerce generated postback URL from the settings page and enter the value on the FlexPay options page in Control Center.
7. The Success URL can be left empty as it is not used by the plugin.
8. Now you are ready to sell your products via Verotel / CardBilling / Bill / GayCharge / BitsafePay service.

If you want to use WooCommerce Subscriptions the setup follows:

1. Setup WooCommerce Subscriptions plugin
2. Setup a "read and write" Control Center API user (Please Contact Verotel Support to activate Control Center API)
3. Enter the Control Center API user credentials in the WooCommerce settings page

== Screenshots ==

1. The screenshot1 shows the settings tab in the WooCommerce plugin.
2. The screenshot2 shows the 'FlexPay options' in the Verotel / CardBilling / Bill / GayCharge / BitsafePay Control Center.

== Changelog ==

= 1.0 =

* Finally a first official version of Verotel/CardBilling FlexPay gateway for WooCommerce.

= 1.1 =

* Updated Verotel FlexPay library to 4.0.2 so now you can pay with Bill

= 1.2 =

* Updated Verotel FlexPay library to 4.2.0 so paying through PaintFest and GayCharge is available

= 1.3 =

* The WooCommerce checkout page now shows exactly your brand connected to your Merchant ID as an option for payment

= 1.4 =

* Updated Verotel FlexPay library to 4.3.0 so paying through BitsafePay is available

= 1.5 =

* Now we support WooCommerce Subscriptions with WooCommerce Subscriptions plugin.

= 1.5.1 =

* Bug fix

= 1.5.2 =

* Improve description of how to setup plugin to work with Subscriptions and Control Center

= 1.5.3 =

* Bug fix

= 1.5.4 =

* Update guide on how to enable the plugin
* Show settings link next to the activation button of plugin

= 1.5.5 =
* Process subscription expiry postback

= 1.6 =
* Add support for newer versions of WordPress, WooCommerce, WooCommerce Subscriptions

= 1.7 =
* Add FlexPay protocol version 3.5

= 1.8 =
* Add support for credit (refunds) - only for new orders
* Bug fixes

= 1.9 =
* Minor improvements

== Upgrade Notice ==

= 1.0 =

= 1.1 =

* Now you can pay with Bill!

= 1.2 =

* Now you can pay with PaintFest and GayCharge!

= 1.3 =

* The WooCommerce checkout page now shows exactly your brand connected to your Merchant ID as an option for payment

= 1.4 =

* Now you can pay with BitsafePay!

= 1.5 =

* Now we support WooCommerce Subscriptions with WooCommerce Subscriptions plugin.

= 1.5.1 =

* Bug fix

= 1.5.2 =

* Improve description of how to setup plugin to work with Subscriptions and Control Center

= 1.5.3 =

* Bug fix

= 1.5.4 =

* Update guide on how to enable the plugin
* Show settings link next to the activation button of plugin

= 1.5.5 =
* Process subscription expiry postback

= 1.6 =
* Plugin now supports newer versions of WordPress, WooCommerce, WooCommerce Subscriptions

= 1.7 =
* Add FlexPay protocol version 3.5

= 1.8 =
* Add support for credit (refunds) - only for new orders
* Bug fixes

= 1.9 =
* Minor improvements

== Useful Links ==

* [Verotel Website](http://www.verotel.com/ "Verotel website")
* [Verotel Control Center](https://controlcenter.verotel.com/ "Verotel control center")
* [CardBilling Website](http://billing.creditcard "CardBilling website")
* [CardBilling Control Center](https://controlcenter.billing.creditcard/ "CardBilling control center")
* [Bill Website](http://www.bill.creditcard/ "Bill website")
* [Bill Control Center](https://controlcenter.bill.creditcard "Bill control center")
* [GayCharge Website](https://www.gaycharge.com/en/index.html?lang=en "GayCharge website")
* [GayCharge Control Center](https://controlcenter.gaycharge.com/ "GayCharge control center")
* [BitsafePay Website](https://www.bitsafepay.com/ "BitsafePay website")
* [WooCommerce Subscriptions]("https://woocommerce.com/products/woocommerce-subscriptions")