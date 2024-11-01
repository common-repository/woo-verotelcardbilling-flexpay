<?php

namespace FlexPayWoo\Controller;

use FlexPayWoo\ApiCommunicator;
use FlexPayWoo\CancelSubscriptionException;

class SubscriptionController
{

    public $api_communicator;

    public function __construct(ApiCommunicator $api_communicator)
    {
        $this->api_communicator = $api_communicator;
    }

    public function period_converter($period)
    {
        switch ($period) {
            case "day":
                return "D";
                break;
            case "week":
                return "W";
                break;
            case "month":
                return "M";
                break;
            case "year":
                return "Y";
                break;
            default:
                return false;
                break;
        }
    }

    public function make_period_for_processing($period, $interval, $order)
    {
        $converted_period = $this->period_converter($period);
        if ($converted_period == "W") {
            $week_to_days = 7;
            return $final_period = "P" . $week_to_days * $interval . "D";
        } else {
            return $final_period = "P" . $interval . $converted_period;
        }
    }

    public function is_lowest_period_valid($subscription)
    {
        $default_period = $subscription->get_billing_period();
        $period = $this->period_converter($default_period);
        $interval = $subscription->get_billing_interval();
        if ($period) {
            if ($period == "D" and $interval <= 7) {
                return true;
            }
            return false;
        } else {
            return true;
        }
        return false;
    }

    public function process_subscription_cancellation(\WC_Subscription $subscription)
    {
        $parent_order_id = $subscription->get_parent_id();
        $order = wc_get_order($parent_order_id);

        $payment_method = $order->get_payment_method();
        if ($payment_method !== 'verotel') {
            return;
        }

        $sale_id = get_post_meta($order->get_id(), "_verotel_flexpay_saleID", true);
        $is_cancelled_via_controlcenter = get_post_meta($order->get_id(), "_verotel_flexpay_cancelledByCC", true);

        if ($is_cancelled_via_controlcenter) {
            return;
        }

        try {
            $this->api_communicator->cancel_subscription($sale_id);
        } catch (CancelSubscriptionException $cancel_subscription_exception) {
            if (!is_admin()) {
                wc_add_notice('Error in subscription cancellation, please contact us', "error");
            }

            $order->add_order_note(
                "User subscription cancellation ended with status: "
                . $cancel_subscription_exception->get_status_code()
                . " - "
                . $cancel_subscription_exception->get_message()
                . ". Please cancel the recurring in the Control Center"
            );
            add_filter( 'woocommerce_add_success', function( $message ) {
                if ($message == 'Your subscription has been cancelled.') {
                    $message = '';
                }
                return $message;
            });

            $receiver = get_bloginfo('admin_email');
            $subject = 'WooCommerce FlexPay plugin';
            $message = "There is a subscription, that threw an error in the cancellation process. ".
                "Please check the order in WooCommerce and Control Center! \nWooCommerce OrderID: " . $parent_order_id ."\n".
                "saleID: " . $sale_id . "\nFROM: " . $order->get_billing_email();
            wp_mail($receiver, $subject, $message);

            $order->update_status("failed");
        }
    }
}