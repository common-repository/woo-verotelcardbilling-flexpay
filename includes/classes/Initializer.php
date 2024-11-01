<?php

namespace FlexPayWoo;

class Initializer {
    public function init() {
        add_action('plugins_loaded', array($this, 'verotel_cardbilling_plugin_init_Plugin'));
    }

    public function verotel_cardbilling_plugin_init_Plugin()
    {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $plugin = explode("/", plugin_basename(__FILE__))[0] . "/index.php";
            add_filter("plugin_action_links_" . $plugin, 'FlexPayWoo\verotel_cardbilling_plugin_add_settings_link');

            add_filter('woocommerce_quantity_input_args', 'FlexPayWoo\allow_only_one_quantity', 10, 2);
            add_filter('woocommerce_payment_gateways', 'FlexPayWoo\add_FlexPay');
        } else {
            add_action('admin_notices', 'not_active_woo_notice');
        }
    }
}
