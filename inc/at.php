<?php
function at_option($key, $default = '')
{
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        $at    = new WC_Africas_Talking_Gateway();
        $value = $at->get_option($key, $default);
    } else {
        $value = get_option('at_mpesa_options')[$key];
    }

    return $value;
}

function africastalking_post_id_by_meta_key_and_value($key, $value)
{
    global $wpdb;
    $meta = $wpdb->get_results("SELECT * FROM `" . $wpdb->postmeta . "` WHERE meta_key='" . $key . "' AND meta_value='" . $value . "'");
    if (is_array($meta) && !empty($meta) && isset($meta[0])) {
        $meta = $meta[0];
    }

    if (is_object($meta)) {
        return $meta->post_id;
    } else {
        return false;
    }
}

function at_wallet_balance()
{
    $username = at_option('username');
    $apiKey   = at_option('key');
    $AT       = new AfricasTalking\SDK\AfricasTalking($username, $apiKey);
    $url      = ($username == 'sandbox') 
    ? 'https://payments.sandbox.africastalking.com/query/wallet/balance' 
    : 'https://payments.africastalking.com/query/wallet/balance';
    $url = "{$url}?username={$username}";

    $response = wp_remote_get(
        $url,
        array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'apiKey'       => $apiKey,
            ),
        )
    );

    if (is_wp_error($response)) {
        $balance    = 'Could not connect to AT';
    } else {
        $response   = json_decode($response['body'], true);
        $balance    = isset($response['balance']) ? $response['balance'] : $response['status'];
    }

    return $balance;
}

function at_sms($phones, $message = 'Test message')
{
    $username = at_option('username');
    $apiKey   = at_option('key');
    $AT       = new AfricasTalking\SDK\AfricasTalking($username, $apiKey);
            $sms        = $AT->sms();
            $recipients = strip_tags(trim($phones));

            $phones     = array();
            if (strpos(',', $recipients) !== false) {
                $phones = explode(',', $recipients);
            } else {
                $phones = $recipients;
            }

            try {
                // Thats it, hit send and we'll take care of the rest
                reurn $sms->send([
                    'to'      => $phones,
                    'message' => $message,
                    'from'    => at_option('shortcode'),
                ]);
            } catch(Throwable $th){
                return $th;
            }

}
