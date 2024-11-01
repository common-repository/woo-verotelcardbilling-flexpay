<?php

namespace FlexPayWoo;

function allow_only_one_quantity($args, $product)
{
    if(class_exists('\\WC_Subscriptions_Product') &&
        \WC_Subscriptions_Product::is_subscription($product)) {

        $args['input_value'] = 1;
        $args['max_value'] = 1;
        $args['min_value'] = 1;
    }
    return $args;
}

function add_FlexPay($methods)
{
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        $methods[] = '\FlexPayWoo\WC_Gateway_FlexPay';
        return $methods;
    }
}

function not_active_woo_notice()
{
    ?>
    <div class="error notice">
        <p>
            <?php
            _e(
                "Please activate WooCommerce plugin, "
                ."if you want to use Verotel / CardBilling / Bill / GayCharge FlexPay Plugin for WooCommerce",
                'FlexPayWoo'
            );
            ?>
        </p>
    </div>
    <?php
}

function verotel_cardbilling_plugin_add_settings_link($links)
{
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=verotel') . '" aria-label="' . esc_attr__('View FlexPayWoo settings', 'woocommerce') . '">' . esc_html__('Settings', 'woocommerce') . '</a>';
    array_push($links, $settings_link);
    return $links;
}