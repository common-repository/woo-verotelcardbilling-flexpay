<?php
namespace FlexPayWoo\Controller;

use FlexPayWoo\ApiCommunicator;

class PaymentController
{
    public $shopID, $merchantID, $signature, $gateway;

    public function __construct(\FlexPayWoo\WC_Gateway_FlexPay $gateway)
    {
        $this->gateway = $gateway;
        $this->shopID = $gateway->shopID;
        $this->merchantID = $gateway->merchantID;
        $this->signature = $gateway->signature;
        $this->subscription_controller = new SubscriptionController(new ApiCommunicator($this->gateway->apiUsername, $this->gateway->apiPassword));
    }

    public function getFlexPayClient()
    {
        $brand = \Verotel\FlexPay\Brand::create_from_merchant_id($this->merchantID);
        return new \Verotel\FlexPay\Client($this->shopID, $this->signature, $brand);
    }

    public function get_return_url_for_product($order)
    {
        $params = array(
            "priceAmount" => $order->get_total(),
            "priceCurrency" => get_woocommerce_currency(),
            "description" => "Payment for order # " . $order->get_id() . " on " . get_option('siteurl'),
            "email" => $order->get_billing_email(),
            "type" => 'purchase',
            "referenceID" => $order->get_id(),
            "origin" => "Woocommerce WP plugin " . $this->gateway->version,
            "backURL" => $order->get_checkout_order_received_url()
        );
        $order_key = $order->get_order_key();
        $flexpayClient = $this->getFlexPayClient();
        $purchaseUrl = $flexpayClient->get_purchase_URL($params);
        return $purchaseUrl;
    }

    public function get_return_url_for_subscription($order)
    {
        $calculated_total = $order->get_total();
        $subscriptions_in_order = wcs_get_subscriptions_for_order($order->get_id());
        foreach ($subscriptions_in_order as $subscription) {
            $period = $subscription->get_billing_period();
        }

        if (!$period) {
            return false;
        }

        $interval = $subscription->get_billing_interval();

        if (!$interval) {
            return false;
        }

        $params = array(
            "subscriptionType" => "recurring",
            "name" => "Recurring subscription order # " . $order->get_id() . " on " . get_option('siteurl'),
            "priceAmount" => $calculated_total,
            "priceCurrency" => get_woocommerce_currency(),
            "period" => $this->subscription_controller->make_period_for_processing($period, $interval, $order),
            "email" => $order->get_billing_email(),
            "referenceID" => $order->get_id(),
            "backURL" => $order->get_checkout_order_received_url(),
            "origin" => "Woocommerce WP plugin " . $this->gateway->version
        );

        $flexpayClient = $this->getFlexPayClient();
        $subscriptionUrl = $flexpayClient->get_subscription_URL($params);
        return $subscriptionUrl;
    }

    public function process_payment_for_subscription($order)
    {
        $subscriptions_in_order = wcs_get_subscriptions_for_order($order->get_id());
        foreach ($subscriptions_in_order as $subscription) {
            if ($subscription->get_sign_up_fee($this)) {
                return wc_add_notice(
                    $this->gateway->title .
                    " gateway does not support sign up fee's in subscriptions, please select a different gateway",
                    'error'
                );
            }

            if ($this->subscription_controller->is_lowest_period_valid($subscription, $order)) {
                $order->add_order_note(
                    "Customer tried to pay the subscription with " .
                    $this->gateway->title .
                    " gateway that doesnt support subscriptions that have period less than a one week"
                );
                return wc_add_notice(
                    $this->gateway->title .
                    " gateway cannot process subscriptions that have period of payment less than a one week",
                    "error"
                );
            }
        }

        if ($order->get_item_count() === 1) {
            return array(
                'result' => 'success',
                'redirect' => $this->gateway->get_return_url($order),
            );
        }

        return wc_add_notice(
            "Only one quantity of subscription is allowed if you want to pay with " .
            $this->gateway->title,
            "error"
        );

    }

    public function process_payment_for_product($order)
    {
        return array(
            'result' => 'success',
            'redirect' => $this->gateway->get_return_url($order),
        );
    }

    public function validate_postback_signature($get_response)
    {
        $flexpayClient = $this->getFlexPayClient();
        if ($flexpayClient->validate_signature($get_response)) {
            return true;
        }
        return false;
    }

    public function postback_handler_for_products($get_response, $order)
    {
        if ($this->validate_postback_signature($get_response)) {
            $event = $this->gateway->input_validator->get_get_value("event");

            if (!$event) {
                $order->payment_complete();
                return esc_html("OK");
            }

            if ($event === "credit") {
                $result = $this->handle_refund_postback($order);
                return esc_html($result);
            }

            $order->update_status("failed");
            $order->add_order_note("Order process failed, because of unknown event type in postback");
            return esc_html("ERROR");
        }
        $order->update_status("failed");
        $order->add_order_note("Order process failed, because the signature was incorrect");
        return esc_html("ERROR");
    }

    private function handle_missing_get_parameter(\WC_Order $order): string {
        $order->update_status("failed");
        $order->add_order_note("Order process failed, because of the missing parameter in the GET");
        return esc_html("ERROR");
    }

