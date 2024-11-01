<?php
namespace FlexPayWoo;

class WC_Gateway_FlexPay extends \WC_Payment_Gateway
{

    /**
     * @var string $version Version of the plugin
     */
    public $version = "1.9";

    /**
     * @var PaymentController $payment_controller Instance of the controller
     */
    public $payment_controller;

    /**
     * @var InputValidator $input_validator Instance of the validator
     */
    public $input_validator;

    /**
     * @var ApiCommunicator $api_communicator Instance of API communicator
     */
    public $api_communicator;

    public $merchantID, $signature, $shopID, $apiUsername, $apiPassword;
    private $allowed_currencies = array("USD", "EUR", "GBP", "AUD", "CAD", "CHF", "DKK", "NOK", "SEK");

    public function __construct()
    {
        $success_url = get_option('siteurl') . "/index.php/checkout/order-received";
        $postback_url = get_option('siteurl') . "/?wc-api=callback";
        $this->id = 'verotel';
        $this->icon = 'http://www.verotel.com/images.v2/logos.png';
        $this->title = 'Verotel / CardBilling / Bill / GayCharge / BitsafePay FlexPay';
        $this->method_title = "Verotel / CardBilling / Bill / GayCharge / BitsafePay FlexPay";
        $this->method_description = $this->title . "plugin enables your customers to pay with credit card, "
            . "using " . $this->title . " gateway.<br><br>"
            . "Control Center Setup:<br>"
            . "- Set the 'Flexpay postback script URL' in your Control Center to: <strong>"
            . esc_url($postback_url) . "</strong><br>"
            . "- 'Flexpay success URL' is not used and can be left empty.";
        $this->init_form_fields();
        $this->init_settings();
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options',
            ));
        }

        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        $this->input_validator = new InputValidator($this->id, $this->plugin_id, $this->apiUsername, $this->apiPassword);
        $this->payment_controller = new \FlexPayWoo\Controller\PaymentController($this);

        if ($this->apiUsername and $this->apiPassword) {
            $this->supports = array(
                "products",
                "subscriptions",
                "gateway_scheduled_payments",
                "subscription_cancellation",
            );
        } else {
            $this->supports = array(
                "products",
            );
        }

        if ($this->merchantID) {
            $brand = \Verotel\FlexPay\Brand::create_from_merchant_id($this->merchantID);
            if ($brand) {
                $class_name = explode("\\", get_class($brand));
                $this->title = array_pop($class_name);
            }
        }

        add_action('woocommerce_api_callback', array(
            $this,
            'postback_handler',
        ));

        add_action("woocommerce_subscription_status_active_to_pending-cancel", array(
            $this->payment_controller->subscription_controller,
            "process_subscription_cancellation"
        ), 0, 1);

        add_action("woocommerce_subscription_status_active_to_cancelled", array(
            $this->payment_controller->subscription_controller,
            "process_subscription_cancellation"
        ), 0, 1);

        if (
            $this->enabled == "yes"
            and (
                $this->signature === null
                or $this->shopID === null
                or $this->merchantID === null
            )
        ) {
            $this->enabled = "no";
        }

        if (!$this->check_eshop_currency()) {
            $this->enabled = "no";
        }
    }

    public function check_eshop_currency()
    {
        return in_array(get_woocommerce_currency(), $this->allowed_currencies);
    }

    public function admin_options()
    {
        if ($this->check_eshop_currency()) {
            parent::admin_options();
        } else {
            ?>
            <div class="inline error">
                <p>
                    <strong>
                        <?php _e('Gateway disabled', 'woocommerce');?>
                    </strong>:
                    <?php
                    _e($this->title . ' plugin does not'
                        . ' support your store currency.', 'woocommerce'
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }

    public function validate_signature_field($key)
    {
        return $this->input_validator->validate_signature_field($key);
    }

    public function validate_shopID_field($key)
    {
        return $this->input_validator->validate_shopID_field($key);
    }

    public function validate_merchantID_field($key)
    {
        return $this->input_validator->validate_merchantID_field($key);
    }

    public function validate_apiUsername_field($key)
    {
        return $this->input_validator->validate_apiUsername_field($key);
    }

    public function validate_apiPassword_field($key)
    {
        return $this->input_validator->validate_apiPassword_field($key);
    }

    /* resolve type of postback, only complete payment on success callback */

    public function postback_handler()
    {
        echo $this->postback_handler_helper();
        exit;
    }

    public function postback_handler_helper()
    {
        $order_id = $this->input_validator->get_get_value("referenceID");
        if ($order_id === null) {
            return "Order process failed, because of the missing referenceID in the GET";
        }

        try {
            $order = new \WC_Order($order_id);
        } catch (\Exception $err) {
            if ($err->getMessage() == "Invalid order.") {
                return "ERROR \n Wrong order ID";
            }
            return "ERROR \n There was a problem with your order";
        }
        if (function_exists('wcs_order_contains_subscription')) {
            if (wcs_order_contains_subscription($order)) {
                return $this->payment_controller->postback_handler_for_subscriptions($_GET, $order);
            } else {
                return $this->payment_controller->postback_handler_for_products($_GET, $order);
            }
        } else {
            return $this->payment_controller->postback_handler_for_products($_GET, $order);
        }
    }

    public function init_form_fields()
    {
        $this->form_fields = include __DIR__ . '/../settings.php';
    }

    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = new \WC_Order($order_id);

        $order->update_status('pending', __('Waiting for payment', 'woocommerce'));
        wc_reduce_stock_levels($order_id);

        if (function_exists('wcs_order_contains_subscription')){
            if (wcs_order_contains_subscription($order)) {
                return $this->payment_controller->process_payment_for_subscription($order);
            }
        }

        return $this->payment_controller->process_payment_for_product($order);
    }

    public function get_return_url($order = null)
    {
        $url = parent::get_return_url($order);
        if ($order === null) {
            return $url;
        }
        if (function_exists('wcs_order_contains_subscription')){
            if (wcs_order_contains_subscription($order)) {
                return $this->payment_controller->get_return_url_for_subscription($order);
            }
        }
        return $this->payment_controller->get_return_url_for_product($order);
    }
}