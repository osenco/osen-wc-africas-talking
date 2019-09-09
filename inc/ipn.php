<?php
add_action('init', 'at_rewrite_add_rewrites');
function at_rewrite_add_rewrites()
{
    add_rewrite_rule('africastalking/([^/]*)/?', 'index.php?africastalking=$matches[1]', 'top');
}

add_filter('query_vars', 'at_rewrite_add_var');
function at_rewrite_add_var($vars)
{
    $vars[] = 'africastalking';
    return $vars;
}

add_action('template_redirect', 'at_process_ipn');
function at_process_ipn()
{
    if (get_query_var('africastalking')) {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: Application/json");

        $api = get_query_var('africastalking', 'something_ominous');
        $action = $_GET['action'];

        switch ($action) {
            case "confirm":
                $response = json_decode(file_get_contents('php://input'), true);

                if (!$response || empty($response)) {
                    exit(
                        wp_send_json(
                            array(
                                'ResultCode' => 1,
                                'ResultDesc' => 'No response data received',
                            )
                        )
                    );
                }

                $TransactionType    = $response['TransactionType'];
                $mpesaReceiptNumber = $response['TransID'];
                $transactionDate    = $response['TransTime'];
                $amount             = $response['TransAmount'];
                $BusinessShortCode  = $response['BusinessShortCode'];
                $BillRefNumber      = $response['BillRefNumber'];
                $InvoiceNumber      = $response['InvoiceNumber'];
                $OrgAccountBalance  = $response['OrgAccountBalance'];
                $ThirdPartyTransID  = $response['ThirdPartyTransID'];
                $phone              = $response['MSISDN'];
                $FirstName          = $response['FirstName'];
                $MiddleName         = $response['MiddleName'];
                $LastName           = $response['LastName'];

                $post = at_post_id_by_meta_key_and_value('_reference', $BillRefNumber);
                if ($post !== false) {
                    wp_update_post(
                        array(
                            'post_content' => file_get_contents('php://input'), 'ID' => $post,
                        )
                    );
                } else {
                    $post_id = wp_insert_post(
                        array(
                            'post_title'   => 'C2B',
                            'post_content' => "Response: " . json_encode($response),
                            'post_status'  => 'publish',
                            'post_type'    => 'mpesaipn',
                            'post_author'  => 1,
                        )
                    );

                    update_post_meta($post_id, '_customer', "{$FirstName} {$MiddleName} {$LastName}");
                    update_post_meta($post_id, '_phone', $phone);
                    update_post_meta($post_id, '_amount', $amount);
                    update_post_meta($post_id, '_reference', $BillRefNumber);
                    update_post_meta($post_id, '_receipt', $mpesaReceiptNumber);
                    update_post_meta($post_id, '_order_status', 'processing');
                }

                $order_id        = get_post_meta($post, '_order_id', true);
                $amount_due      = get_post_meta($post, '_amount', true);
                $before_ipn_paid = get_post_meta($post, '_paid', true);

                if (wc_get_order($order_id)) {
                    $order    = new WC_Order($order_id);
                    $customer = "{$FirstName} {$MiddleName} {$LastName}";
                } else {
                    $customer = "M-PESA Customer";
                }

                $after_ipn_paid = round($before_ipn_paid) + round($amount);
                $ipn_balance    = $after_ipn_paid - $amount_due;

                if (wc_get_order($order_id)) {
                    $order = new WC_Order($order_id);

                    if ($ipn_balance == 0) {
                        $mpesa = new WC_Gateway_MPESA();
                        $order->update_status('complete');
                        $order->payment_complete();
                        $order->add_order_note(__("Full M-PESA Payment Received From {$phone}. Receipt Number {$mpesaReceiptNumber}"));
                        update_post_meta($post, '_order_status', 'complete');

                        $headers = 'From: ' . get_bloginfo('name') . ' <' . get_bloginifo('admin_email') . '>' . "\r\n";
                        wp_mail($order["billing_address"], 'Your Mpesa payment', 'We acknowledge receipt of your payment via M-PESA of KSh. ' . $amount . ' on ' . $transactionDate . 'with receipt Number ' . $mpesaReceiptNumber . '.', $headers);

                        $total      = round($order->get_total());
                        $reference  = 'ORDER#' . $order_id;

                        $username = at_option('username');
                        $apiKey   = at_option('key');
                        $AT       = new AfricasTalking\SDK\AfricasTalking($username, $apiKey);
                        $sms      = $AT->sms();

                        $recipients = $order->get_billing_phone();
                        $message    = "We acknowledge receipt of your payment via M-PESA of KSh. {$amount} on {$transactionDate} for {$reference} with receipt number {$mpesaReceiptNumber}";
                        $from       = at_option('shortcode');

                        try {
                            // Thats it, hit send and we'll take care of the rest
                            $result = $sms->send([
                                'to'      => $recipients,
                                'message' => $message,
                                'from'    => at_option('shortcode'),
                            ]);
                        } catch (Exception $e) {
                            $result = [
                                'status' => 'error',
                                'data'   => $e->getMessage(),
                            ];
                        }
                    } elseif ($ipn_balance < 0) {
                        $currency = get_woocommerce_currency();
                        $order->payment_complete();
                        $order->add_order_note(__("{$phone} has overpayed by {$currency} {$ipn_balance}. Receipt Number {$mpesaReceiptNumber}"));
                        update_post_meta($post, '_order_status', 'complete');
                    } else {
                        $order->update_status('on-hold');
                        $order->add_order_note(__("M-PESA Payment from {$phone} Incomplete"));
                        update_post_meta($post, '_order_status', 'on-hold');
                    }
                }

                update_post_meta($post, '_paid', $after_ipn_paid);
                update_post_meta($post, '_amount', $amount_due);
                update_post_meta($post, '_balance', $ipn_balance);
                update_post_meta($post, '_phone', $phone);
                update_post_meta($post, '_customer', $customer);
                update_post_meta($post, '_order_id', $order_id);
                update_post_meta($post, '_receipt', $mpesaReceiptNumber);

                exit(wp_send_json(['tests']));
                break;

            default:
                exit(wp_send_json(['tests']));
        }
    }
}