    public function postback_handler_for_subscriptions($get_response, $order)
    {
        if ($this->validate_postback_signature($get_response)) {
            $event = $this->gateway->input_validator->get_get_value("event");
            $reference_id = $this->gateway->input_validator->get_get_value("referenceID");
            $sale_id = $this->gateway->input_validator->get_get_value("saleID");
            if ($event && $reference_id && $sale_id) {
                switch ($event) {
                    case "initial":
                        $transaction_id = $this->gateway->input_validator->get_get_value("transactionID");
                        if ($transaction_id === null) {
                            return $this->handle_missing_get_parameter($order);
                        }
                        update_post_meta($reference_id, "_verotel_flexpay_saleID", $sale_id);
                        update_post_meta($reference_id, "_verotel_flexpay_cancelledByCC", false);
                        update_post_meta($reference_id, "_verotel_flexpay_transactionID", $transaction_id);
                        $order->payment_complete();
                        return esc_html("OK");
                    break;
                    case "rebill":
                        $transaction_id = $this->gateway->input_validator->get_get_value("transactionID");
                        if ($transaction_id === null) {
                            return $this->handle_missing_get_parameter($order);
                        }
                        $original_order = $order;
                        $original_subscription_order = new \WC_Subscription($original_order->get_id());
                        $renewal_order = wcs_create_renewal_order( $original_subscription_order );
                        $renewal_order->add_order_note( __('Renewal order for order #' . $reference_id, 'verotel_flexpay_plugin'));
                        $renewal_order->set_payment_method($this->gateway->id);
                        $renewal_order->update_status('completed');
                        update_post_meta($renewal_order->get_id(), "_verotel_flexpay_transactionID", $transaction_id);
                        $original_order->add_order_note( __( 'Create and complete renewal order requested by gateway.', 'verotel_flexpay_plugin' ), false, true );
                        $original_order->update_status("processing");
                        return esc_html("OK");
                    break;
                    case "expiry":
                        $subscriptions = wcs_get_subscriptions_for_order($order->get_id());
                        if (count($subscriptions) > 0) {
                            foreach ($subscriptions as $subscription) {
                                $subscription->update_status("expired");
                                $subscription->add_order_note(
                                    __(
                                        "Subscription marked as expired due to a expiry postback from gateway",
                                        "verotel_flexpay_plugin"
                                    )
                                );
                            }
                            $order->update_status("completed");
                            $order->add_order_note(
                                __("Order marked as completed due to an expiry postback from gateway",
                                    "verotel_flexpay_plugin")
                            );
                            return "OK";
                        } else {
                            $order->update_status("failed");
                            $order->add_order_note(
                                __("Order has no subscriptions, so the expiry postback from gateway cannot be processed",
                                    "verotel_flexpay_plugin")
                            );
                            return "ERROR";
                        }
                    case "cancel":
                        $order->add_order_note( __("Subscription cancelled by ".$_GET["cancelledBy"], "verotel_flexpay_plugin"));
                        update_post_meta($reference_id, "_verotel_flexpay_cancelledByCC", true);
                        $order->update_status("cancelled");
                        return "OK";
                    case "credit":
                        $parent_transaction_id = $this->gateway->input_validator->get_get_value("parentID");
                        if ($parent_transaction_id === null) {
                            return $this->handle_missing_get_parameter($order);
                        }

                        $args = array(
                            'post_type' => 'shop_order',
                            'fields' => 'ids',
                            'posts_per_page' => 1,
                            'post_status' => 'all',
                            'meta_key' => "_verotel_flexpay_transactionID",
                            'meta_value' => $parent_transaction_id,
                        );
                        $order_ids = get_posts($args);

                        if (count($order_ids) === 0) {
                            return esc_html("Cannot find an order to refund using received parentID");
                        }
                        return $this->handle_refund_postback(new \WC_Order($order_ids[0]), $order->get_id());
                    break;
                }
                $order->update_status("failed");
                $order->add_order_note("Order process failed, because of the wrong postback event");
                return esc_html("ERROR");
            }
            return $this->handle_missing_get_parameter($order);
        }
        $order->update_status("failed");
        $order->add_order_note("Order process failed, because the signature was incorrect");
        return esc_html("ERROR");
    }

    public function handle_refund_postback($order, $initial_order_id = null) {
        $initial_order_id = $initial_order_id ?: $order->get_id();

        $price_amount = $this->gateway->input_validator->get_get_value("priceAmount");
        $price_currency = $this->gateway->input_validator->get_get_value("priceCurrency");
        $type = $this->gateway->input_validator->get_get_value("type");
        if ($price_amount === null or !$price_currency or !$type) {
            $order->update_status("failed");
            $fail_note = "Refund order failed because of missing GET params";
            $order->add_order_note($fail_note);
            return $fail_note;
        }

        if ($order->has_status("refunded")) {
            return "Order #" . $order->get_id() . " is already refunded";
        }

        $refund = \wc_create_refund(
            array(
                'order_id' => $order->get_id(),
                'amount'   => $price_amount,
            )
        );

        if ($refund instanceof \WP_Error) {
            return $refund->get_error_message();
        }

        $price_with_currency = $price_amount . " " . $price_currency;
        $order->add_order_note(
            "Order refunded from control center: $price_with_currency"
            . " (please note that the 'refunded by' should be 'control center'"
            . ", the username is not propagated and some default is used)."
        );

        if ($type == "subscription") {
            $subscription_phase = $this->gateway->input_validator->get_get_value("subscriptionPhase");
            if ($subscription_phase === null) {
                $order->update_status("failed");
                $fail_note = "Cannot define if the subscription should be cancelled by refund - missing subscriptionPhase";
                $order->add_order_note($fail_note);
                return $fail_note;
            }
            if ($subscription_phase === "terminated") {
                $subscriptions = wcs_get_subscriptions_for_order($initial_order_id);
                $refund_order_id = $order->get_id();
                foreach ($subscriptions as $subscription) {
                    $subscription->add_order_note("Subscription cancelled due to a refund of"
                                                  . " order #$refund_order_id with subscription termination.");
                    $subscription->update_status("cancelled");
                }
            }
        }

        return "OK";
    }

}