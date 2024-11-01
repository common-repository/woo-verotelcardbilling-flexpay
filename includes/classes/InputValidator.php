<?php

namespace FlexPayWoo;

class InputValidator
{
    /**
     * @var string $plugin_id 'woocommerce_' Prefix for custom columns in database
     */
    public $plugin_id;

    /**
     * @var string $id 'verotel' Name of the data that are saved into the database via our plugin etc. woocommerce_verotel_settings
     */
    public $id;

    /**
     * @var ApiCommunicator $api_communicator Instance of class that is used to validate api credentials
     */
    public $api_communicator;

    /**
     * @var $are_api_credentials_valid By default false, changes to true if the api credentials are valid
     */
    public $are_api_credentials_valid;

    public function __construct($id, $plugin_id, $apiUsername, $apiPassword)
    {
        $this->id = $id;
        $this->plugin_id = $plugin_id;
        $this->api_communicator = new ApiCommunicator($apiUsername, $apiPassword);
        $this->are_api_credentials_valid = false;
    }

    public function display_validation_errors($settings_key)
    {
        \WC_Admin_Settings::add_error(
            esc_html__(
                "Please enter a valid " . $settings_key . ".",
                'verotel_cardbilling_plugin'
            )
        );
    }

    public function display_active_subscriptions_error() {
        \WC_Admin_Settings::add_error(
            esc_html__(
                "Cannot delete your credentials because there are active subscriptions bought with this plugin",
                'verotel_cardbilling_plugin'
            )
        );
    }

    public function has_active_subscriptions() {
        $subscriptions = get_posts(
            array(
                'numberposts' => -1,
                'post_type'   => 'shop_subscription', // Subscription post type
                'post_status' => 'wc-active', // Active subscription
            )
        );

        return !!$subscriptions;
    }

    private function get_post_value($key)
    {
        $final_key = $this->plugin_id . $this->id . '_' . $key;
        if (isset($_POST[$final_key])) {
            return $_POST[$final_key];
        }
    }

    public function get_get_value($key) {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        return null;
    }

    public function validate_signature_field($key)
    {
        $value = $this->get_post_value($key);
        if (!ctype_alnum($value) or preg_match('/\s/', $value) or strlen($value) > 50) {
            $this->display_validation_errors("Signature Key");
            return null;
        }
        return $value;
    }

    public function validate_shopID_field($key)
    {
        $value = $this->get_post_value($key);
        if (!is_numeric($value) or strlen($value) > 12) {
            $this->display_validation_errors("Shop ID");
            return null;
        }
        return $value;
    }

    public function validate_merchantID_field($key)
    {
        $value = $this->get_post_value($key);
        if (!preg_match('/^\d{16}$/', $value)) {
            $this->display_validation_errors("Merchant ID");
            return null;
        }
        return $value;
    }

    public function validate_apiUsername_field($key)
    {
        $username = $this->get_post_value($key);
        $password = $this->get_post_value("apiPassword");
        $saved_settings = get_option("woocommerce_verotel_settings");
        $saved_username = $saved_settings["apiUsername"];
        $saved_password = $saved_settings["apiPassword"];

        if ((!$username or !$password) and $saved_username and $saved_password and $this->has_active_subscriptions()) {
            $this->display_active_subscriptions_error();
            return $saved_username;
        }

        if ($username and !$password) {
            $this->display_validation_errors("API Password");
            return null;
        }
        $input_is_valid = true;
        if ($username and $password) {
            if (strlen($username) > 36 or !preg_match("/^[A-Za-z0-9-]+$/", $username)) {
                $input_is_valid = false;
                $this->display_validation_errors("API Username");
                return null;
            }
            if (strlen($password) > 36 or !ctype_alnum($password)) {
                $input_is_valid = false;
                $this->display_validation_errors("API Password");
                return null;
            }
            if ($input_is_valid) {
                if ($this->api_communicator->is_api_available($username, $password)) {
                    $this->are_api_credentials_valid = true;
                    return $username;
                } elseif (!$this->are_api_credentials_valid and $this->has_active_subscriptions()) {
                    $this->display_active_subscriptions_error();
                    return $saved_username;
                }
            }
        }
        return null;
    }

    public function validate_apiPassword_field($key)
    {
        $password = $this->get_post_value($key);
        $username = $this->get_post_value("apiUsername");
        $saved_settings = get_option("woocommerce_verotel_settings");
        $saved_username = $saved_settings["apiUsername"];
        $saved_password = $saved_settings["apiPassword"];

        if (((!$username or !$password) and ($saved_username and $saved_password)) and $this->has_active_subscriptions()) {
            return $saved_password;
        }

        if (!empty($password) and empty($username)) {
            $this->display_validation_errors("API Username");
            return null;
        }
        if ($this->are_api_credentials_valid) {
            return $password;
        } elseif (!$this->are_api_credentials_valid and $this->has_active_subscriptions()) {
            return $saved_password;
        }
        return null;
    }
}
