<?php

$custom_settings = array(

    'enabled' => array(
        'title' => __('Enable/Disable', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable Verotel / CardBilling / Bill / GayCharge / BitsafePay gateway', 'woocommerce')
    ),
    'signature' => array(
        'title' => __('Signature Key', 'woocommerce'),
        'type' => 'text',
        'placeholder' => __('Signature Key', 'woocommerce')
    ),
    'shopID' => array(
        'title' => __('Shop ID', 'woocommerce'),
        'type' => 'text',
        'placeholder' => __('Shop ID', 'woocommerce')
    ),
    'merchantID' => array(
        'title' => __('Merchant ID', 'woocommerce'),
        'type' => 'text',
        'placeholder' => __('Merchant ID', 'woocommerce')
    ),
    'apiTitle' => array(
        'title' => __('API Settings', 'woocommerce'),
        'type' => 'title',
        'description' => __('If you want to use Verotel / CardBilling / Bill / GayCharge / BitsafePay subscriptions, please enter your Control Center API credentials (ask our support to have Control Center API activated)', 'woocommerce')
    ),
    'apiUsername' => array(
        'title' => __('API Username', 'woocommerce'),
        'type' => 'text',
        'placeholder' => __('API Username', 'woocommerce')
    ),
    'apiPassword' => array(
        'title' => __('API Password', 'woocommerce'),
        'type' => 'password',
        'placeholder' => __('API Password', 'woocommerce')
    )
);

return $custom_settings;