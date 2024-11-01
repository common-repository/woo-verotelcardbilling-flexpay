<?php

namespace FlexPayWoo;

class ApiCommunicator
{


    CONST API_URL = "https://controlcenter.verotel.com/api/";
    CONST HTTP_GET_METHOD = "GET";
    CONST HTTP_POST_METHOD = "POST";

    /**
     * @var InputValidator $input_validator Instance of validator
     */
    public $input_validator;

    /**
     * @var string $apiUsername Saved value from the apiUsername input in the settings
     */
    public  $apiPassword;

    /**
     * @var string $apiPassword Saved value from the apiPassword input in the settings
     */
    public $apiUsername;

    /**
     * @var WC_Gateway_FlexPay $gateway Instance of the payment gateway
     */
    public $gateway;

    public function __construct($apiUsername, $apiPassword)
    {
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
    }

    protected function make_api_request($apiUsername, $apiPassword, $path, $method = self::HTTP_GET_METHOD)
    {
        $url = SELF::API_URL.$path;
        $credentials_together = $apiUsername . ":" . $apiPassword;
        $encoded_credentials = base64_encode($credentials_together);
        $headers = array(
            "Authorization: Basic " . $encoded_credentials,
            "Accept: application/json; version=1.5.0",
        );

        $ch = curl_init($url);

        if ($method === self::HTTP_POST_METHOD) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $server_output = curl_exec($ch);
        $response_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array("status_code" => $response_status, "output" => json_decode($server_output));
    }

    public function is_api_available($apiUsername, $apiPassword)
    {
        $response_array = $this->make_api_request($apiUsername, $apiPassword, "dashboard/totals?output_currency=USD");

        switch ($response_array["status_code"]) {
            case 200:
                return true;
                break;
            case 401:
                \WC_Admin_Settings::add_error(
                    esc_html__(
                        "Please enter a valid API Credentials.",
                        'verotel_cardbilling_plugin'
                    )
                );
                break;
            default:
                \WC_Admin_Settings::add_error(
                    esc_html__(
                        "There was an error processing your request of saving Control Center API credentials, "
                        . "there might be a problem on our site. Please contact us.",
                        'woocommerce'
                    )
                );
                break;
        }
        return false;
    }

    public function cancel_subscription($sale_id)
    {
        $response_array = $this->make_api_request($this->apiUsername, $this->apiPassword, 'sale/'.$sale_id.'/cancel', "POST");

        if (
            $response_array["status_code"] === 412
            and $response_array["output"]->error->title === "This sale is already cancelled."
        ) {
            return;
        }

        if ($response_array["status_code"] !== 200 or !isset($response_array["output"]->is_success)) {
            throw new CancelSubscriptionException($response_array["output"]->error->title ,$response_array["status_code"]);
        }
    }
}
